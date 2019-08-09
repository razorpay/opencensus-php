<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Paytm\Entity as Paytm;

class CreateQubolePaytmView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Paytm::ID,
            Paytm::PAYMENT_ID,
            Paytm::ACTION,
            Paytm::RECEIVED,
            Paytm::METHOD,
            Paytm::REQUEST_TYPE,
            Paytm::TXN_AMOUNT,
            Paytm::CHANNEL_ID,
            Paytm::PAYMENT_MODE_ONLY,
            Paytm::AUTH_MODE,
            Paytm::BANK_CODE,
            Paytm::PAYMENT_TYPE_ID,
            Paytm::INDUSTRY_TYPE_ID,
            Paytm::TXNID,
            Paytm::TXNAMOUNT,
            Paytm::BANKTXNID,
            Paytm::ORDERID,
            Paytm::STATUS,
            Paytm::RESPCODE,
            Paytm::RESPMSG,
            Paytm::BANKNAME,
            Paytm::PAYMENTMODE,
            Paytm::REFUNDAMOUNT,
            Paytm::GATEWAYNAME,
            Paytm::TXNDATE,
            Paytm::TXNTYPE,
            Paytm::REFUND_ID,
            Paytm::CREATED_AT,
            Paytm::UPDATED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_paytm_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYTM;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_paytm_view');
    }
}
