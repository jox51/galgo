<?php

namespace App\Http\Controllers;

use App\Models\HandballAlgoRank;
use Illuminate\Http\Request;
use App\Services\HandballStandingsService;
use Carbon\Carbon;
use Inertia\Inertia;

class HandballController extends Controller {

    public function create(Request $request) {
        // $handballStandingsService = new HandballStandingsService();
        // $handballStandingsService->fetchHandballStandings();

        $dateToday = Carbon::now('America/New_York')->toDateString();

        $todaysHandballGames = HandballAlgoRank::whereDate('timestamp', $dateToday)->latest()->get()->toArray();

        // dd($todaysHandballGames);
        return Inertia::render('Handball', [
            'handballGames' => $todaysHandballGames,
        ]);
    }
}
