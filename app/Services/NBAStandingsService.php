<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\NbaGame;

class NBAStandingsService {
  protected $apiKey = '1240f4bf06msh22e9d539d535101p1b1ff3jsn337bd1967b6d';
  protected $apiHost = 'api-basketball.p.rapidapi.com';

  public function fetchNbaStandings() {
    $date = now()->format('Y-m-d');
    $games = $this->getGames($date);
    $standings = $this->getStandings();
    $mergedGames = $this->mergeGamesWithStandings($games['response'], $standings);
    $gamesWithAlgo = $this->calculateAlgoRanking($mergedGames);
    $this->saveGamesToDatabase($gamesWithAlgo);
  }

  public function getGames($date) {
    $response = Http::withHeaders([
      'X-RapidAPI-Key' => $this->apiKey,
      'X-RapidAPI-Host' => $this->apiHost
    ])->get('https://api-basketball.p.rapidapi.com/games', [
      'season' => '2023-2024',
      'timezone' => 'America/New_York', // Example timezone
      'league' => '12',
      'date' => $date
    ]);

    return $response->json();
  }

  public function getStandings() {
    $groups = ['western conference', 'eastern conference'];
    $allStandings = [];

    foreach ($groups as $group) {
      $response = Http::withHeaders([
        'X-RapidAPI-Key' => $this->apiKey,
        'X-RapidAPI-Host' => $this->apiHost
      ])->get('https://api-basketball.p.rapidapi.com/standings', [
        'season' => '2023-2024',
        'league' => '12',
        'group' => $group
      ]);

      $standings = $response->json();
      $allStandings = array_merge($allStandings, $standings['response'][0]);
    }

    return $allStandings;
  }

  public function mergeGamesWithStandings($games, $standings) {
    $leagueStandings = $this->calculateLeagueStandings($standings);

    foreach ($games as &$game) {
      foreach ($standings as $standing) {
        if ($game['teams']['home']['id'] === $standing['team']['id']) {
          $game['teams']['home']['standing'] = $standing['position'];
          $game['teams']['home']['win_percentage'] = $standing['games']['win']['percentage'];
          // Find and add league standing
          $leagueStanding = $this->findLeagueStanding($leagueStandings, $standing['team']['id']);
          $game['teams']['home']['league_standing'] = $leagueStanding;
        }

        if ($game['teams']['away']['id'] === $standing['team']['id']) {
          $game['teams']['away']['standing'] = $standing['position'];
          $game['teams']['away']['win_percentage'] = $standing['games']['win']['percentage'];
          // Find and add league standing
          $leagueStanding = $this->findLeagueStanding($leagueStandings, $standing['team']['id']);
          $game['teams']['away']['league_standing'] = $leagueStanding;
        }
      }
    }

    return $games;
  }


  public function calculateLeagueStandings($standings) {
    // Aggregate all teams with their win percentages
    $allTeams = [];
    foreach ($standings as $standing) {
      $allTeams[] = [
        'id' => $standing['team']['id'],
        'win_percentage' => floatval($standing['games']['win']['percentage']),
      ];
    }

    // Sort teams by win percentage
    usort($allTeams, function ($teamA, $teamB) {
      return $teamB['win_percentage'] <=> $teamA['win_percentage'];
    });

    // Assign league standing based on sorted position
    foreach ($allTeams as $index => &$team) {
      $team['league_standing'] = $index + 1;
    }

    return $allTeams;
  }

  public function findLeagueStanding($leagueStandings, $teamId) {
    foreach ($leagueStandings as $standing) {
      if ($standing['id'] == $teamId) {
        return $standing['league_standing'];
      }
    }
    return null; // Or appropriate fallback
  }

  public function calculateAlgoRanking($games) {
    foreach ($games as &$game) {
      $homeLeagueStanding = $game['teams']['home']['league_standing'];
      $awayLeagueStanding = $game['teams']['away']['league_standing'];

      // Initialize default algo_rank and probabilities
      $game['algo_rank'] = $game['algo_rank'] ?? 'h'; // Placeholder for conditions not explicitly covered
      $game['teams']['home']['probability'] = 0.5;
      $game['teams']['away']['probability'] = 0.5;

      if ($homeLeagueStanding <= 5 && $awayLeagueStanding >= 20) {
        $game['algo_rank'] = 'a';
        $game['teams']['home']['probability'] = 0.95;
        $game['teams']['away']['probability'] = 0.05;
      } else if ($awayLeagueStanding <= 5 && $homeLeagueStanding >= 20) {
        $game['algo_rank'] = 'b';
        $game['teams']['home']['probability'] = 0.10;
        $game['teams']['away']['probability'] = 0.90;
      } else if ($homeLeagueStanding <= 5 && $awayLeagueStanding >= 15 && $awayLeagueStanding < 20) {
        $game['algo_rank'] = 'c';
        $game['teams']['home']['probability'] = 0.85;
        $game['teams']['away']['probability'] = 0.15;
      } else if ($awayLeagueStanding <= 5 && $homeLeagueStanding >= 15 && $homeLeagueStanding < 20) {
        $game['algo_rank'] = 'd';
        $game['teams']['home']['probability'] = 0.20;
        $game['teams']['away']['probability'] = 0.80;
      } else if ($homeLeagueStanding > 5 && $homeLeagueStanding <= 10 && $awayLeagueStanding >= 20) {
        $game['algo_rank'] = 'e';
        $game['teams']['home']['probability'] = 0.75;
        $game['teams']['away']['probability'] = 0.25;
      } else if ($awayLeagueStanding > 5 && $awayLeagueStanding <= 10 && $homeLeagueStanding >= 20) {
        $game['algo_rank'] = 'f';
        $game['teams']['home']['probability'] = 0.35;
        $game['teams']['away']['probability'] = 0.65;
      } else {
        // Additional or default logic for setting probabilities based on other factors can go here
        // This part of the code adjusts probabilities based on rankings if none of the above conditions are met
        if ($homeLeagueStanding < $awayLeagueStanding) {
          $game['teams']['home']['probability'] += 0.05;
          $game['teams']['away']['probability'] -= 0.05;
        } else {
          $game['teams']['home']['probability'] -= 0.03;
          $game['teams']['away']['probability'] += 0.03;
        }
      }

      // Ensure probabilities are within bounds [0,1]
      $game['teams']['home']['probability'] = max(0, min(1, $game['teams']['home']['probability']));
      $game['teams']['away']['probability'] = max(0, min(1, $game['teams']['away']['probability']));
    }

    return $games;
  }

  public function saveGamesToDatabase($games) {
    // Check the structure of the games array before saving to the database


    foreach ($games as $game) {

      NbaGame::create([
        'game_date' => $game['date'],
        'home_team_name' => $game['teams']['home']['name'],
        'away_team_name' => $game['teams']['away']['name'],
        'home_team_logo' => $game['teams']['home']['logo'],
        'away_team_logo' => $game['teams']['away']['logo'],
        'algo_rank' => $game['algo_rank'],
        'home_team_probability' => $game['teams']['home']['probability'] * 100, // Convert to percentage
        'away_team_probability' => $game['teams']['away']['probability'] * 100, // Convert to percentage
        // Map other fields as needed
      ]);
    }
  }
}
