<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TennisMatchWithAlgoRank extends Model {
    use HasFactory;

    protected $fillable = [
        'season',
        'tournament',
        'homeTeam',
        'awayTeam',
        'algo_rank',
        'startTime',
        'matchStartDate',
    ];
}
