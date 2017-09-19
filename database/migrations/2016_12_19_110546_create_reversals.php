<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Reversal\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Constants\Table;
use RZP\Models\Transaction;
use RZP\Models\Merchant;

class CreateReversals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REVERSAL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Entity::ENTITY_ID, PublicEntity::ID_LENGTH);

            $table->char(Entity::ENTITY_TYPE, 255);

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->char(Entity::CURRENCY, 3);

            $table->text(Entity::NOTES);

            $table->char(Entity::TRANSACTION_ID, Transaction\Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->index(Entity::ENTITY_ID);

            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Entity::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
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
        Schema::table(Table::REVERSAL, function($table)
        {
            $table->dropForeign
            (
                Table::REVERSAL . '_' . Entity::TRANSACTION_ID . '_foreign'
            );

            $table->dropForeign
            (
                Table::REVERSAL . '_' . Entity::MERCHANT_ID . '_foreign'
            );
        });

        Schema::drop(Table::REVERSAL);
    }
}
