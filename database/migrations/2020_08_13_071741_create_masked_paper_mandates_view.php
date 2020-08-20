<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaperMandatesView extends Migration
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
            'utility_code',
            'generated_file_id',
            'deleted_at',
            'merchant_id',
            'debit_type',
            'uploaded_file_id',
            'bank_account_id',
            'type',
            'terminal_id',
            'customer_id',
            'frequency',
            'form_checksum',
            'amount',
            'reference_1',
            'start_at',
            'status',
            'reference_2',
            'end_at',
            'umrn',
            'secondary_account_holder',
            'created_at',
            'sponsor_bank_code',
            'tertiary_account_holder',
            'updated_at'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_paper_mandates_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAPER_MANDATE;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_paper_mandates_view');
    }
}
