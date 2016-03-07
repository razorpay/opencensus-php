<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Merchant;
use Models\Customer\Entity as Customer;

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

            $table->char(Customer::NAME, 50)
                  ->nullable();

            $table->char(Customer::EMAIL, 255);
            
            $table->char(Customer::CONTACT, 15);

            $table->integer(Customer::CREATED_AT);
            $table->integer(Customer::UPDATED_AT);

            $table->index(Customer::EMAIL);

            $table->index(Customer::CREATED_AT);

            $table->foreign(Customer::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
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
        Schema::table(Table::CUSTOMER, function($table)
        {
            $table->dropForeign(Table::CUSTOMER.'_'.Customer::MERCHANT_ID.'_foreign');
        });

        Schema::drop(Table::CUSTOMER);
    }

}
