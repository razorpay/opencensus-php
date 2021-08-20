<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Paytm;
use RZP\Models\Base\UniqueIdEntity;

class CreatePaytm extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paytm', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('method');
            $table->tinyInteger('received')->default(0);
            $table->string('request_type')->nullable();
            $table->string('txn_amount');
            $table->string('cust_id');
            $table->string('channel_id');
            $table->string('payment_mode_only')->nullable();
            $table->string('auth_mode')->nullable();
            $table->string('bank_code', 10)->nullable();
            $table->string('payment_type_id', 5)->nullable();
            $table->string('industry_type_id');
            $table->string('txnid',255)->nullable();
            $table->string('txnamount')->nullable();
            $table->string('banktxnid', 30)->nullable();
            $table->string('orderid', 25)->nullable();
            $table->string('status', 15)->nullable();
            $table->string('respcode', 10)->nullable();
            $table->string('respmsg')->nullable();
            $table->string('bankname')->nullable();
            $table->string('paymentmode')->nullable();
            $table->string('refundamount')->nullable();
            $table->string('gatewayname')->nullable();
            $table->string('txndate')->nullable();
            $table->string('txntype')->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
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
        Schema::table('paytm', function($table)
        {
            $table->dropForeign('paytm_payment_id_foreign');
        });

        Schema::drop('paytm');
    }

}
