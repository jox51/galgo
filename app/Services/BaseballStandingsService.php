<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\BaseballSchedule;
use App\Models\BaseballWithRanks;

class BaseballStandingsService {

  public function getStandings() {
    $today = Carbon::now("America/New_York");

    $schedules =  $this->getSchedules();
    $filteredSchedules = $this->filterMLBSchedule($schedules);

    // Grab Standings
    $standings = $this->fetchBaseballStandings($filteredSchedules);

    // Add rankings to teams who are on the schedule today
    $schedulesWithRanks = $this->addRankingsToSchedule($standings, $filteredSchedules);

    // Save schedules with ranks in database
    $this->saveSchedulesToDatabase($schedulesWithRanks);

    $schedulesFromDatabase = BaseballSchedule::whereDate('startTimestamp', $today)->get()->toArray();

    $gamesWithAlgoRanks = $this->calculateAlgoRanks($schedulesFromDatabase);

    $this->storeBaseballRanksWithAlgo($gamesWithAlgoRanks);
  }

  public function getSchedules() {
    $apiKey = env('RAPID_API_KEY');


    $today = Carbon::now("America/New_York");
    $day = $today->day;
    $month = $today->month;
    $year = $today->year;


    // Construct the API URL with the current date
    $url = "https://baseballapi.p.rapidapi.com/api/baseball/matches/{$day}/{$month}/{$year}";



    // Make the API request using Laravel's HTTP client
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => $apiKey,
      'X-RapidAPI-Host' => 'baseballapi.p.rapidapi.com',
    ])->get($url);


    // Check if the request was successful
    if ($response->successful()) {
      // Decode the JSON response
      $data = $response->json();


      // Process the data as needed
      return $data;
    } else {
      // Handle errors or unsuccessful responses
      // You might want to log this or throw an exception
      return null;
    }
  }

  public function filterMLBSchedule($schedules) {
    $filteredSchedules = [];
    foreach ($schedules['events'] as $schedule) {

      if (strpos($schedule['season']['name'], "MLB") !== false) {

        // Convert startTimestamp to matchStartDate
        $matchStartDate = Carbon::createFromTimestamp($schedule['startTimestamp'])->format('m/d/Y');

        // // Store in database
        // BaseballSchedule::create([
        //   'season' => json_encode($schedule['season']),
        //   'tournament' => json_encode($schedule['tournament']),
        //   'homeTeam' => json_encode($schedule['homeTeam']),
        //   'awayTeam' => json_encode($schedule['awayTeam']),
        //   'matchStartDate' => $matchStartDate,
        // ]);

        $schedule['matchStartDate'] = $matchStartDate; // Add the formatted date to the schedule array
        $schedule['startTimestamp'] = $schedule['startTimestamp'];
        $filteredSchedules[] = $schedule;
      }
    }
    return $filteredSchedules;
  }

  public function fetchBaseballStandings($filteredSchedules) {

    if (!empty($filteredSchedules)) {
      $firstMLBSchedule = $filteredSchedules[0];
      $seasonId = $firstMLBSchedule['season']['id'];
      $tournamentId = $firstMLBSchedule['tournament']['uniqueTournament']['id'];

      $apiKey = env('RAPID_API_KEY');
      $url = "https://baseballapi.p.rapidapi.com/api/baseball/tournament/$tournamentId/season/$seasonId/standings/total";

      $response = Http::withHeaders([
        'X-RapidAPI-Key' => $apiKey,
        'X-RapidAPI-Host' => 'baseballapi.p.rapidapi.com',
      ])->get($url);

      if ($response->successful()) {
        $data = $response->json();


        // Filter for MLB standings if needed
        $mlbStandings = collect($data['standings'])->filter(function ($standing) {
          return $standing['name'] === 'MLB';
        })->all();

        return array_values($mlbStandings);
      } else {
        // Handle errors or unsuccessful responses
        return null;
      }
    }
  }

  public function addRankingsToSchedule($standings, $filteredSchedules) {

    $standingsRows = $standings[0]['rows'] ?? [];
    $totalTeamsInRanking = count($standingsRows);

    foreach ($filteredSchedules as &$schedule) {
      $homeTeamId = $schedule['homeTeam']['id'];
      $awayTeamId = $schedule['awayTeam']['id'];

      foreach ($standingsRows as $row) {

        if ($row['team']['id'] == $homeTeamId) {

          // Attach the ranking to the home team
          $homeTeamRanking = $row['position'];
          $schedule['homeTeam']['ranking'] = $homeTeamRanking;
          $schedule['tournament']['totalTeams'] = $totalTeamsInRanking;
        }

        if ($row['team']['id'] == $awayTeamId) {
          // Attach the ranking to the away team
          $awayTeamRanking = $row['position'];
          $schedule['awayTeam']['ranking'] = $awayTeamRanking;
          $schedule['tournament']['totalTeams'] = $totalTeamsInRanking;
        }
      }
    }
    return $filteredSchedules;
  }

  public function saveSchedulesToDatabase($schedulesWithRanks) {
    foreach ($schedulesWithRanks as $schedule) {
      $startDatetime = Carbon::createFromTimestamp($schedule['startTimestamp'])->toDateTimeString();

      BaseballSchedule::create([
        'season' => json_encode($schedule['season']),
        'tournament' => json_encode($schedule['tournament']),
        'homeTeam' => json_encode($schedule['homeTeam']),
        'awayTeam' => json_encode($schedule['awayTeam']),
        'matchStartDate' => $schedule['matchStartDate'],
        'startTimestamp' => $startDatetime,

      ]);
    }
  }

  public function calculateAlgoRanks($games) {
    $processedGames = [];

    foreach ($games as $game) {

      $homeTeam = json_decode($game['homeTeam'], true);
      $awayTeam = json_decode($game['awayTeam'], true);
      $homeTeamTournament = json_decode($game['tournament'], true);
      $awayTeamTournament = json_decode($game['tournament'], true);

      $homeTeamRank = $homeTeam['ranking'];
      $awayTeamRank = $awayTeam['ranking'];

      $homeTeamTotalTeams = $homeTeamTournament['totalTeams'];
      $awayTeamTotalTeams = $awayTeamTournament['totalTeams'];

      $homeTeamRankPercentage = $homeTeamRank / $homeTeamTotalTeams * 100;
      $awayTeamRankPercentage = $awayTeamRank / $awayTeamTotalTeams * 100;

      // Initialize default algo_rank and probabilities
      $game['algo_rank'] = $game['algo_rank'] ?? 'h'; // Placeholder for conditions not explicitly covered
      $homeTeam['probability'] = 0.5;
      $awayTeam['probability'] = 0.5;

      if ($homeTeamRankPercentage <= 17 && $awayTeamRank >= 67) {
        $game['algo_rank'] = 'a';
        $homeTeam['probability'] = 0.95;
        $awayTeam['probability'] = 0.05;
      } else if ($awayTeamRankPercentage <= 17 && $homeTeamRankPercentage >= 67) {
        $game['algo_rank'] = 'b';
        $homeTeam['probability'] = 0.10;
        $awayTeam['probability'] = 0.90;
      } else if ($homeTeamRankPercentage <= 17 && $awayTeamRankPercentage >= 50 && $awayTeamRankPercentage < 67) {
        $game['algo_rank'] = 'c';
        $homeTeam['probability'] = 0.85;
        $awayTeam['probability'] = 0.15;
      } else if ($awayTeamRankPercentage <= 17 && $homeTeamRankPercentage >= 50 && $homeTeamRankPercentage < 67) {
        $game['algo_rank'] = 'd';
        $homeTeam['probability'] = 0.20;
        $awayTeam['probability'] = 0.80;
      } else if ($homeTeamRankPercentage > 17 && $homeTeamRankPercentage <= 33 && $awayTeamRankPercentage >= 67) {
        $game['algo_rank'] = 'e';
        $homeTeam['probability'] = 0.75;
        $awayTeam['probability'] = 0.25;
      } else if ($awayTeamRankPercentage > 17 && $awayTeamRankPercentage <= 33 && $homeTeamRankPercentage >= 67) {
        $game['algo_rank'] = 'f';
        $homeTeam['probability'] = 0.35;
        $awayTeam['probability'] = 0.65;
      } else {
        // Additional or default logic for setting probabilities based on other factors can go here
        // This part of the code adjusts probabilities based on rankings if none of the above conditions are met
        if ($homeTeamRankPercentage < $awayTeamRankPercentage) {
          $homeTeam['probability'] += 0.05;
          $awayTeam['probability'] -= 0.05;
        } else {
          $homeTeam['probability'] -= 0.03;
          $awayTeam['probability'] += 0.03;
        }
      }

      // Ensure probabilities are within bounds [0,1]
      $homeTeam['probability'] = max(0, min(1, $homeTeam['probability']));
      $awayTeam['probability'] = max(0, min(1, $awayTeam['probability']));

      // Update the game array with the calculated values
      $game['homeTeam'] = $homeTeam;
      $game['awayTeam'] = $awayTeam;

      $processedGames[] = $game;
    }
    return $processedGames;
  }

  public function storeBaseballRanksWithAlgo($gamesWithAlgoRanks) {
    foreach ($gamesWithAlgoRanks as $game) {
      $startDatetime = Carbon::createFromFormat('Y-m-d H:i:s', $game['startTimestamp'])->toDateTimeString();

      BaseballWithRanks::create([
        'season' => json_encode($game['season']),
        'tournament' => json_encode($game['tournament']),
        'homeTeam' => json_encode($game['homeTeam']),
        'awayTeam' => json_encode($game['awayTeam']),
        'algo_rank' => $game['algo_rank'],
        'matchStartDate' => $game['matchStartDate'],
        'startTimestamp' => $startDatetime,
      ]);
    }
  }
}
