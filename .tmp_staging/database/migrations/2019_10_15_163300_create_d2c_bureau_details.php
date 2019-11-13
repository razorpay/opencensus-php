<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use \RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\D2cBureauDetail\Gender;
use RZP\Models\D2cBureauDetail\Entity;

class CreateD2cBureauDetails extends Migration
{

    public function up()
    {
        Schema::create(Table::D2C_BUREAU_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, RZP\Models\Merchant\Entity::ID_LENGTH);

            $table->char(Entity::USER_ID, RZP\Models\User\Entity::ID_LENGTH);

            $table->string(Entity::FIRST_NAME);

            $table->string(Entity::LAST_NAME)
                  ->nullable();

            $table->date(Entity::DATE_OF_BIRTH)
                  ->nullable();

            $table->enum(Entity::GENDER, [Gender::MALE, Gender::FEMALE])
                  ->nullable();

            $table->string(Entity::CONTACT_MOBILE, 15)
                  ->nullable();

            $table->string(Entity::EMAIL)
                  ->nullable();

            $table->text(Entity::ADDRESS)
                  ->nullable();

            $table->string(Entity::CITY)
                  ->nullable();

            $table->string(Entity::STATE)
                  ->nullable();

            $table->string(Entity::PINCODE)
                  ->nullable();

            $table->string(Entity::PAN, 10);

            $table->string(Entity::STATUS, 64);

            $table->integer(Entity::VERIFIED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index([Entity::ID, Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->index([Entity::MERCHANT_ID, Entity::USER_ID]);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::STATUS);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::D2C_BUREAU_DETAIL);
    }
}
