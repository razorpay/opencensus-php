<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPayoutLinksView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            '"*redacted*" AS contact_name',
            'idempotency_key',
            'cancelled_at',
            '"*redacted*" AS contact_email',
            'status',
            'created_at',
            '"*redacted*" AS contact_phone_number',
            'amount',
            'updated_at',
            'fund_account_id',
            'currency',
            'deleted_at',
            'balance_id',
            'notes',
            'send_sms',
            'id',
            'merchant_id',
            'purpose',
            'send_email',
            'short_url',
            'user_id',
            'description',
            'contact_id',
            'batch_id',
            'receipt'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payout_links_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYOUT_LINK;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payout_links_view');
    }
}
