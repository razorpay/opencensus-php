<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Isg\Entity as ISG;

class CreateQuboleIsgView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            ISG::ID,
            ISG::PAYMENT_ID,
            ISG::REFUND_ID,
            ISG::ACTION,
            ISG::RECEIVED,
            ISG::MERCHANT_REFERENCE,
            ISG::SECONDARY_ID,
            ISG::BANK_REFERENCE_NUMBER,
            ISG::TRANSACTION_DATE_TIME,
            ISG::AMOUNT,
            ISG::AUTH_CODE,
            ISG::RRN,
            ISG::TIP_AMOUNT,
            ISG::STATUS_CODE,
            ISG::STATUS_DESC,
            ISG::CREATED_AT,
            ISG::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_isg_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::ISG;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_isg_view');
    }
}
