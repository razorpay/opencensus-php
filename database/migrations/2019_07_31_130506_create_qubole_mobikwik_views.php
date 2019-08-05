<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Mobikwik\Entity as Mobikwik;

class CreateQuboleMobikwikViews extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            Mobikwik::ID,
            Mobikwik::PAYMENT_ID,
            Mobikwik::ACTION,
            Mobikwik::METHOD,
            Mobikwik::RECEIVED,
            Mobikwik::AMOUNT,
            Mobikwik::ORDERID,
            Mobikwik::TXID,
            Mobikwik::MID,
            Mobikwik::MERCHANTNAME,
            Mobikwik::SHOWMOBILE,
            Mobikwik::STATUSCODE,
            Mobikwik::STATUSMSG,
            Mobikwik::REFID,
            Mobikwik::ISPARTIAL,
            Mobikwik::REFUND_ID,
            Mobikwik::CREATED_AT,
            Mobikwik::UPDATED_AT
        ];
        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_mobikwik_view AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MOBIKWIK;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_mobikwik_view');
    }
}
