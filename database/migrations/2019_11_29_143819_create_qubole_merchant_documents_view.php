<?php

use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Document\Entity as MerchantDocument;

class CreateQuboleMerchantDocumentsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
           MerchantDocument::ID,
           MerchantDocument::MERCHANT_ID,
           MerchantDocument::DOCUMENT_TYPE,
           MerchantDocument::ENTITY_TYPE,
           MerchantDocument::OCR_VERIFY,
           MerchantDocument::CREATED_AT,
           MerchantDocument::UPDATED_AT,
           MerchantDocument::DELETED_AT
        ];

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_merchant_documents_view AS
                        SELECT ' . $columnStr .
            ' FROM ' . Table::MERCHANT_DOCUMENT;
        DB::statement($statement);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_merchant_documents_view');
    }
}
