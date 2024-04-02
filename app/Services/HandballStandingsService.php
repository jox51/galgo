<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\BaseballSchedule;
use App\Models\BaseballWithRanks;
use App\Models\Handball;
use App\Models\HandballAlgoRank;

class HandballStandingsService {

  public function fetchHandballStandings() {

    $games = $this->fetchTodaysGames();
    $gamesWithStandings = $this->fetchStandingsForGames($games);
    $this->storeGamesToDatabase($gamesWithStandings);

    // Calculate algo rank for each game
    $gamesWithAlgoRank =  $this->calculateAlgoRank();
    $this->saveAlgoRankToDatabase($gamesWithAlgoRank);
  }

  public function fetchTodaysGames() {

    // Use Carbon or the date() function to get today's date in 'Y-m-d' format

    $today = Carbon::now("America/New_York")->format('Y-m-d');
    $apiKey = env('RAPID_API_KEY');

    // Make the API call to fetch fixtures for today
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => $apiKey,
      'X-RapidAPI-Host' => 'api-handball.p.rapidapi.com',
    ])->get('https://api-handball.p.rapidapi.com/games', [
      'date' => $today, // Dynamically set the date to today
    ]);

    // Decode the response body to an array or object, based on your needs
    $games = $response->json()['response'];
    return $games;
  }

  public function fetchStandingsForGames(array $games) {
    $apiKey = env('RAPID_API_KEY');
    $standingsForAllGames = [];

    foreach ($games as $game) {
      $leagueId = $game['league']['id'];
      $season = $game['league']['season'];
      $homeTeamId = $game['teams']['home']['id'];
      $awayTeamId = $game['teams']['away']['id'];
      $gameDate = Carbon::createFromTimestamp($game['timestamp'], "UTC")->timezone("America/New_York")->format("m-d-Y");
      $game['game_date'] = $gameDate;


      $response = Http::withHeaders([
        'X-RapidAPI-Key' => $apiKey,
        'X-RapidAPI-Host' => 'api-handball.p.rapidapi.com',
      ])->get('https://api-handball.p.rapidapi.com/standings', [
        'league' => $leagueId,
        'season' => $season,
      ]);

      if ($response->successful() && $response->json()['response']) {
        $standings = $response->json()['response'][0];
      } else {
        continue;
      }



      $numOfTeams = count($standings);
      $game['league']['numOfTeams'] = $numOfTeams;

      // Loop through the standings to find the ranks for the home and away teams
      foreach ($standings as $standing) {

        if ($standing['team']['id'] === $homeTeamId && $standing['position'] !== null) {
          $game['home_rank'] = $standing['position'];
        } elseif ($standing['team']['id'] === $awayTeamId && $standing['position'] !== null) {
          $game['away_rank'] = $standing['position'];
        }
      }

      $standingsForAllGames[] = $game;
    }

    return $standingsForAllGames;
  }

  public function storeGamesToDatabase($games) {

    foreach ($games as $game) {
      $handballGame = new Handball([
        'timestamp' =>  Carbon::createFromTimestamp($game['timestamp'], "UTC")->toDateTimeString(),
        'league' => json_encode($game['league']),
        'teams' => json_encode($game['teams']),
        'game_date' => $game['game_date'],
        'away_rank' => $game['away_rank'] ?? 0,
        'home_rank' => $game['home_rank'] ?? 0,
      ]);
      $handballGame->save();
    }
  }

  public function calculateAlgoRank() {

    $dateToday = Carbon::now('America/New_York')->toDateString();
    $games = Handball::whereDate('timestamp', $dateToday)->latest()->get()->toArray();


    $processedGames = []; // Initialize an empty array to store processed games


    foreach ($games as $game) {

      // Decode JSON strings to arrays
      $leagueData = json_decode($game['league'], true);
      $teamsData = json_decode($game['teams'], true);
      $homeTeamData = $teamsData['home'];
      $awayTeamData = $teamsData['away'];
      $numOfTeams =  $leagueData['numOfTeams'];
      $homeRank = $game['home_rank'];
      $awayRank = $game['away_rank'];

      if ($homeRank === 0 || $awayRank === 0) {
        continue;
      }
      $homeLeagueStandingPercentage = ($homeRank / $numOfTeams) * 100;
      $awayLeagueStandingPercentage = ($awayRank / $numOfTeams) * 100;



      // Initialize default algo_rank and probabilities
      $game['algo_rank'] = 'h'; // Placeholder for conditions not explicitly covered
      $homeTeamData['probability'] = 0.5;
      $awayTeamData['probability'] = 0.5;

      // Adjust the conditions based on relative standings
      if ($homeLeagueStandingPercentage <= 20 && $awayLeagueStandingPercentage >= 80) {
        $game['algo_rank'] = 'a';
        $homeTeamData['probability'] = 0.95;
        $awayTeamData['probability'] = 0.05;
      } else if ($awayLeagueStandingPercentage <= 20 && $homeLeagueStandingPercentage >= 80) {
        $game['algo_rank'] = 'b';
        $homeTeamData['probability'] = 0.10;
        $awayTeamData['probability'] = 0.90;
      } else if ($homeLeagueStandingPercentage <= 20 && $awayLeagueStandingPercentage >= 50 && $awayLeagueStandingPercentage < 80) {
        $game['algo_rank'] = 'c';
        $homeTeamData['probability'] = 0.85;
        $awayTeamData['probability'] = 0.15;
      } else if ($awayLeagueStandingPercentage <= 20 && $homeLeagueStandingPercentage >= 50 && $homeLeagueStandingPercentage < 80) {
        $game['algo_rank'] = 'd';
        $homeTeamData['probability'] = 0.20;
        $awayTeamData['probability'] = 0.80;
      } else if ($homeLeagueStandingPercentage >= 20 && $homeLeagueStandingPercentage < 40 && $awayLeagueStandingPercentage >= 80) {
        $game['algo_rank'] = 'e';
        $homeTeamData['probability'] = 0.25;
        $awayTeamData['probability'] = 0.75;
      } else if ($awayLeagueStandingPercentage >= 20 && $awayLeagueStandingPercentage < 40 && $homeLeagueStandingPercentage >= 80) {
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
        'timestamp' => $game['timestamp'],
        'game_date' => $game['game_date'],
        'league' => $game['league'],
        'teams' => $game['teams'],
        'algo_rank' => $game['algo_rank'],
        'home_probability' =>  $homeTeamData['probability'],
        'away_probability' => $awayTeamData['probability'],
      ];
      $processedGames[] = $processedGame; // Push the processed game to the array

    }

    return $processedGames;
  }

  public function saveAlgoRankToDatabase($gamesWithAlgoRank) {

    foreach ($gamesWithAlgoRank as $game) {

      $handballGame = new HandballAlgoRank([
        'timestamp' =>  $game['timestamp'],
        'game_date' => $game['game_date'],
        'league' => $game['league'],
        'teams' => $game['teams'],
        'algo_rank' => $game['algo_rank'],
        'home_probability' =>  $game['home_probability'],
        'away_probability' => $game['away_probability'],
      ]);
      $handballGame->save();
    }
  }
}
