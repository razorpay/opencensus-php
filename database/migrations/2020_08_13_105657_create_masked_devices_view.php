<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedDevicesView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'os_version',
            'merchant_id',
            'updated_at',
            'imei',
            'status',
            'tag',
            'verification_token',
            'challenge',
            'auth_token',
            'capability',
            'upi_token',
            'id',
            'package_name',
            'verified_at',
            'type',
            'customer_id',
            'registered_at',
            'os',
            'token_id',
            'created_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_devices_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::DEVICE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_devices_view');
    }
}
