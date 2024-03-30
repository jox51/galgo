<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NBAStandingsService;

class FetchNbaGamesData extends Command {
    protected $signature = 'nba:fetch-games';

    protected $description = 'Fetch, process, and save NBA games data';

    public function handle() {
        $service = resolve(NBAStandingsService::class); // Resolve service from the container
        $date = now()->toDateString(); // Adjust the date as necessary
        $games = $service->getGames($date);
        $standings = $service->getStandings();
        $mergedGames = $service->mergeGamesWithStandings($games['response'], $standings);
        $service->calculateAlgoRanking($mergedGames);
        $service->saveGamesToDatabase($mergedGames);

        $this->info('NBA games data fetched and saved successfully.');
    }
}
