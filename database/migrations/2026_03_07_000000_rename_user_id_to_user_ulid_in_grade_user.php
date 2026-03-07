<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename the column from user_id to user_ulid to match Laravel's expectation
        // for many-to-many relationships when the primary key is 'ulid'
        DB::statement('ALTER TABLE grade_user RENAME COLUMN user_id TO user_ulid;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename back if needed
        DB::statement('ALTER TABLE grade_user RENAME COLUMN user_ulid TO user_id;');
    }
};
