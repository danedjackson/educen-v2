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
        Schema::table('scores', function (Blueprint $table) {
            $table->ulid('assignment_type_id')->nullable()->after('description');
            $table->datetime('date_administered')->nullable()->after('assignment_type_id');

            $table->foreign('assignment_type_id')->references('id')->on('assignment_types')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropForeign(['assignment_type_id']);
            $table->dropColumn('assignment_type_id');
            $table->dropColumn('date_administered');
        });
    }
};
