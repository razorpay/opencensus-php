<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPaymentAggregationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // still there will be a key with confusing name payment_aggregations_merchant_id_foreign
        Schema::table('payment_aggregations', function(Blueprint $table)
        {
            $table->dropForeign('payment_aggregations_merchant_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_aggregations', function(Blueprint $table)
        {
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants');
        });
    }
}
