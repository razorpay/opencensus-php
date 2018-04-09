<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Pricing\Entity as Pricing;
use RZP\Models\Transaction\Entity as Transaction;
use RZP\Models\Transaction\FeeBreakup\Entity as FeeBreakup;

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

            $table->char(FeeBreakup::NAME, FeeBreakup::NAME_LENGTH);

            $table->char(FeeBreakup::TRANSACTION_ID, FeeBreakup::ID_LENGTH);

            $table->char(FeeBreakup::PRICING_RULE_ID, FeeBreakup::ID_LENGTH)
                  ->nullable();

            $table->integer(FeeBreakup::PERCENTAGE)
                  ->nullable();

            $table->integer(FeeBreakup::AMOUNT);

            $table->integer(FeeBreakup::CREATED_AT);

            $table->integer(FeeBreakup::UPDATED_AT);

            $table->index(FeeBreakup::CREATED_AT);
            $table->index(FeeBreakup::PERCENTAGE);

            $table->foreign(FeeBreakup::TRANSACTION_ID)
                  ->references(Transaction::ID)
                  ->on(Table::TRANSACTION)
                  ->on_delete('restrict');

            $table->foreign(FeeBreakup::PRICING_RULE_ID)
                  ->references(Pricing::ID)
                  ->on(Table::PRICING)
                  ->on_delete('restrict');

            $table->unique([FeeBreakup::NAME, FeeBreakup::TRANSACTION_ID]);
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

            $table->dropForeign(Table::FEE_BREAKUP .'_' .FeeBreakup::PRICING_RULE_ID .'_foreign');

            $table->dropUnique([FeeBreakup::NAME, FeeBreakup::TRANSACTION_ID]);
        });

        Schema::drop(Table::FEE_BREAKUP);
    }
}
