<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Vpa\Entity as Vpa;

class CreateQuboleVpasView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Vpa::ID,
            Vpa::CREATED_AT,
            Vpa::UPDATED_AT,
        ];

        $sha2_columns = [
            Vpa::USERNAME,
            Vpa::HANDLE,
        ];

        $sha2_columns = array_map(function ($v)
        {
            return "sha2({$v}, 256) as {$v}";
        },
        $sha2_columns);

        $columns = array_merge($columns, $sha2_columns);

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_vpas_view AS
                        SELECT ' . $columnStr .
                        ' FROM `' . Table::VPA . '`';

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_vpas_view');
    }
}