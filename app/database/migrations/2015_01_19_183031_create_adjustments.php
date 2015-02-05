<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Adjustment\Entity as Adjustment;
use Models\Merchant;
use Models\Transaction;

class CreateAdjustments extends Migration {

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

            $table->integer(Adjustment::AMOUNT);

            $table->char(Adjustment::CURRENCY, 3);

            $table->string(Adjustment::CHANNEL);

            $table->string(Adjustment::DESCRIPTION);

            $table->char(Adjustment::TRANSACTION_ID, Adjustment::ID_LENGTH)
                  ->nullable();

            $table->integer(Adjustment::CREATED_AT);
            $table->integer(Adjustment::UPDATED_AT);

            $table->foreign(Adjustment::TRANSACTION_ID)
                  ->references(Transaction\Entity::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->index(Adjustment::CHANNEL);
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
            $table->dropForeign(Table::ADJUSTMENT.'_'.Adjustment::TRANSACTION_ID.'_foreign');
        });

        Schema::drop(Table::ADJUSTMENT);
    }
}
