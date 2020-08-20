<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaymentLinksView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'merchant_id',
            'status_reason',
            'udf_jsonschema_id',
            'view_type',
            'amount',
            'short_url',
            '"*redacted*" AS support_contact',
            'currency',
            'user_id',
            '"*redacted*" AS support_email',
            'expire_by',
            'receipt',
            'terms',
            'times_payable',
            'title',
            'type',
            'times_paid',
            'description',
            'created_at',
            'total_amount_paid',
            'notes',
            'updated_at',
            'id',
            'status',
            'hosted_template_id',
            'deleted_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_payment_links_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAYMENT_LINK;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_payment_links_view');
    }
}
