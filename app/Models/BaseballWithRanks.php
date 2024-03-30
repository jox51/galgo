<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseballWithRanks extends Model {
    use HasFactory;

    protected $fillable = [
        'season',
        'tournament',
        'homeTeam',
        'awayTeam',
        'algo_rank',
        'matchStartDate',
        'startTimestamp',
    ];
}
