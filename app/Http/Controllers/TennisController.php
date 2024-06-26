<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\TennisStandingsService;
use App\Models\TennisMatchWithAlgoRank;
use Carbon\Carbon;

class TennisController extends Controller {
    //
    public function create() {


        $dateToday = Carbon::now('America/New_York')->toDateString();

        $todaysTennisMatches = TennisMatchWithAlgoRank::whereDate('startTime', $dateToday)->latest()->get()->toArray();

        return Inertia::render('Tennis', [
            'tennisGames' => $todaysTennisMatches,
        ]);
    }
}
