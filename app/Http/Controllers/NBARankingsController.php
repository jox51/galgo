<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\NBAStandingsService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\NbaGame;
use Carbon\Carbon;

class NBARankingsController extends Controller {

  protected $nbaStandingsService;

  public function __construct(NBAStandingsService $nbaStandingsService) {
    $this->nbaStandingsService = $nbaStandingsService;
  }

  public function create(Request $request) {



    // $date = $request->input('date', now()->format('Y-m-d'));
    $today = Carbon::now("America/New_York")->format('Y-m-d');


    $latestGames = NbaGame::whereDate('game_date', $today)
      ->get();

    // $latestGames = NbaGame::latest()->get();


    return Inertia::render('NBARankings', [
      'nbaGames' => $latestGames,
    ]);
  }
}
