<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;

use RZP\Models\Payment\PaymentSupportingDocuments\Entity as Entity;

class PaymentSupportingDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::PAYMENT_SUPPORTING_DOCUMENTS, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH)->nullable(false);

            $table->char(Entity::PAYMENT_ID,  Entity::ID_LENGTH)->nullable(false);

            $table->string(Entity::DOCUMENT_TYPE, 45)->nullable();

            $table->string(Entity::DOCUMENT_NUMBER, 45)->nullable();

            $table->string(Entity::DOCUMENT_OWNER, 45)->nullable();

            $table->char(Entity::FILE_ID,  Entity::ID_LENGTH)->nullable();

            $table->integer(Entity::CREATED_AT)->nullable(false);

            $table->integer(Entity::UPDATED_AT)->nullable();

            $table->integer(Entity::DELETED_AT)->nullable();

            $table->index(Entity::PAYMENT_ID);

            $table->index(Entity::DOCUMENT_NUMBER);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENT_SUPPORTING_DOCUMENTS);
    }

}
