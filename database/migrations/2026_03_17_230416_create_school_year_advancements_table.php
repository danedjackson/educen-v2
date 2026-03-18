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
        Schema::create('school_year_advancements', function (Blueprint $table) {
            $table->id()->primary();
            $table->timestamp('advanced_at')->useCurrent();
            $table->ulid('advanced_by')->nullable();
            $table->foreign('advanced_by')->references('ulid')->on('users')->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_year_advancement');
    }
};
