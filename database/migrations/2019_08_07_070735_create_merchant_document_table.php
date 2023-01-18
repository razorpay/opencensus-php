<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\Document\Source as Source;
use RZP\Models\Merchant\Document\Entity as Document;

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

            $table->char(Document::ENTITY_ID, Document::ID_LENGTH)->nullable();

            $table->char(Document::UPLOAD_BY_ADMIN_ID, Document::ID_LENGTH)->nullable();

            $table->string(Document::DOCUMENT_TYPE, 255);

            $table->string(Document::ENTITY_TYPE, 255);

            $table->enum(Document::SOURCE, [Source::API, Source::UFH])
                ->default(Source::API);

            $table->string(Document::OCR_VERIFY, 30)
                ->nullable();

            $table->char(Document::VALIDATION_ID, Document::ID_LENGTH)
                ->nullable();

            $table->unsignedInteger(Document:: DOCUMENT_DATE)
                ->nullable();

            $table->integer(Document::CREATED_AT);

            $table->integer(Document::UPDATED_AT);

            $table->integer(Document::DELETED_AT)->nullable();

            $table->string(Document::AUDIT_ID)->nullable();

            //index
            $table->index(Document::MERCHANT_ID);

            $table->index(Document::FILE_STORE_ID);

            $table->index(Document::ENTITY_ID);

            $table->json(Document::METADATA)
                ->nullable();
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
