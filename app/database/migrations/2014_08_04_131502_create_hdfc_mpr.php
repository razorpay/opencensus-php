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

            $table->char('transaction_id', 24)
                  ->primary();

            $table->string('gateway_transaction_id');

            $table->string('gateway_merchant_id');
            $table->string('gateway_terminal_id');

            $table->string('card_network');
            $table->string('card_number');
            $table->string('card_type');
            $table->string('capture_date');
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

            $table->string('reconciliation_format');
            $table->string('batch_number');
            $table->string('upvalue');
            $table->string('sequence_number');
            $table->string('approve_code');

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
        Schema::drop('hdfc_mpr');
    }

}
