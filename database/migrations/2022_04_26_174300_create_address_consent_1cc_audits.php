<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Address\AddressConsent1ccAudits\Entity;

class CreateAddressConsent1ccAudits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADDRESS_CONSENT_1CC_AUDITS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::CONTACT, 20)
                ->nullable();
            $table->string(Entity::UNIQUE_ID, 36)
                  ->nullable();

            $table->integer(Entity::DELETED_AT)
                  ->nullable();
            $table->integer(Entity::CREATED_AT);

            $table->index(Entity::CONTACT);
            $table->index(Entity::UNIQUE_ID);
            $table->index(Entity::DELETED_AT);
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
        Schema::drop(Table::ADDRESS_CONSENT_1CC_AUDITS);
    }
}
