<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\D2cBureauDetail\Entity as D2cBureauDetail;

class CreateQuboleD2CBureauDetailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            D2cBureauDetail::ID,
            D2cBureauDetail::VERIFIED_AT,
            D2cBureauDetail::STATUS,
            D2cBureauDetail::CREATED_AT,
            D2cBureauDetail::UPDATED_AT,
            D2cBureauDetail::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_d2c_bureau_details_view AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::D2C_BUREAU_DETAIL;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_d2c_bureau_details_view');
    }
}
