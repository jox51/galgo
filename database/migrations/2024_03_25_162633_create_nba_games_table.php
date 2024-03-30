<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('nba_games', function (Blueprint $table) {
            $table->id();
            $table->dateTime('game_date');
            $table->string('home_team_name');
            $table->string('away_team_name');
            $table->string('home_team_logo');
            $table->string('away_team_logo');
            $table->string('algo_rank')->nullable();
            $table->decimal('home_team_probability', 5, 2);
            $table->decimal('away_team_probability', 5, 2);
            // Add other fields as necessary
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('nba_games');
    }
};
