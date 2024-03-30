<?php

namespace App\Http\Controllers;

use App\Models\BaseballWithRanks;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\BaseballStandingsService;
use Carbon\Carbon;

class BaseballController extends Controller {

    public function create() {

        $today = Carbon::now("America/New_York");

        // $baseballStandingsService = new BaseballStandingsService();
        // $baseballStandingsService->getStandings();

        $baseballStandings = BaseballWithRanks::whereDate('startTimestamp', $today)->get()->toArray();



        return Inertia::render('Baseball', [
            'baseballGames' => $baseballStandings,
        ]);
    }
}
