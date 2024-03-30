<?php

use App\Models\SoccerFixture;
use App\Services\BaseballStandingsService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\NBAStandingsService;
use App\Services\SoccerStandingsService;
use App\Services\TennisStandingsService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


// Define a scheduled task for fetching NBA games data
Schedule::command('nba:fetch-games')->dailyAt('17:45');
Schedule::command('soccer:fetch-games')->dailyAt('17:45');
Schedule::command('tennis:fetch-games')->dailyAt('17:45');
Schedule::command('baseball:fetch-games')->dailyAt('17:45');


// Registering a custom console command
Artisan::command('nba:fetch-games', function (NBAStandingsService $nbaStandingsService) {

    $nbaStandingsService->fetchNbaStandings();

    $this->info('NBA games data fetched and saved successfully.');
})->describe('Fetch and process NBA games data');

Artisan::command('soccer:fetch-games', function (SoccerStandingsService $soccerStandingsService) {
    $soccerStandingsService = new SoccerStandingsService();
    $soccerStandingsService->fetchSoccerStandings();

    $this->info('Soccer games data fetched and saved successfully.');
})->describe('Fetch and process Soccer games data');

Artisan::command('tennis:fetch-games', function (TennisStandingsService $soccerStandingsService) {
    $soccerStandingsService = new TennisStandingsService();
    $soccerStandingsService->getTodaysGames();

    $this->info('Tennis games data fetched and saved successfully.');
})->describe('Fetch and process Tennis games data');

Artisan::command('baseball:fetch-games', function (BaseballStandingsService $baseballStandingsService) {
    $baseballStandingsService = new BaseballStandingsService();
    $baseballStandingsService->getStandings();

    $this->info('Baseball games data fetched and saved successfully.');
})->describe('Fetch and process Baseball games data');
