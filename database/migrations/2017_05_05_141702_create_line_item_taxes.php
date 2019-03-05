<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\LineItem\Tax\Entity;
use RZP\Models\Tax as TaxModel;

class CreateLineItemTaxes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return
     */
    public function up()
    {
        Schema::create(Table::LINE_ITEM_TAX, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::LINE_ITEM_ID, Entity::ID_LENGTH);

            $table->char(Entity::TAX_ID, Entity::ID_LENGTH);

            $table->string(Entity::NAME, 512);

            $table->string(Entity::RATE_TYPE, 15);

            $table->integer(Entity::RATE)
                  ->unsigned();

            $table->char(Entity::GROUP_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::GROUP_NAME, 512)
                  ->nullable();

            $table->integer(Entity::TAX_AMOUNT)
                  ->unsigned();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::LINE_ITEM_ID);
            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);
            $table->index(Entity::DELETED_AT);

            $table->foreign(Entity::TAX_ID)
                  ->references(TaxModel\Entity::ID)
                  ->on(Table::TAX)
                  ->on_delete('restrict');

            $table->foreign(Entity::GROUP_ID)
                  ->references(TaxModel\Group\Entity::ID)
                  ->on(Table::TAX_GROUP)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return
     */
    public function down()
    {
        Schema::table(Table::LINE_ITEM_TAX, function($table)
        {
            $table->dropForeign
            (
                Table::LINE_ITEM_TAX . '_' . Entity::TAX_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::LINE_ITEM_TAX . '_' . Entity::GROUP_ID . '_foreign'
            );
        });

        Schema::drop(Table::LINE_ITEM_TAX);
    }
}
