<?php

namespace App\Http\Controllers;

use App\Models\ProcessedSoccerFixture;
use App\Models\SoccerFixture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use App\Services\SoccerStandingsService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SoccerController extends Controller {
    //

    public function create(Request $request) {



        $dateToday = Carbon::now('America/New_York')->toDateString();
        dd($dateToday);

        $todayProcessedFixtures = ProcessedSoccerFixture::whereDate('created_at', $dateToday)->latest()->get()->toArray();




        return Inertia::render('Soccer', [
            'soccerGames' => $todayProcessedFixtures,
        ]);
    }



    public function calculateAlgoRankingForSoccer($games) {
        $processedGames = []; // Initialize an empty array to store processed games

        foreach ($games as &$game) {

            // Decode JSON strings to arrays
            $leagueData = json_decode($game->league, true);
            $homeTeamData = json_decode($game->teams, true)['home'];
            $awayTeamData = json_decode($game->teams, true)['away'];



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


            // Add more conditions as needed

            // Ensure probabilities are within bounds [0,1]
            $homeTeamData['probability'] = max(0, min(1, $homeTeamData['probability']));
            $awayTeamData['probability'] = max(0, min(1, $awayTeamData['probability']));

            $processedGame = [
                'fixture_data' => [
                    'fixture' => json_decode($game->fixture, true),
                    'league' => json_decode($game->league, true),
                    'teams' => json_decode($game->teams, true),
                ],
                'algo_rank' => $game['algo_rank'],
                'home_probability' =>  $homeTeamData['probability'],
                'away_probability' => $awayTeamData['probability'],
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
            ]);
        }
    }
}
