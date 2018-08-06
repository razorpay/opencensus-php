<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Tax\Entity;
use RZP\Models\Item;

class CreateTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return
     */
    public function up()
    {
        Schema::create(Table::TAX, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 512);

            $table->string(Entity::RATE_TYPE, 15);

            $table->integer(Entity::RATE)
                  ->unsigned();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->onDelete('restrict');
        });

        // This needs to be done here because migrations are run in order of
        // timestamps and taxes table gets created after items.
        Schema::table(Table::ITEM, function(Blueprint $table)
        {
            $table->foreign(Item\Entity::TAX_ID)
                  ->references(Entity::ID)
                  ->on(Table::TAX)
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return
     */
    public function down()
    {
        Schema::table(Table::TAX, function($table)
        {
            $table->dropForeign
            (
                Table::TAX . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::table(Table::ITEM, function($table)
        {
            $table->dropForeign
            (
                Table::ITEM . '_' . Item\Entity::TAX_ID . '_foreign'
            );
        });

        Schema::drop(Table::TAX);
    }
}
