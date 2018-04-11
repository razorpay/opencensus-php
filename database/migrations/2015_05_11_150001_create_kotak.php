<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Kotak;
use RZP\Models\Base\UniqueIdEntity;

class CreateKotak extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kotak', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->char('TxnType', 2);
            $table->char('TxnRefNo', 14);
            $table->string('OrderInfo', 34)->nullable();
            $table->integer('Amount');
            $table->char('Currency', 4);
            $table->string('ResponseCode', 7)->nullable();
            $table->string('Message')->nullable();
            $table->char('CardType', 3)->nullable();
            $table->string('MaskedCardNum', 19)->nullable();
            $table->string('BatchNo', 8)->nullable();
            $table->string('RetRefNo', 12)->nullable();
            $table->string('AuthCode', 6)->nullable();
            $table->string('CaptureAmount', 12)->nullable();
            $table->string('RefundAmount', 12)->nullable();
            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('TxnType');
            $table->index('TxnRefNo');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

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
        Schema::table('kotak', function($table)
        {
            $table->dropForeign('kotak_payment_id_foreign');
        });

        Schema::drop('kotak');
    }

}
