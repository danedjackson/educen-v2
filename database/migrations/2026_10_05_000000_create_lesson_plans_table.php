<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('teacher_id');
            $table->string('title');
            $table->string('original_path');
            $table->string('original_filename');
            $table->string('revised_path')->nullable();
            $table->string('revised_filename')->nullable();
            $table->string('status')->default('pending');
            $table->ulid('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('teacher_id')->references('ulid')->on('users')->cascadeOnDelete();
            $table->foreign('completed_by')->references('ulid')->on('users')->nullOnDelete();
            $table->index(['status', 'created_at']);
            $table->index(['teacher_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
