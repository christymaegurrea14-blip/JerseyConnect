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
        Schema::create('design_request_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_request_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('number', 3)->nullable();
            $table->string('position')->nullable();
            $table->string('size', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('design_request_players');
    }
};
