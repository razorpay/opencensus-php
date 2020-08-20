<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedMerchantInvoiceView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'type',
            'description',
            'id',
            'amount',
            'merchant_id',
            'tax',
            'invoice_number',
            'amount_due',
            'month',
            'balance_id',
            'year',
            'created_at',
            '"*redacted*" AS gstin',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_merchant_invoice_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MERCHANT_INVOICE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_merchant_invoice_view');
    }
}
