<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Transaction\FeeBreakup\Entity as FeeBreakup;

class AlterFeesBreakupTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(Table::FEE_BREAKUP, function($table)
        {
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
            $table->dropUnique([FeeBreakup::NAME, FeeBreakup::TRANSACTION_ID]);
        });
    }
}
