<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAggregations extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aggregations', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->integer('merchant_id')
                  ->unsigned()
                  ->primary();

            $table->integer('total_amount')
                  ->unsigned()
                  ->default(0);

            $table->integer('successful_txn_count')
                  ->unsigned()
                  ->default(0);

            $table->integer('txn_count')
                  ->unsigned()
                  ->default(1);

            $table->integer('created_at');
            $table->integer('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('aggregations'); 
    }

}
