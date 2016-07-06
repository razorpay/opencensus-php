<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAggregations extends Migration
{

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

            $table->char('merchant_id', 14);

            $table->integer('total_amount')
                  ->unsigned()
                  ->default(0);

            $table->integer('successful_txn_count')
                  ->unsigned()
                  ->default(0);

            $table->integer('txn_count')
                  ->unsigned()
                  ->default(1);

            $table->char('mode', 4);

            $table->string('resource');

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('merchant_id')
                  ->references('id')
                  ->on('merchants');
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
          $table->dropForeign('aggregations_merchant_id_foreign');
      });

      Schema::drop('aggregations');
    }

}
