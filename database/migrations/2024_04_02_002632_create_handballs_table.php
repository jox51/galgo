<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('handballs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('timestamp');
            $table->json('league');
            $table->json('teams');
            $table->string('game_date');
            $table->integer('away_rank');
            $table->integer('home_rank');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('handballs');
    }
};
