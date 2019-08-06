<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Report\Entity as Report;

class CreateQuboleReportsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Report::ID,
            Report::MERCHANT_ID,
            Report::FILE_ID,
            Report::TYPE,
            Report::GENERATED_AT,
            Report::START_TIME,
            Report::END_TIME,
            Report::DAY,
            Report::MONTH,
            Report::YEAR,
            Report::CREATED_AT,
            Report::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_reports_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::REPORT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_reports_view');
    }
}
