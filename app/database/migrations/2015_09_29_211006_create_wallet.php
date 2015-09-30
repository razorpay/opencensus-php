<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Models\Base\UniqueIdEntity;

class CreateWallet extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('amount');
            $table->string('wallet');
            $table->boolean('received')->default(0);
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->string('gateway_merchant_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_payment_id_2')->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->string('response_code')->nullable();
            $table->string('response_description')->nullable();
            $table->string('status_code')->nullable();
            $table->string('error_message')->nullable();
            $table->string('reference1')->nullable();
            $table->string('date')->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();
            $table->char('caps_payment_id', UniqueIdEntity::ID_LENGTH);

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
            $table->index('caps_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }

}
