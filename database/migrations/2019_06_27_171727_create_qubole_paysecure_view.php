<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Gateway\Paysecure\Entity as Paysecure;



class CreateQubolePaysecureView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        $columns = [
            Paysecure::ID,
            Paysecure::PAYMENT_ID,
            Paysecure::ACTION,
            Paysecure::RECEIVED,
            Paysecure::REFUND_ID,
            Paysecure::STATUS,
            Paysecure::GATEWAY_TRANSACTION_ID,
            Paysecure::ERROR_CODE,
            Paysecure::ERROR_MESSAGE,
            Paysecure::RRN,
            Paysecure::TRAN_DATE,
            Paysecure::TRAN_TIME,
            Paysecure::FLOW,
            Paysecure::AUTH_NOT_REQUIRED,
            Paysecure::APPRCODE,
            Paysecure::CREATED_AT,
            Paysecure::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_paysecure_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYSECURE;

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_paysecure_view');
    }
}
