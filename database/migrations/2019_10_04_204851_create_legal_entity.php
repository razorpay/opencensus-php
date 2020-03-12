<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\LegalEntity\Entity as LegalEntity;

class CreateLegalEntity extends Migration
{
    public function up()
    {
        Schema::create(Table::LEGAL_ENTITY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(LegalEntity::ID, LegalEntity::ID_LENGTH)
                  ->primary();

            $table->char(LegalEntity::MCC,4)
                  ->nullable();

            $table->integer(LegalEntity::BUSINESS_TYPE)
                  ->unsigned()
                  ->nullable();

            $table->string(LegalEntity::BUSINESS_CATEGORY)
                  ->nullable();

            $table->string(LegalEntity::BUSINESS_SUBCATEGORY)
                  ->nullable();

            $table->string(LegalEntity::EXTERNAL_ID)
                  ->nullable();

            $table->index(LegalEntity::EXTERNAL_ID);

            $table->integer(LegalEntity::CREATED_AT);

            $table->integer(LegalEntity::UPDATED_AT);
        });
    }

    public function down()
    {
        Schema::drop(Table::LEGAL_ENTITY);
    }
}
