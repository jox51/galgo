<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\NbaGame;
use App\Models\ProcessedSoccerFixture;
use App\Models\SoccerFixture;
use Carbon\Carbon;

class SoccerStandingsService {

  public function fetchSoccerStandings() {

    // max time execution
    set_time_limit(300); // Sets the maximum execution time to 60 seconds


    // Fetch todays soccer fixtures
    $gamesForToday = $this->fetchFixturesForToday();

    // // Add standings to the fixtures
    $todaysGamesAppended = $this->fetchAndAppendStandings($gamesForToday['response']);


    // Save the standings to the database
    $this->saveSoccerStandings($todaysGamesAppended);

    $dateToday = Carbon::today()->toDateString();

    //Fetch the latest data from the database based on todays date
    $latestData = SoccerFixture::whereDate('created_at', $dateToday)
      ->latest()
      ->get();

    // Fetch and append correct scores
    $this->fetchAndAppendCorrectScores($latestData);

    // calculate algo ranking for soccer
    $processedData = $this->calculateAlgoRankingForSoccer($latestData);
    // Save the processed data to the database
    $this->saveProcessedSoccerData($processedData);
  }

  public function fetchFixturesForToday() {
    // Use Carbon or the date() function to get today's date in 'Y-m-d' format
    $todayDate = date('Y-m-d');

    // Make the API call to fetch fixtures for today
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => '1240f4bf06msh22e9d539d535101p1b1ff3jsn337bd1967b6d',
      'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
    ])->get('https://api-football-v1.p.rapidapi.com/v3/fixtures', [
      'date' => $todayDate, // Dynamically set the date to today
      ' timezone' => 'America/New_York'
    ]);

    // Decode the response body to an array or object, based on your needs
    $fixtures = $response->json();

    // Handle the fixtures data as needed
    return $fixtures;
  }

  public function fetchAndAppendStandings($gamesForToday) {

    $fixtures = $gamesForToday;
    $filteredFixtures = [];


    foreach ($fixtures as &$fixture) {

      $leagueId = $fixture['league']['id'];
      $season = $fixture['league']['season'];

      // Fetch standings
      $standingsResponse = Http::withHeaders([
        'X-RapidAPI-Key' => '1240f4bf06msh22e9d539d535101p1b1ff3jsn337bd1967b6d',
        'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
      ])->get('https://api-football-v1.p.rapidapi.com/v3/standings', [
        'season' => $season,
        'league' => $leagueId,
      ]);

      $standingsData = $standingsResponse->json();

      $foundHomeRanking = false;
      $foundAwayRanking = false;


      // Loop through standings to find the team rankings
      if (!empty($standingsData) && isset($standingsData['response'][0]['league']['standings'][0])) {
        $standings = $standingsData['response'][0]['league']['standings'][0];

        $foundHomeRanking = false;
        $foundAwayRanking = false;

        $numOfTeams = count($standings);
        $fixture['league']['numOfTeams'] = $numOfTeams;

        foreach ($standings as $standing) {
          if (isset($standing['team']['id'])) {

            if ($standing['team']['id'] === $fixture['teams']['home']['id']) {
              $fixture['teams']['home']['ranking'] = $standing['rank'];
              $foundHomeRanking = true;
            }

            if ($standing['team']['id'] === $fixture['teams']['away']['id']) {
              $fixture['teams']['away']['ranking'] = $standing['rank'];
              $foundAwayRanking = true;
            }
          }
        }
        // If both home and away teams have rankings, add the fixture to the filtered list
        if ($foundHomeRanking && $foundAwayRanking) {
          $filteredFixtures[] = $fixture;
        }
      }
    }
    // unset($fixture);

    return $filteredFixtures;
  }


  public function saveSoccerStandings($standings) {
    // Save the standings to the database
    $uniqueStandings = collect($standings)->unique(function ($item) {
      // Check if $item['teams'] is a string and decode it, otherwise use it directly
      $teamsData = is_string($item['teams']) ? json_decode($item['teams'], true) : $item['teams'];
      return $teamsData['home']['id'] . '-' . $teamsData['away']['id'];
    })->all(); // Convert back to array if needed

    foreach ($uniqueStandings as $standing) {
      $soccerFixture = new SoccerFixture([
        'fixture' => json_encode($standing['fixture']),
        'league' => json_encode($standing['league']),
        'teams' => json_encode($standing['teams']),
      ]);
      $soccerFixture->save();
    }
  }

  public function fetchAndAppendCorrectScores($fixtures) {
    // Add logic to fetch and append correct scores

    // $processedGames = [];

    foreach ($fixtures as $fixture) {

      $teams = json_decode($fixture->teams, true);
      $fixtureObject = json_decode($fixture->fixture, true);

      $fixtureId = $fixtureObject['id'];
      $apiKey = env('RAPID_API_KEY');

      // Fetch standings
      $correctScoresResponse = Http::withHeaders([
        'X-RapidAPI-Key' => $apiKey,
        'X-RapidAPI-Host' => 'api-football-v1.p.rapidapi.com',
      ])->get('https://api-football-v1.p.rapidapi.com/v3/predictions', [
        'fixture' => $fixtureId,
      ]);

      $correctScoresData = $correctScoresResponse->json();
      $goalsHome = $correctScoresData['response'][0]['predictions']['goals']['home'] ?? 0;
      $goalsAway = $correctScoresData['response'][0]['predictions']['goals']['away'] ?? 0;
      $winner = $correctScoresData['response'][0]['predictions']['winner']  ?? 0;






      $teams['home']['goals'] = $goalsHome;
      $teams['away']['goals'] = $goalsAway;
      $fixtureObject['winner'] = $winner;


      $fixture->teams = json_encode($teams);
      $fixture->fixture = json_encode($fixtureObject);


      $fixture->save();



      // $processedGames[] = $fixture;
    }
  }

  public function calculateAlgoRankingForSoccer($games) {
    $processedGames = []; // Initialize an empty array to store processed games

    foreach ($games as &$game) {

      // Decode JSON strings to arrays
      $fixtureData = json_decode($game->fixture, true);
      $leagueData = json_decode($game->league, true);
      $homeTeamData = json_decode($game->teams, true)['home'];
      $awayTeamData = json_decode($game->teams, true)['away'];

      // Adjust 'goals' for both teams
      $homeTeamData['goals'] = (int) floor(abs($homeTeamData['goals']));
      $awayTeamData['goals'] = (int) floor(abs($awayTeamData['goals']));

      $numOfTeams =  $leagueData['numOfTeams'];

      $homeLeagueStandingPercentage = ($homeTeamData['ranking'] / $numOfTeams) * 100;
      $awayLeagueStandingPercentage = ($awayTeamData['ranking'] / $numOfTeams) * 100;


      // Initialize default algo_rank and probabilities
      $game['algo_rank'] = 'h'; // Placeholder for conditions not explicitly covered
      $homeTeamData['probability'] = 0.5;
      $awayTeamData['probability'] = 0.5;

      // Adjust the conditions based on relative standings
      if ($homeLeagueStandingPercentage <= 17 && $awayLeagueStandingPercentage >= 67) {
        $game['algo_rank'] = 'a';
        $homeTeamData['probability'] = 0.95;
        $awayTeamData['probability'] = 0.05;
      } else if ($awayLeagueStandingPercentage <= 17 && $homeLeagueStandingPercentage >= 67) {
        $game['algo_rank'] = 'b';
        $homeTeamData['probability'] = 0.10;
        $awayTeamData['probability'] = 0.90;
      } else if ($homeLeagueStandingPercentage <= 17 && $awayLeagueStandingPercentage >= 50 && $awayLeagueStandingPercentage < 67) {
        $game['algo_rank'] = 'c';
        $homeTeamData['probability'] = 0.85;
        $awayTeamData['probability'] = 0.15;
      } else if ($awayLeagueStandingPercentage <= 17 && $homeLeagueStandingPercentage >= 50 && $homeLeagueStandingPercentage < 67) {
        $game['algo_rank'] = 'd';
        $homeTeamData['probability'] = 0.20;
        $awayTeamData['probability'] = 0.80;
      } else if ($homeLeagueStandingPercentage >= 17 && $homeLeagueStandingPercentage < 33 && $awayLeagueStandingPercentage >= 67) {
        $game['algo_rank'] = 'e';
        $homeTeamData['probability'] = 0.25;
        $awayTeamData['probability'] = 0.75;
      } else if ($awayLeagueStandingPercentage >= 17 && $awayLeagueStandingPercentage < 33 && $homeLeagueStandingPercentage >= 67) {
        $game['algo_rank'] = 'f';
        $homeTeamData['probability'] = 0.35;
        $awayTeamData['probability'] = 0.65;
      } else {
        if ($homeLeagueStandingPercentage < $awayLeagueStandingPercentage) {
          $homeTeamData['probability'] += 0.05;
          $awayTeamData['probability'] -= 0.05;
        } else {
          $homeTeamData['probability'] -= 0.03;
          $awayTeamData['probability'] += 0.03;
        }
      }


      // Ensure probabilities are within bounds [0,1]
      $homeTeamData['probability'] = max(0, min(1, $homeTeamData['probability']));
      $awayTeamData['probability'] = max(0, min(1, $awayTeamData['probability']));

      $processedGame = [
        'fixture_data' => [
          'fixture' => json_decode($game->fixture, true),
          'league' => json_decode($game->league, true),
          'teams' => json_decode($game->teams, true),
          'home_goals' => $homeTeamData['goals'] ?? 0,
          'away_goals' => $awayTeamData['goals'] ?? 0,
        ],
        'algo_rank' => $game['algo_rank'],
        'home_probability' =>  $homeTeamData['probability'],
        'away_probability' => $awayTeamData['probability'],
        // 'home_goals' => $homeTeamData['goals'] ?? 0,
        // 'away_goals' => $awayTeamData['goals'] ?? 0,

      ];

      $processedGames[] = $processedGame; // Push the processed game to the array

    }

    return $processedGames;
  }

  public function saveProcessedSoccerData($processedGames) {
    foreach ($processedGames as $game) {
      // Assuming you have a model named ProcessedSoccerFixture
      ProcessedSoccerFixture::create([
        'fixture_data' => json_encode($game['fixture_data']),
        'algo_rank' => $game['algo_rank'],
        'home_probability' => $game['home_probability'],
        'away_probability' => $game['away_probability'],
        // 'home_goals' => $game['home_goals'],
        // 'away_goals' => $game['away_goals'],
      ]);
    }
  }
}
