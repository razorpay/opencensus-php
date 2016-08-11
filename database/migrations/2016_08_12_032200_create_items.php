<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Item\Entity;
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

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);
            
            $table->string(Entity::NAME, 512);
            
            $table->string(Entity::DESCRIPTION, 2048)
                  ->nullable();

            $table->integer(Entity::AMOUNT);

            $table->char(Entity::CURRENCY, 8)
                  ->nullable();

            $table->string(Entity::LISTING_ID, 512)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::AMOUNT);
            $table->index(Entity::LISTING_ID);

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