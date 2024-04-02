<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('handball_algo_ranks', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->string('game_date');
            $table->json('league');
            $table->json('teams');
            $table->string('algo_rank');
            $table->double('home_probability');
            $table->double('away_probability');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('handball_algo_ranks');
    }
};
