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
        Schema::create('grades', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // Ensure the column is NOT NULL before setting it as a primary key
            $table->ulid('ulid')->nullable(false)->change(); 
            $table->primary('ulid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');

        Schema::table('users', function (Blueprint $table) {
            $table->dropPrimary('users_ulid_primary'); // Use the conventional index name
        });
    }
};
