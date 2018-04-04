<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEMandateToPaymentAggregations extends Migration
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
            $table->integer('EMANDATE')
                  ->unsigned()
                  ->default(0);

            $table->integer('AEPS')
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
            $table->dropColumn('EMANDATE');
            $table->dropColumn('AEPS');
        });
    }
}
