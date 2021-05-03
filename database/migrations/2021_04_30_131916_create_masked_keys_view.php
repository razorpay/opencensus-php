<?php

use RZP\Constants\Table;
use RZP\Models\Key\Entity as Key;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedKeysView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Key::ID,
            Key::MERCHANT_ID,
            Key::CREATED_AT,
            Key::UPDATED_AT,
            Key::EXPIRED_AT,
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_keys_view';

        $statement = 'CREATE OR REPLACE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
            ' FROM `' . Table::KEY . '`';

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $view = DB::getConfig('view_db') . '.masked_keys_view';

        $statement = 'DROP VIEW IF EXISTS ' . $view;

        DB::statement($statement);
    }
}
