<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Base\UniqueIdEntity;

class CreateSbiepay extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('sbiepay', function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments('id');

            $table->char('payment_id', UniqueIdEntity::ID_LENGTH);
            $table->string('action');
            $table->string('method');
            $table->tinyInteger('received')->default(0);

            $table->string('MerchantId', 20);
            $table->string('OperatingMode', 10)->nullable();
            $table->string('MerchantCountry', 10)->nullable();
            $table->string('MerchantCurrency', 10)->nullable();
            $table->string('PostingAmount', 19)->nullable();
            $table->string('OtherDetails', 1000)->nullable();
            $table->string('AggregatorId', 10);
            $table->string('MerchantOrderNo', 100);
            $table->string('MerchantCustomerID', 100)->nullable();
            $table->string('Paymode', 10)->nullable();
            $table->string('Accesmedium', 10)->nullable();
            $table->string('TransactionSource', 10)->nullable();

            $table->string('SBIePayReferenceID', 20)->nullable();
            $table->string('Status', 20)->nullable();
            $table->string('Reason', 200)->nullable();
            $table->string('BankCode', 10)->nullable();
            $table->string('BankReferenceNumber', 40)->nullable();
            $table->dateTime('TransactionDate')->nullable();
            $table->string('CIN',20)->nullable();


            $table->char('refund_id', UniqueIdEntity::ID_LENGTH)->nullable();
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
        Schema::table('sbiepay', function($table)
        {
            $table->dropForeign('sbiepay_payment_id_foreign');
        });

        Schema::drop('sbiepay');
	}

}
