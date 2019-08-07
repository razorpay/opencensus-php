<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Gateway\Enach\Base\Entity as Enach;

class CreateQuboleEnachView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Enach::ID,
            Enach::PAYMENT_ID,
            Enach::REFUND_ID,
            Enach::AUTHENTICATION_GATEWAY,
            Enach::ACTION,
            Enach::ACQUIRER,
            Enach::BANK,
            Enach::AMOUNT,
            Enach::STATUS,
            Enach::UMRN,
            Enach::GATEWAY_REFERENCE_ID,
            Enach::GATEWAY_REFERENCE_ID2,
            Enach::ACKNOWLEDGE_STATUS,
            Enach::REGISTRATION_STATUS,
            Enach::REGISTRATION_DATE,
            Enach::ERROR_MESSAGE,
            Enach::ERROR_CODE,
            Enach::CREATED_AT,
            Enach::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_enach_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ENACH;

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_enach_view');
    }
}
