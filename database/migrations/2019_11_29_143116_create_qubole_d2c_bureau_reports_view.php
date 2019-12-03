<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\D2cBureauReport\Entity as D2cBureauReport;

class CreateQuboleD2cBureauReportsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            D2cBureauReport::ID,
            D2cBureauReport::MERCHANT_ID,
            D2cBureauReport::USER_ID,
            D2cBureauReport::D2C_BUREAU_DETAIL_ID,
            D2cBureauReport::PROVIDER,
            D2cBureauReport::SCORE,
            D2cBureauReport::INTERESTED,
            D2cBureauReport::CREATED_AT,
            D2cBureauReport::UPDATED_AT,
            D2cBureauReport::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_d2c_bureau_reports_view AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::D2C_BUREAU_REPORT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_d2c_bureau_reports_view');
    }
}
