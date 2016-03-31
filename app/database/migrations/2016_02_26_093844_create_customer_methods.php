<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Card;
use Models\Merchant;
use Models\Customer;
use Models\Customer\Methods\Entity as Methods;

class CreateCustomerMethods extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CUSTOMER_METHOD, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Methods::ID, 14)
                  ->primary();

            $table->char(Methods::CUSTOMER_ID, 14);

            $table->char(Methods::METHOD, 10);
  
            $table->char(Methods::CARD_ID, 14)
                  ->nullable();

            $table->char(Methods::BANK, 6)
                  ->nullable();

            $table->char(Methods::WALLET, 15)
                  ->nullable();

            $table->char(Methods::ACCOUNT_KEY)
                  ->nullable();

            $table->string(Methods::NOTES)
                  ->nullable();

            $table->integer(Methods::CREATED_AT);
            
            $table->integer(Methods::UPDATED_AT);

            $table->index(Methods::CREATED_AT);

            $table->foreign(Methods::CUSTOMER_ID)
                  ->references(Customer\Entity::ID)
                  ->on(Table::CUSTOMER)
                  ->on_delete('restrict');

            $table->foreign(Methods::CARD_ID)
                  ->references(Card\Entity::ID)
                  ->on(Table::CARD)
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
        Schema::table(Table::CUSTOMER_METHOD, function($table)
        {
            $table->dropForeign(Table::CUSTOMER_METHOD.'_'.Customer::CUSTOMER_ID.'_foreign');

            $table->dropForeign(Table::CUSTOMER_METHOD.'_'.Customer::CARD_ID.'_foreign');
        });

        Schema::drop(Table::CUSTOMER_METHOD);
    }
}