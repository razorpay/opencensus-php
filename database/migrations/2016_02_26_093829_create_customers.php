<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Order\Entity as Order;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Customer\Entity as Customer;

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

            $table->string(Customer::NAME, 50)
                  ->nullable();

            $table->string(Customer::CONTACT, 20)
                  ->nullable();

            $table->string(Customer::EMAIL, 255)
                  ->nullable();

            $table->string(Customer::GSTIN, 20)
                  ->nullable();

            $table->text(Customer::NOTES);

            $table->tinyInteger(Customer::ACTIVE)
                  ->default(1);

            $table->string(Customer::GLOBAL_CUSTOMER_ID, Customer::ID_LENGTH)
                  ->nullable();

            $table->integer(Customer::CREATED_AT);
            $table->integer(Customer::UPDATED_AT);
            $table->integer(Customer::DELETED_AT)
                  ->nullable();

            //
            // Currently laravel does not provide a way to use indexes with
            // a prefix length in migrations. Actual index length for email
            // column is 40. Ref https://github.com/laravel/framework/issues/9293
            //
            $table->index(Customer::EMAIL);
            $table->index([Customer::CONTACT, Customer::EMAIL, Customer::MERCHANT_ID]);
            $table->index([Customer::CONTACT, Customer::MERCHANT_ID]);
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
            $table->dropForeign(Table::ORDER . '_' . Order::CUSTOMER_ID . '_foreign');

        });

        Schema::table(Table::PAYMENT, function($table)
        {
            $table->dropForeign(Table::PAYMENT . '_' . Payment::CUSTOMER_ID . '_foreign');

        });

        Schema::table(Table::CUSTOMER, function($table)
        {
            $table->dropForeign(Table::CUSTOMER . '_' . Customer::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::CUSTOMER);
    }
}
