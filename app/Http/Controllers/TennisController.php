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


        $dateToday = Carbon::today()->toDateString();

        $todaysTennisMatches = TennisMatchWithAlgoRank::whereDate('created_at', $dateToday)->latest()->get()->toArray();

        return Inertia::render('Tennis', [
            'tennisGames' => $todaysTennisMatches,
        ]);
    }
}
