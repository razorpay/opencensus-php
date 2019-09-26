<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Merchant\Document\Entity as Document;
use RZP\Models\Merchant\Entity as Merchant;

class CreateMerchantDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_DOCUMENT, function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Document::ID, Document::ID_LENGTH)
                  ->primary();

            $table->char(Document::FILE_STORE_ID, Document::ID_LENGTH)->nullable();

            $table->char(Document::MERCHANT_ID, Document::ID_LENGTH);

            $table->string(Document::DOCUMENT_TYPE, 255);

            $table->string(Document::ENTITY_TYPE, 255);

            $table->integer(Document::CREATED_AT);

            $table->integer(Document::UPDATED_AT);

            $table->integer(Document::DELETED_AT)->nullable();

            //index
            $table->index(Document::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_DOCUMENT);
    }
}
