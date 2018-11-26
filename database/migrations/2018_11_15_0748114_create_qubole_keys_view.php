<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Key\Entity as Key;

class CreateQuboleKeysView extends Migration
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

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_keys_view AS
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
        DB::statement('DROP VIEW IF EXISTS qubole_keys_view');
    }
}