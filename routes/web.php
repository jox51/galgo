<?php

use App\Http\Controllers\BaseballController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\NBARankingsController;
use App\Http\Controllers\PicksController;
use App\Http\Controllers\SoccerController;
use App\Http\Controllers\TennisController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/picks', [PicksController::class, 'create'])->middleware('auth')->name('picks');
Route::get('/nba', [NBARankingsController::class, 'create'])->middleware('auth')->name('nba');
Route::get('/soccer', [SoccerController::class, 'create'])->middleware('auth')->name('soccer');
Route::get('/tennis', [TennisController::class, 'create'])->middleware('auth')->name('tennis');
Route::get('/baseball', [BaseballController::class, 'create'])->middleware('auth')->name('tennis');

require __DIR__ . '/auth.php';
