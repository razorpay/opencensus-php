<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMockhdfcMprGenerator extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mockhdfc_mpr_generator', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string('merchant_trackid')->primary();
            $table->string('merchant_code');
            $table->string('terminal_number');
            $table->string('rfc_fmt');
            $table->string('bat_nbr');
            $table->string('card_type');
            $table->string('card_number');
            $table->string('trans_date');
            $table->string('settle_date');
            $table->string('approv_code');
            $table->string('intl_amt');
            $table->string('domestic_amt');
            $table->string('tran_id');
            $table->string('upvalue');
            $table->string('msf');
            $table->string('service_tax');
            $table->string('edu_cess');
            $table->string('net_amount');
            $table->string('debitcredit_type');
            $table->string('udf1');
            $table->string('udf2');
            $table->string('udf3');
            $table->string('udf4');
            $table->string('udf5');
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
        Schema::drop('mockhdfc_mpr_generator');
    }
}
