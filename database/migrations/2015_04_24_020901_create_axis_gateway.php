<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

use RZP\Gateway\AxisMigs;
use RZP\Models\Base\UniqueIdEntity;

class CreateAxisGateway extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MIGS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action', 10);
            $table->tinyInteger('received')->default(0);
            $table->tinyInteger('genius')->default(0);
            $table->tinyInteger('amex')->default(0);

            $table->integer('vpc_Amount');
            $table->string('vpc_AcqResponseCode', 4)->nullable();
            $table->integer('vpc_AuthorisedAmount')->nullable();
            $table->integer('vpc_CapturedAmount')->nullable();
            $table->char('vpc_Command', 20);
            $table->char('vpc_Currency', 3)->nullable();
            $table->char('vpc_MerchTxnRef', 14)->nullable();
            $table->char('vpc_3DSECI', 2)->nullable();
            $table->string('vpc_3DSXID', 28)->nullable();
            $table->char('vpc_3DSenrolled',1)->nullable();
            $table->char('vpc_3DSstatus',1)->nullable();
            $table->string('vpc_AuthorizeId', 6)->nullable();
            $table->string('vpc_BatchNo', 8)->nullable();
            $table->char('vpc_Card', 2)->nullable();
            $table->string('vpc_ReceiptNo', 12)->nullable();
            $table->integer('vpc_RefundedAmount')->nullable();
            $table->string('vpc_ShopTransactionNo', 19)->nullable();
            $table->string('vpc_TransactionNo', 19)->nullable();
            $table->string('vpc_TxnResponseCode')->nullable();
            $table->string('vpc_VerToken', 28)->nullable();
            $table->char('vpc_VerType', 3)->nullable();
            $table->char('vpc_VerSecurityLevel', 2)->nullable();
            $table->char('vpc_VerStatus', 1)->nullable();
            $table->string('vpc_Message')->nullable();
            $table->string('vpc_CSCResultCode', 20)->nullable();
            $table->string('vpc_AcqCSCRespCode', 20)->nullable();

            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();
            $table->char('arn', 40)->nullable();

            $table->string('terminal_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->index('vpc_TransactionNo');
            $table->index('vpc_ShopTransactionNo');
            $table->index('vpc_TxnResponseCode');
            $table->index('genius');
            $table->index('received');
            $table->index('amex');
            $table->index('vpc_ReceiptNo');
            $table->index('vpc_3DSstatus');
            $table->index('vpc_Command');
            $table->index('vpc_MerchTxnRef');
            $table->index('refund_id');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->foreign('terminal_id')
                  ->references('id')
                  ->on('terminals')
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
        Schema::table('axis', function($table)
        {
            $table->dropForeign('axis_payment_id_foreign');
            $table->dropForeign('axis_terminal_id_foreign');
        });

        Schema::drop('axis');
    }

}
