<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\InternationalEnablement\Document\Entity;

class CreateInternationalEnablementDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INTERNATIONAL_ENABLEMENT_DOCUMENT, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::DOCUMENT_ID, Entity::ID_LENGTH);

            $table->char(Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID, Entity::ID_LENGTH);

            $table->string(Entity::TYPE, Entity::TYPE_FIELD_MAX_LENGTH);

            $table->string(Entity::CUSTOM_TYPE, Entity::TYPE_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::DISPLAY_NAME, Entity::DISPLAY_NAME_MAX_LENGTH)
                ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);

            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->unsignedInteger(Entity::DELETED_AT)
                ->nullable();

            // TODO: check for other indexes
            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(
                Entity::INTERNATIONAL_ENABLEMENT_DETAIL_ID, 'international_enablement_documents_ie_detail_id');

            $table->index(Entity::DOCUMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::INTERNATIONAL_ENABLEMENT_DOCUMENT);
    }
}
