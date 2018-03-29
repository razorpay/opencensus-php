<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Gateway\Billdesk;
use RZP\Models\Base\UniqueIdEntity;

class CreateBilldesk extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('billdesk', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->tinyInteger('received')->default(0);
            $table->string('MerchantID');
            $table->string('CustomerID');
            $table->string('TxnAmount');
            $table->string('BankID')->nullable();
            $table->string('AccountNumber', 50)->nullable();
            $table->string('CurrencyType');
            $table->string('ItemCode')->nullable();
            $table->string('TypeField1')->nullable();
            $table->string('TypeField2')->nullable();
            $table->string('AdditionalInfo1')->nullable();
            $table->string('TxnReferenceNo')->nullable();
            $table->string('BankReferenceNo')->nullable();
            $table->string('BankMerchantID')->nullable();
            $table->string('SecurityType')->nullable();
            $table->string('TxnDate')->nullable();
            $table->string('AuthStatus')->nullable();
            $table->string('SettlementType')->nullable();
            $table->string('ErrorStatus')->nullable();
            $table->string('ErrorDescription')->nullable();
            $table->string('RequestType')->nullable();
            $table->string('RefAmount')->nullable();
            $table->string('RefDateTime')->nullable();
            $table->string('RefStatus')->nullable();
            $table->string('RefundId')->nullable();
            $table->string('ErrorCode')->nullable();
            $table->string('ErrorReason')->nullable();
            $table->string('ProcessStatus')->nullable();

            $table->string('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer('created_at');
            $table->integer('updated_at');

            $table->foreign('payment_id')
                  ->references('id')
                  ->on('payments')
                  ->on_delete('restrict');

            $table->index('received');
            $table->index('AuthStatus');
            $table->index('TxnReferenceNo');
            $table->index('RefundId');
            $table->index('BankReferenceNo');
            $table->index('RefStatus');
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
        Schema::table('billdesk', function($table)
        {
            $table->dropForeign('billdesk_payment_id_foreign');
        });

        Schema::drop('billdesk');
    }

}
