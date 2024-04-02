<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\TennisMatch;
use App\Models\TennisMatchWithAlgoRank;

class TennisStandingsService {

  public function fetchTennisStandings() {
    $this->getTodaysGames();
  }

  public function getTodaysGames() {
    $today = Carbon::now("America/New_York");
    $todayUTC = Carbon::today();


    // Fetch tennis schedules for today

    $schedules = $this->fetchTennisSchedules($today->day, $today->month, $today->year);
    $this->saveTennisSchedule($schedules);

    $savedSchedules = TennisMatch::whereDate('created_at', $todayUTC)->latest()->get()->toArray();
    $schedulesWithStandings =  $this->addStandingsToSchedule($savedSchedules);
    $scheduleWithAlgoRanking = $this->calculateAlgoRankingForTennis($schedulesWithStandings);
    $this->storeTennisMatchesWithAlgoRanks($scheduleWithAlgoRanking);


    return $savedSchedules;
  }

  public function fetchTennisSchedules($day, $month, $year) {
    $apiKey = env('RAPID_API_KEY');

    $response = Http::withHeaders([
      'X-RapidAPI-Key' => $apiKey,
      'X-RapidAPI-Host' => 'tennisapi1.p.rapidapi.com'
    ])->get("https://tennisapi1.p.rapidapi.com/api/tennis/events/{$day}/{$month}/{$year}");


    // Check if the request was successful
    if ($response->successful()) {
      return $response->json();
    }

    // Handle the case where the API request fails
    return null;
  }

  public function saveTennisSchedule($schedules) {


    foreach ($schedules['events'] as $event) {


      TennisMatch::create([
        'season' => json_encode($event['season']),
        'tournament' => json_encode($event['tournament']),
        'homeTeam' => json_encode($event['homeTeam']),
        'awayTeam' => json_encode($event['awayTeam']),
        'startTime' => Carbon::createFromTimestamp($event['startTimestamp'])->toDateTimeString(),
        'matchStartDate' => Carbon::createFromTimestamp($event['startTimestamp'])->format('Y-m-d'),
      ]);
    }
  }

  public function fetchAtpRankings() {
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => env('RAPID_API_KEY'),
      'X-RapidAPI-Host' => 'tennisapi1.p.rapidapi.com'
    ])->get('https://tennisapi1.p.rapidapi.com/api/tennis/rankings/atp');

    return $response->successful() ? $response->json()['rankings'] : [];
  }

  public function fetchWtaRankings() {
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => env('RAPID_API_KEY'),
      'X-RapidAPI-Host' => 'tennisapi1.p.rapidapi.com'
    ])->get('https://tennisapi1.p.rapidapi.com/api/tennis/rankings/wta');

    return $response->successful() ? $response->json()['rankings'] : [];
  }


  public function addStandingsToSchedule($schedules) {

    $atpRankings = $this->fetchAtpRankings();
    $wtaRankings = $this->fetchWtaRankings();

    // Initialize an empty array to store processed games
    $processedGames = [];

    foreach ($schedules as &$schedule) {
      $season = json_decode($schedule['season'], true);
      $homeTeam = json_decode($schedule['homeTeam'], true);
      $awayTeam = json_decode($schedule['awayTeam'], true);

      // Skip matches with 'Doubles' or 'ITF' in the season name
      if (strpos($season['name'], 'Doubles') !== false || strpos($season['name'], 'ITF') !== false) {
        continue;
      }


      if (strpos($season['name'], 'ATP') !== false) {
        // Handle ATP singles
        $homeTeam['ranking'] = $this->findRankingById($homeTeam['id'], $atpRankings);
        $awayTeam['ranking'] = $this->findRankingById($awayTeam['id'], $atpRankings);
      } elseif (strpos($season['name'], 'WTA') !== false) {
        // Handle WTA singles
        $homeTeam['ranking'] = $this->findRankingById($homeTeam['id'], $wtaRankings);
        $awayTeam['ranking'] = $this->findRankingById($awayTeam['id'], $wtaRankings);
      }


      $schedule['homeTeam'] = json_encode($homeTeam);
      $schedule['awayTeam'] = json_encode($awayTeam);
      $schedule['startTime'] = $schedule['startTime'];
      $schedule['matchStartDate'] = $schedule['matchStartDate'];
      $processedGames[] = $schedule;
    }

    return $processedGames;
  }

  private function findRankingById($teamId, $rankings) {
    foreach ($rankings as $ranking) {

      if ($ranking['team']['id'] == $teamId) {
        return $ranking;
      }
    }
    return null; // Return null or appropriate value if ranking not found
  }

  public function calculateAlgoRankingForTennis($games) {
    $processedGames = [];

    // Define percentage thresholds for ranking standard (Highest Tier Matches)
    $upperFirstThreshold = 17;
    $lowerFirstThreshold = 67;
    $upperSecondThreshold = 17;
    $lowerSecondThresholdMin = 50;
    $lowerSecondThresholdMax = 67;
    $upperThirdThresholdMin = 17;
    $upperThirdThresholdMax = 33;
    $lowerThirdThreshold = 67;

    // Define perceantage adjustments for Mid Tier Matches
    $midUpperFirstThreshold = 25;
    $midLowerFirstThreshold = 60;
    $midUpperSecondThreshold = 25;
    $midUpperSecondThresholdMin = 50;
    $midUpperSecondThresholdMax = 60;
    $midUpperThirdThresholdMin = 25;
    $midUpperThirdThresholdMax = 35;
    $midLowerThirdThreshold = 62;

    // Define percentage adjustments for Last Tier Matches
    $lastUpperFirstThreshold = 35;
    $lastLowerFirstThreshold = 60;
    $lastUpperSecondThreshold = 35;
    $lastLowerSecondThresholdMin = 50;
    $lastLowerSecondThresholdMax = 60;
    $lastUpperThirdThresholdMin = 35;
    $lastUpperThirdThresholdMax = 42;
    $lastLowerThirdThreshold = 58;





    foreach ($games as &$game) {
      $season = json_decode($game['season'], true);
      $homeTeam = json_decode($game['homeTeam'], true);
      $awayTeam = json_decode($game['awayTeam'], true);

      // Assuming you have the total number of ATP and WTA players stored somewhere
      $numOfPlayersAtp = count($this->fetchAtpRankings());
      $numOfPlayersWta = count($this->fetchWtaRankings());

      $homeTeamRanking = $homeTeam['ranking']['ranking'] ?? null;
      $awayTeamRanking = $awayTeam['ranking']['ranking'] ?? null;

      // Determine the correct player count based on ATP or WTA
      $numOfPlayers = strpos($game['season'], 'ATP') !== false ? $numOfPlayersAtp : $numOfPlayersWta;


      $tier = $this->determineMatchTier($season['name']);
      $thresholds = $this->getTierThresholds($tier);

      $homeRankingPercentage = ($homeTeamRanking / $numOfPlayers) * 100;
      $awayRankingPercentage = ($awayTeamRanking / $numOfPlayers) * 100;

      // Default values
      $game['algo_rank'] = 'h';
      $homeTeam['probability'] = 0.5;
      $awayTeam['probability'] = 0.5;

      // Example condition, adjust according to your logic
      if ($homeRankingPercentage <= $thresholds['upperFirstThreshold'] && $awayRankingPercentage >= $thresholds['lowerFirstThreshold']) {
        $game['algo_rank'] = 'a';
        $homeTeam['probability'] = 0.95;
        $awayTeam['probability'] = 0.05;
      } else if ($awayRankingPercentage <= $thresholds['upperFirstThreshold'] && $homeRankingPercentage >= $thresholds['lowerFirstThreshold']) {
        $game['algo_rank'] = 'b';
        $homeTeam['probability'] = 0.10;
        $awayTeam['probability'] = 0.90;
      } else if ($homeRankingPercentage <= $thresholds['upperSecondThreshold'] && $awayRankingPercentage >= $thresholds['lowerSecondThresholdMin'] && $awayRankingPercentage < $thresholds['lowerSecondThresholdMax']) {
        $game['algo_rank'] = 'c';
        $homeTeam['probability'] = 0.85;
        $awayTeam['probability'] = 0.15;
      } else if ($awayRankingPercentage <= $thresholds['upperSecondThreshold'] && $homeRankingPercentage >= $thresholds['lowerSecondThresholdMin']  && $homeRankingPercentage < $thresholds['lowerSecondThresholdMax']) {
        $game['algo_rank'] = 'd';
        $homeTeam['probability'] = 0.20;
        $awayTeam['probability'] = 0.80;
      } else if ($homeRankingPercentage >= $thresholds['upperThirdThresholdMin']  && $homeRankingPercentage < $thresholds['upperThirdThresholdMax'] && $awayRankingPercentage >= $thresholds['lowerThirdThreshold']) {
        $game['algo_rank'] = 'e';
        $homeTeam['probability'] = 0.75;
        $awayTeam['probability'] = 0.25;
      } else if ($awayRankingPercentage >= $thresholds['upperThirdThresholdMin'] && $awayRankingPercentage < $thresholds['upperThirdThresholdMax'] && $homeRankingPercentage >= $thresholds['lowerThirdThreshold']) {
        $game['algo_rank'] = 'f';
        $homeTeam['probability'] = 0.35;
        $awayTeam['probability'] = 0.65;
      } else {
        if ($homeRankingPercentage < $awayRankingPercentage) {
          $homeTeam['probability'] += 0.05;
          $awayTeam['probability'] -= 0.05;
        } else {
          $homeTeam['probability'] -= 0.03;
          $awayTeam['probability'] += 0.03;
        }
      }


      // Add more conditions as needed

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

  private function determineMatchTier($seasonName) {
    // Define the different tiers of tennis matches
    $higherTierMatches = ['Grand Slam', 'Masters', 'ATP Finals', 'WTA Finals', '1000'];
    $mediumTierMatches = ['500',  '250', 'Premier', 'International'];
    $lowerTierMatches = ['Challenger', '125'];

    // Check for higher tier matches
    foreach ($higherTierMatches as $keyword) {
      if (stripos($seasonName, $keyword) !== false) {
        return 'higher';
      }
    }

    // Check for medium tier matches
    foreach ($mediumTierMatches as $keyword) {
      if (stripos($seasonName, $keyword) !== false) {
        return 'medium';
      }
    }

    // Check for lower tier matches
    foreach ($lowerTierMatches as $keyword) {
      if (stripos($seasonName, $keyword) !== false) {
        return 'lower';
      }
    }

    // Default to 'lower' if no specific keywords are found
    return 'lower';
  }

  private function getTierThresholds($tier) {
    // Define percentage thresholds for each tier
    $thresholds = [
      'higher' => [
        'upperFirstThreshold' => 17,
        'lowerFirstThreshold' => 67,
        'upperSecondThreshold' => 17,
        'lowerSecondThresholdMin' => 50,
        'lowerSecondThresholdMax' => 67,
        'upperThirdThresholdMin' => 17,
        'upperThirdThresholdMax' => 33,
        'lowerThirdThreshold' => 67,
      ],
      'medium' => [
        'upperFirstThreshold' => 25,
        'lowerFirstThreshold' => 60,
        'upperSecondThreshold' => 25,
        'lowerSecondThresholdMin' => 50,
        'lowerSecondThresholdMax' => 60,
        'upperThirdThresholdMin' => 25,
        'upperThirdThresholdMax' => 35,
        'lowerThirdThreshold' => 62,
      ],
      'lower' => [
        'upperFirstThreshold' => 35,
        'lowerFirstThreshold' => 60,
        'upperSecondThreshold' => 35,
        'lowerSecondThresholdMin' => 50,
        'lowerSecondThresholdMax' => 60,
        'upperThirdThresholdMin' => 35,
        'upperThirdThresholdMax' => 42,
        'lowerThirdThreshold' => 58,
      ],
    ];

    // Return the thresholds for the given tier, defaulting to 'lower' if the tier is not recognized
    return $thresholds[$tier] ?? $thresholds['lower'];
  }



  public function storeTennisMatchesWithAlgoRanks($schedulesWithAlgoRanks) {
    // Save the processed games to the database
    foreach ($schedulesWithAlgoRanks as $match) {
      TennisMatchWithAlgoRank::create([
        'season' => json_encode($match['season']),
        'tournament' => json_encode($match['tournament']),
        'homeTeam' => json_encode($match['homeTeam']),
        'awayTeam' => json_encode($match['awayTeam']),
        'startTime' => $match['startTime'],
        'matchStartDate' => $match['matchStartDate'],
        'algo_rank' => $match['algo_rank'],
      ]);
    }
  }
}
