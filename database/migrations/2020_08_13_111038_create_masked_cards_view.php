<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedCardsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'id',
            'merchant_id',
            'last4',
            'emi',
            'reference2',
            'length',
            'international',
            'reference3',
            'network',
            'reference4',
            'type',
            '"*redacted*" AS token',
            'trivia',
            '"*redacted*" AS name',
            'sub_type',
            'vault',
            'country',
            'expiry_month',
            'category',
            '"*redacted*" AS vault_token',
            'global_card_id',
            'expiry_year',
            'issuer',
            'global_fingerprint',
            'iin',
            'reference1',
            'created_at',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_cards_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::CARD;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_cards_view');
    }
}
