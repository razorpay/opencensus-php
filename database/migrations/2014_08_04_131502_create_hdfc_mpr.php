<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHdfcMpr extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hdfc_mpr', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char('track_id', 14)
                  ->primary();

            $table->string('gateway_transaction_id');

            $table->string('gateway_merchant_id');
            $table->string('gateway_terminal_id');

            $table->string('card_trivia');
            $table->string('card_number');
            $table->string('card_type');
            $table->string('transaction_date');
            $table->string('settlement_date');

            $table->integer('international_amount')
                  ->default(0);
            $table->integer('domestic_amount')
                  ->default(0);

            $table->integer('net_amount');
            $table->integer('gateway_net_fee');
            $table->integer('gateway_fee');
            $table->integer('service_tax');
            $table->integer('education_cess');

            $table->string('rec_format');
            $table->string('batch_number');
            $table->string('upvalue');
            $table->string('sequence_number');
            $table->string('approve_code');

            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('gateway_transaction_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('hdfc_mpr');
    }

}
