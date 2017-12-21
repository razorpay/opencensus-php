<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Plan\Entity;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Schedule;
use RZP\Models\Item;

class CreatePlan extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PLAN, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::ITEM_ID, Entity::ID_LENGTH);

            $table->string(Entity::PERIOD, 16);

            $table->integer(Entity::INTERVAL);

            // $table->string(Entity::NAME, 256);

            $table->text(Entity::NOTES);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

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
        Schema::table(Table::PLAN, function($table)
        {
            $table->dropForeign(Table::PLAN . '_' . Entity::MERCHANT_ID . '_foreign');

            $table->dropForeign(Table::PLAN . '_' . Entity::ITEM_ID . '_foreign');
        });

        Schema::drop(Table::PLAN);
    }
}
