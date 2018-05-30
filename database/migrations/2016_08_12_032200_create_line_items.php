<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Item;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\LineItem\Entity;

class CreateLineItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LINE_ITEM, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::ITEM_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::NAME, 512);

            $table->string(Entity::DESCRIPTION, 2048)
                  ->nullable();

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->integer(Entity::GROSS_AMOUNT)
                  ->unsigned();

            $table->integer(Entity::TAX_AMOUNT)
                  ->unsigned();

            $table->integer(Entity::NET_AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->char(Entity::TYPE, 16)
                  ->default(Item\Type::INVOICE);

            $table->tinyInteger(Entity::TAX_INCLUSIVE)
                  ->default(0);

            $table->string(Entity::HSN_CODE, 20)
                  ->nullable();

            $table->string(Entity::SAC_CODE, 20)
                  ->nullable();

            $table->integer(Entity::TAX_RATE)
                  ->unsigned()
                  ->nullable();

            $table->string(Entity::UNIT, 512)
                  ->nullable();

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH);

            $table->string(Entity::ENTITY_TYPE, 32);

            $table->char(Entity::REF_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::REF_TYPE, 32)
                  ->nullable();

            $table->integer(Entity::QUANTITY);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);
            $table->index(Entity::ENTITY_ID);
            $table->index(Entity::ENTITY_TYPE);
            $table->index(Entity::TYPE);
            $table->index(Entity::REF_ID);
            $table->index(Entity::REF_TYPE);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT);

            $table->foreign(Entity::ITEM_ID)
                  ->references(Item\Entity::ID)
                  ->on(Table::ITEM)
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
        Schema::table(Table::LINE_ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::LINE_ITEM . '_' . Entity::MERCHANT_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::LINE_ITEM . '_' . Entity::ITEM_ID . '_foreign'
            );
        });

        Schema::drop(Table::LINE_ITEM);
    }
}
