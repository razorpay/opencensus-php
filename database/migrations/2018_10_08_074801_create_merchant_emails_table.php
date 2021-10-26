<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Email\Entity;
use RZP\Models\Merchant\Entity as Merchant;

class CreateMerchantEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_EMAIL, function (Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::TYPE, 255);

            $table->text(Entity::EMAIL)
                  ->nullable();

            $table->string(Entity::PHONE)
                  ->nullable();

            $table->string(Entity::POLICY)
                  ->nullable();

            $table->string(Entity::URL)
                  ->nullable();

            $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->tinyInteger(Entity::VERIFIED)
                  ->default(0);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->unique([Entity::MERCHANT_ID, Entity::TYPE]);

            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_EMAIL);
    }
}
