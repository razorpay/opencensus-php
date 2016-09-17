<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Customer\Entity as Customer;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;

class CreateCustomers extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CUSTOMER, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Customer::ID, 14)
                  ->primary();

            $table->char(Customer::MERCHANT_ID, 14);

            // $table->char(Customer::SHIPPING_ADDRESS_ID, Customer::ID_LENGTH)
            //       ->nullable();

            $table->string(Customer::NAME, 50)
                  ->nullable();

            $table->string(Customer::CONTACT, 20)
                  ->nullable();

            $table->string(Customer::EMAIL, 255)
                  ->nullable();

            $table->text(Customer::NOTES);

            $table->boolean(Customer::ACTIVE)
                  ->default(1);

            $table->integer(Customer::CREATED_AT);
            $table->integer(Customer::UPDATED_AT);
            $table->integer(Customer::DELETED_AT)
                  ->nullable();

            $table->index(Customer::CONTACT);
            $table->index(Customer::CREATED_AT);

            $table->foreign(Customer::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->foreign(Payment::CUSTOMER_ID)
                  ->references(Customer::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');
        });

        Schema::table(Table::ORDER, function($table)
        {
            $table->foreign(Order::CUSTOMER_ID)
                  ->references(Customer::ID)
                  ->on(Table::CUSTOMER)
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
        Schema::table(Table::ORDER, function($table)
        {
            $table->dropForeign(Table::ORDER.'_'.Order::CUSTOMER_ID.'_foreign');

        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT.'_'.Payment::CUSTOMER_ID.'_foreign');

        });

        Schema::table(Table::CUSTOMER, function($table)
        {
            $table->dropForeign(Table::CUSTOMER.'_'.Customer::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::CUSTOMER);
    }

}
