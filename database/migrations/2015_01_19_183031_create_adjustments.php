<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Models\Adjustment\Entity as Adjustment;
use RZP\Models\Merchant\Balance\Entity as Balance;

class CreateAdjustments extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADJUSTMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Adjustment::ID, Adjustment::ID_LENGTH)
                  ->primary();

            $table->char(Adjustment::MERCHANT_ID, Adjustment::ID_LENGTH);

            $table->string(Adjustment::ENTITY_TYPE, 100)
                ->nullable();

            $table->char(Adjustment::ENTITY_ID, Adjustment::ID_LENGTH)
                ->nullable();

            $table->integer(Adjustment::AMOUNT);

            $table->char(Adjustment::CURRENCY, 3);

            $table->string(Adjustment::CHANNEL);

            $table->string(Adjustment::DESCRIPTION);

            $table->char(Adjustment::TRANSACTION_ID, Adjustment::ID_LENGTH)
                  ->nullable();

            $table->char(Adjustment::BALANCE_ID, Balance::ID_LENGTH)
                  ->nullable();

            $table->char(Adjustment::SETTLEMENT_ID, Adjustment::ID_LENGTH)
                  ->nullable();

            $table->integer(Adjustment::CREATED_AT);
            $table->integer(Adjustment::UPDATED_AT);

            $table->string(Adjustment::STATUS, Adjustment::ID_LENGTH)
                  ->nullable();

            $table->foreign(Adjustment::SETTLEMENT_ID)
                  ->references(Settlement\Entity::ID)
                  ->on(Table::SETTLEMENT)
                  ->on_delete('restrict');

            $table->foreign(Adjustment::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(Adjustment::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(Adjustment::CHANNEL);
            $table->index(Adjustment::BALANCE_ID);
            $table->index(Adjustment::ENTITY_ID);
            $table->index(Adjustment::ENTITY_TYPE);
            $table->index(Adjustment::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::ADJUSTMENT, function($table)
        {
            $table->dropForeign(Table::ADJUSTMENT.'_'.Adjustment::MERCHANT_ID.'_foreign');

            $table->dropForeign(Table::ADJUSTMENT.'_'.Adjustment::TRANSACTION_ID.'_foreign');

            $table->dropForeign(Table::ADJUSTMENT.'_'.Adjustment::SETTLEMENT_ID.'_foreign');
        });

        Schema::drop(Table::ADJUSTMENT);
    }
}
