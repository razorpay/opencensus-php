<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedD2cBureauReportsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'user_id',
            'updated_at',
            'd2c_bureau_detail_id',
            'deleted_at',
            'provider',
            'csv_report_ufh_file_id',
            'score',
            'error_code',
            'report',
            'ntc_score',
            'ufh_file_id',
            'id',
            'interested',
            'merchant_id',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_d2c_bureau_reports_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_d2c_bureau_reports_view');
    }
}
