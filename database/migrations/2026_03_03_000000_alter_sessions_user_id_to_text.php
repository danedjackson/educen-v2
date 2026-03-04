<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change the user_id column to text/varchar so it can store ULIDs.
        // Use a raw statement to avoid requiring doctrine/dbal.
        DB::statement("ALTER TABLE sessions ALTER COLUMN user_id TYPE varchar USING user_id::text;");

        // Ensure there's an index on the column (create if not exists).
        // Use raw statement with IF NOT EXISTS to avoid duplicate-index errors.
        DB::statement('CREATE INDEX IF NOT EXISTS sessions_user_id_index ON sessions (user_id);');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Attempt to cast back to bigint; this will fail if values are non-numeric.
        DB::statement("ALTER TABLE sessions ALTER COLUMN user_id TYPE bigint USING (user_id::bigint);");

        // Remove index if exists then attempt to cast back to integer
        DB::statement('DROP INDEX IF EXISTS sessions_user_id_index;');
        Schema::table('sessions', function (Blueprint $table) {
            $table->integer('user_id')->nullable()->change();
        });
    }
};
