<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\MerchantApplications\Entity;
use RZP\Models\Merchant\Entity as Merchant;

class CreateMerchantApplications extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_APPLICATION, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->string(Entity::TYPE, 255);

            $table->char(Entity::APPLICATION_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->integer(Entity::DELETED_AT)
                ->nullable();

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::APPLICATION_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::DELETED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_APPLICATION);
    }
}
