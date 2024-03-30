<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NbaGame extends Model {
    use HasFactory;

    protected $fillable = [
        'game_date',
        'home_team_name',
        'away_team_name',
        'home_team_logo',
        'away_team_logo',
        'home_team_score',
        'away_team_score',
        'algo_rank',
        'home_team_probability',
        'away_team_probability',
    ];
}
