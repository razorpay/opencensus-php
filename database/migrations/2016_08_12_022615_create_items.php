<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Item\Entity;
use RZP\Models\Item\Type;
use RZP\Models\Merchant;
use RZP\Constants\Table;

class CreateItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ITEM, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->tinyInteger(Entity::ACTIVE)
                  ->default(1);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 512);

            $table->string(Entity::DESCRIPTION, 2048)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->char(Entity::CURRENCY, 3);

            $table->char(Entity::TYPE, 16)
                  ->default(Type::INVOICE);

            $table->string(Entity::UNIT, 512)
                  ->nullable();

            $table->tinyInteger(Entity::TAX_INCLUSIVE)
                  ->default(0);

            $table->string(Entity::HSN_CODE, 20)
                  ->nullable();

            $table->string(Entity::SAC_CODE, 20)
                  ->nullable();

            $table->integer(Entity::TAX_RATE)
                  ->unsigned()
                  ->nullable();

            $table->char(Entity::TAX_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::TAX_GROUP_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::ACTIVE);
            $table->index(Entity::TYPE);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::ITEM . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::ITEM);
    }
}
