<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUpiToPaymentAggregations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_aggregations', function($table)
        {
            $table->integer('UPI')
                  ->unsigned()
                  ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_aggregations', function($table)
        {
            $table->dropColumn('UPI');
        });
    }
}
