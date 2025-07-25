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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('postcode');
            $table->string('address');
            $table->bigInteger('price')->unsigned();
            $table->date('completed_at');
            $table->string('type');
            $table->boolean('is_new_build');
            $table->boolean('is_freehold');

            $table->index(['postcode', 'address']);
            $table->index(['postcode', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
