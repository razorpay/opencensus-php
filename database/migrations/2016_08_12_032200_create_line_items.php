<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\LineItem\Entity;
use RZP\Models\Invoice;
use RZP\Models\Merchant;
use RZP\Models\Item;
use RZP\Constants\Table;

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

            $table->char(Entity::ITEM_ID, Entity::ID_LENGTH);

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();
            $table->string(Entity::ENTITY_TYPE, 32)
                  ->nullable();

            $table->integer(Entity::QUANTITY);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::ENTITY_ID);
            $table->index(Entity::ENTITY_TYPE);

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
