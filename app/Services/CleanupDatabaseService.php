<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Models\BaseballSchedule;
use App\Models\BaseballWithRanks;
use App\Models\Handball;
use App\Models\HandballAlgoRank;
use App\Models\NbaGame;
use App\Models\ProcessedSoccerFixture;
use App\Models\SoccerFixture;
use App\Models\TennisMatch;
use App\Models\TennisMatchWithAlgoRank;

class CleanupDatabaseService {

  public function deleteOldEntries() {
    $oneWeekAgo = Carbon::now()->subWeek();

    // List of models to clean up
    $models = [
      BaseballSchedule::class,
      BaseballWithRanks::class,
      Handball::class,
      HandballAlgoRank::class,
      NbaGame::class,
      ProcessedSoccerFixture::class,
      SoccerFixture::class,
      TennisMatch::class,
      TennisMatchWithAlgoRank::class,
    ];

    foreach ($models as $model) {
      // Assuming each model has a 'created_at' or 'timestamp' field to check against
      // Adjust the field name as necessary for your database schema
      $model::where('created_at', '<', $oneWeekAgo)->delete();
      // If using 'timestamp' or another field, replace 'created_at' with that field name
      // For example: $model::where('timestamp', '<', $oneWeekAgo->timestamp)->delete();
    }
  }
}
