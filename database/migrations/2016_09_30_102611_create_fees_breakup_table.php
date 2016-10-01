<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Pricing\FeeBreakup\Entity as FeeBreakup;
use RZP\Models\Transaction;

class CreateFeesBreakupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::FEE_BREAKUP, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(FeeBreakup::ID, FeeBreakup::ID_LENGTH)
                  ->primary();

            $table->char(FeeBreakup::TRANSACTION_ID, FeeBreakup::ID_LENGTH);

            $table->char(FeeBreakup::NAME, FeeBreakup::NAME_LENGTH);

            $table->integer(FeeBreakup::PERCENTAGE);

            $table->integer(FeeBreakup::AMOUNT);

            $table->char(FeeBreakup::TYPE, FeeBreakup::TYPE_LENGTH);

            $table->integer(FeeBreakup::CREATED_AT);

            $table->integer(FeeBreakup::UPDATED_AT);

            $table->foreign(FeeBreakup::TRANSACTION_ID)
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
        Schema::table(Table::FEE_BREAKUP, function($table)
        {
            $table->dropForeign(Table::FEE_BREAKUP .'_' .FeeBreakup::TRANSACTION_ID .'_foreign');
        });

        Schema::drop(Table::FEE_BREAKUP);
    }
}
