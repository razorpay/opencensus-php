<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedFilesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'extension',
            'permission',
            'mime',
            'encryption_method',
            'id',
            'size',
            '"*redacted*" AS password',
            'merchant_id',
            'name',
            'metadata',
            'type',
            'store',
            'created_at',
            'entity_id',
            'location',
            'updated_at',
            'entity_type',
            'bucket',
            'deleted_at',
            'comments',
            'region'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_files_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::FILE_STORE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_files_view');
    }
}
