<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\CreditNote\Entity as CreditNote;

class CreateQuboleCreditnoteView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            CreditNote::ID,
            CreditNote::MERCHANT_ID,
            CreditNote::SUBSCRIPTION_ID,
            CreditNote::AMOUNT,
            CreditNote::AMOUNT_AVAILABLE,
            CreditNote::AMOUNT_REFUNDED,
            CreditNote::AMOUNT_ALLOCATED,
            CreditNote::CURRENCY,
            CreditNote::STATUS,
            CreditNote::CREATED_AT,
            CreditNote::UPDATED_AT,
            CreditNote::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_creditnote_view AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::CREDITNOTE;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_creditnote_view');
    }
}
