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
        Schema::table('students', function (Blueprint $table) {
            $table->ulid('grade_id')->nullable()->after('id');
            $table->foreign('grade_id')->references('id')->on('grades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // 1. Drop the foreign key constraint
            // By convention, Laravel names this: table_column_foreign
            $table->dropForeign(['grade_id']);

            // 2. Drop the column
            $table->dropColumn('grade_id');
        });
    }
};
