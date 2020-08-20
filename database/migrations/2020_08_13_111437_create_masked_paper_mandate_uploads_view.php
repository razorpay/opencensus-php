<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedPaperMandateUploadsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'not_matching',
            'ifsc_code',
            'end_date',
            'signature_present_secondary',
            'created_at',
            'id',
            'time_taken_to_process',
            'micr',
            'until_cancelled',
            'signature_present_tertiary',
            'updated_at',
            'merchant_id',
            'umrn',
            'company_name',
            'nach_type',
            '"*redacted*" AS primary_account_holder',
            'deleted_at',
            'paper_mandate_id',
            'nach_date',
            'frequency',
            'phone_number',
            'secondary_account_holder',
            'uploaded_file_id',
            'sponsor_code',
            'amount_in_number',
            'email_id',
            'tertiary_account_holder',
            'enhanced_file_id',
            'utility_code',
            'amount_in_words',
            'reference_1',
            '"*redacted*" AS account_number',
            'status',
            'bank_name',
            'debit_type',
            'reference_2',
            'form_checksum',
            'extracted_raw_data',
            'account_type',
            'start_date',
            'signature_present_primary',
            'status_reason'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_paper_mandate_uploads_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::PAPER_MANDATE_UPLOAD;

        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS masked_paper_mandate_uploads_view');
    }
}
