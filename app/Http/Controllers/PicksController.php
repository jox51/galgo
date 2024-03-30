<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\NbaGame;

class PicksController extends Controller {


  public function create(Request $request) {
    $date = $request->input('date', now()->format('Y-m-d'));
    $games = NbaGame::whereDate('game_date', $date)
      ->get();



    return Inertia::render('Picks', [
      'games' => $games,
    ]);
  }
}
