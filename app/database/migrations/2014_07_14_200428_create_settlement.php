<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Settlement\Entity as Settlement;
use Models\Ledger;
use Models\Merchant;

class CreateSettlement extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::SETTLEMENT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Settlement::ID, Settlement::ID_LENGTH)
                  ->primary();

            $table->integer(Settlement::AMOUNT)
                  ->unsigned();

            $table->string(Settlement::STATUS);

            $table->char(Settlement::MERCHANT_ID, Settlement::ID_LENGTH);

            $table->char(Settlement::LEDGER_ID, Settlement::ID_LENGTH)
                  ->unique();

            $table->integer(Settlement::TRANSACTION_AMOUNT)
                  ->unsigned();

            $table->integer(Settlement::TRANSACTION_FEES)
                  ->unsigned();

            $table->integer(Settlement::REFUND_AMOUNT)
                  ->unsigned();

            $table->integer(Settlement::REFUND_FEES)
                  ->unsigned();

            // Adds created_at and updated_at columns to the table
            $table->integer(Settlement::CREATED_AT);
            $table->integer(Settlement::UPDATED_AT);

            $table->foreign(Settlement::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->foreign(Settlement::LEDGER_ID)
                  ->references(Ledger\Entity::ID)
                  ->on(Table::LEDGER)
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
        Schema::table(Table::SETTLEMENT, function($table)
        {
            $table->dropForeign(
                TABLE::SETTLEMENT.'_'.Settlement::LEDGER_ID.'_foreign');

            $table->dropForeign(
                TABLE::SETTLEMENT.'_'.Settlement::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::SETTLEMENT);
    }

}
