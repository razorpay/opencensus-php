<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Gateway\AxisMigs;
use Models\Base\UniqueIdEntity;

class CreateAxisGateway extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('axis', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);

            $table->boolean('genius')->default(0);

            $table->integer('vpc_Amount');
            $table->char('vpc_AcqResponseCode', 3)->nullable();
            $table->char('vpc_Command', 7);
            $table->char('vpc_Currency', 3)->nullable();
            $table->char('vpc_MerchTxnRef', 14)->nullable();
            $table->char('vpc_3DSECI', 2)->nullable();
            $table->char('vpc_3DSXID', 28)->nullable();
            $table->char('vpc_3DSenrolled',1)->nullable();
            $table->char('vpc_3DSstatus',1)->nullable();
            $table->char('vpc_AuthorizeId', 6)->nullable();
            $table->char('vpc_BatchNo', 8)->nullable();
            $table->char('vpc_Card', 2)->nullable();
            $table->char('vpc_ReceiptNo', 12)->nullable();
            $table->char('vpc_ShopTransactionNo', 19)->nullable();
            $table->char('vpc_TransactionNo', 19)->nullable();
            $table->char('vpc_TxnResponseCode', 1)->nullable();
            $table->char('vpc_VerToken', 28)->nullable();
            $table->char('vpc_VerType', 3)->nullable();
            $table->char('vpc_VerSecurityLevel', 2)->nullable();
            $table->char('vpc_VerStatus', 1)->nullable();
            $table->string('vpc_Message')->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('genius');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('axis', function($table)
        {
            $table->dropForeign('axis_payment_id_foreign');
        });

        Schema::drop('axis');
    }

}
