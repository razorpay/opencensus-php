<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedD2cBureauDetailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS last_name',
            '"*redacted*" AS pincode',
            '"*redacted*" AS date_of_birth',
            '"*redacted*" AS pan',
            'gender',
            'status',
            '"*redacted*" AS contact_mobile',
            'verified_at',
            'id',
            '"*redacted*" AS email',
            'created_at',
            'merchant_id',
            '"*redacted*" AS address',
            'updated_at',
            'user_id',
            'city',
            'deleted_at',
            '"*redacted*" AS first_name',
            'state'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_d2c_bureau_details_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_d2c_bureau_details_view');
    }
}
