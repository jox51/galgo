<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BaseballSchedule extends Model {
    use HasFactory;

    protected $fillable = [
        'season',
        'tournament',
        'homeTeam',
        'awayTeam',
        'matchStartDate',
        'startTimestamp',
    ];
}
