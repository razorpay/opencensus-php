<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // still there will be a key with confusing name transactions_merchant_id_foreign
        Schema::table('transactions', function(Blueprint $table)
        {
            $table->dropForeign('transactions_merchant_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transactions', function(Blueprint $table)
        {
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants');
        });
    }
}
