<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('processed_soccer_fixtures', function (Blueprint $table) {
            $table->id();
            $table->json('fixture_data'); // Storing the entire fixture as JSON for flexibility
            $table->string('algo_rank');
            $table->double('home_probability');
            $table->double('away_probability');
            $table->integer('home_goals')->nullable();
            $table->integer('away_goals')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('processed_soccer_fixtures');
    }
};
