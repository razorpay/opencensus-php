<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAggregationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aggregations', function(Blueprint $table)
        {
            $table->dropForeign('aggregations_merchant_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aggregations', function(Blueprint $table)
        {
            $table->foreign('merchant_id')
                ->references('id')
                ->on('merchants');
        });
    }
}
