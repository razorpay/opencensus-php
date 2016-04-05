<?php

use Constants\Table;

use Models\Order\Entity as Order;
use Models\Payment\Entity as Payment;
use Models\Merchant\Entity as Merchant;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrders extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//
        Schema::create(Table::ORDER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Order::ID, 14)
                  ->primary();


            $table->char(Order::MERCHANT_ID, 14);

            $table->integer(Order::AMOUNT)
                  ->unsigned();

            $table->char(Order::CURRENCY, Payment::CURRENCY_LENGTH);

            $table->integer(Order::ATTEMPTS)
                  ->default(0);

            $table->string(Order::STATUS, 10);

            $table->string(Order::RECEIPT, 40);

            $table->tinyInteger(Order::AUTHORIZED)
                  ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Order::CREATED_AT);
            $table->integer(Order::UPDATED_AT);

            // Commented parts to be added incrementally

            // $table->char(Order::METHOD);//

            // $table->char(Order::ACCOUNT_ID);//

            // $table->integer(Order::CREATED_AT);

            // $table->integer(Order::VALIDITY)
            //       ->default(0);

            // $table->integer(Order::VALID_TILL);

            $table->index(Order::CREATED_AT);
            $table->index(Order::STATUS);
            $table->index(Order::RECEIPT);
            $table->index(Order::AUTHORIZED);

            // Commented parts to be added incrementally

            // $table->index(Order::METHOD);
            // $table->index(Order::ACCOUNT_ID);
            // $table->index(Order::CREATED_AT);
            // $table->index(Order::VALID_TILL);

            // Commented parts to be added incrementally

            // References Merchant Id add
            $table->foreign(Order::MERCHANT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::ORDER_ID)
                  ->references(ORDER::ID)
                  ->on(Table::ORDER)
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
        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(
                Table::PAYMENT.'_'.Payment::ORDER_ID.'_foreign');
        });

        Schema::table(Table::ORDER, function($table)
        {
            $table->dropForeign(
                Table::ORDER.'_'.Webhook::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::ORDER);
	}

}
