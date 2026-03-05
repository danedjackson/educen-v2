<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        // Alter model_has_roles.model_id to varchar to support ULIDs
        DB::statement("ALTER TABLE {$tableNames['model_has_roles']} ALTER COLUMN {$modelMorphKey} TYPE varchar USING {$modelMorphKey}::text;");

        // Alter model_has_permissions.model_id to varchar to support ULIDs
        DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} ALTER COLUMN {$modelMorphKey} TYPE varchar USING {$modelMorphKey}::text;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        // Attempt to cast back to bigint (will fail if non-numeric values exist)
        DB::statement("ALTER TABLE {$tableNames['model_has_roles']} ALTER COLUMN {$modelMorphKey} TYPE bigint USING ({$modelMorphKey}::bigint);");
        DB::statement("ALTER TABLE {$tableNames['model_has_permissions']} ALTER COLUMN {$modelMorphKey} TYPE bigint USING ({$modelMorphKey}::bigint);");
    }
};
