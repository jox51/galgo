<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('tennis_matches', function (Blueprint $table) {
            $table->id();
            $table->json('season');
            $table->json('tournament');
            $table->json('homeTeam');
            $table->json('awayTeam');
            $table->string('matchStartDate', 10)->nullable();
            $table->timestamp('startTime')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('tennis_matches');
    }
};
