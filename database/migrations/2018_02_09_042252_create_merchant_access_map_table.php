<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\AccessMap\Entity;
use RZP\Models\Merchant\Entity as Merchant;

class CreateMerchantAccessMapTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            Table::MERCHANT_ACCESS_MAP, function (Blueprint $table) {
                $table->engine = 'InnoDB';

                $table->char(Entity::ID, Entity::ID_LENGTH)
                    ->primary();

                $table->char(Entity::MERCHANT_ID, Merchant::ID_LENGTH);

                $table->char(Entity::ENTITY_OWNER_ID, Merchant::ID_LENGTH)
                    ->nullable();

                $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

                $table->string(Entity::ENTITY_TYPE, 255);

                $table->tinyInteger(Entity::HAS_KYC_ACCESS)
                      ->default(0);

                $table->integer(Entity::CREATED_AT);

                $table->integer(Entity::UPDATED_AT);

                $table->integer(Entity::DELETED_AT)
                    ->nullable();

                $table->foreign(Entity::MERCHANT_ID)
                    ->references(Merchant::ID)
                    ->on(Table::MERCHANT);

                $table->foreign(Entity::ENTITY_OWNER_ID)
                    ->references(Merchant::ID)
                    ->on(Table::MERCHANT);

                $table->index(Entity::CREATED_AT);

                $table->index(Entity::UPDATED_AT);

                $table->index(Entity::DELETED_AT);
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(
            Table::MERCHANT_ACCESS_MAP, function ($table) {
                $table->dropForeign(Table::MERCHANT_ACCESS_MAP.'_'.Entity::MERCHANT_ID.'_foreign');
                $table->dropForeign(Table::MERCHANT_ACCESS_MAP.'_'.Entity::ENTITY_OWNER_ID.'_foreign');
            }
        );

        Schema::drop(Table::MERCHANT_ACCESS_MAP);
    }
}
