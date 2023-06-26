<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedEntityOriginsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'entity_id',
            'entity_type',
            'origin_id',
            'origin_type',
            'created_at',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_entity_origins_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ENTITY_ORIGIN;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_entity_origins_view');
    }
};
