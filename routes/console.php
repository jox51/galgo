<?php

use App\Models\SoccerFixture;
use App\Services\BaseballStandingsService;
use App\Services\CleanupDatabaseService;
use App\Services\HandballStandingsService;
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
Schedule::command('nba:fetch-games')->timezone('America/New_York')->dailyAt('05:00');
Schedule::command('soccer:fetch-games')->timezone('America/New_York')->dailyAt('05:00');
Schedule::command('tennis:fetch-games')->timezone('America/New_York')->dailyAt('05:00');
Schedule::command('baseball:fetch-games')->timezone('America/New_York')->dailyAt('05:00');
Schedule::command('handball:fetch-games')->timezone('America/New_York')->dailyAt('05:00');
Schedule::command('database:clean')->weekly()->sundays()->at('05:00');


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

Artisan::command('handball:fetch-games', function (HandballStandingsService $handballStandingsService) {
    $handballStandingsService = new HandballStandingsService();
    $handballStandingsService->fetchHandballStandings();

    $this->info('Handball games data fetched and saved successfully.');
})->describe('Fetch and process Handball games data');

Artisan::command('database:clean', function (CleanupDatabaseService $cleanupDatabaseService) {
    $cleanupDatabaseService = new CleanupDatabaseService();
    $cleanupDatabaseService->deleteOldEntries();

    $this->info('Database cleaned successfully.');
})->describe('Clean up old entries from the database');
