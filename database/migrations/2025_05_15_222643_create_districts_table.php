<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('area_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->float('latitude');
            $table->float('longitude');
            $table->integer('five_year_count')->unsigned()->nullable();
            $table->integer('five_year_average')->unsigned()->nullable();
            $table->integer('five_year_standard_deviation')->unsigned()->nullable();

            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
