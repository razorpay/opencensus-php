<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Upi\Vpa\Entity;
use RZP\Models\Customer\Entity as Customer;
use RZP\Models\BankAccount\Entity as BankAccount;

class CreateVpa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::VPA, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::USERNAME, 50);

            $table->char(Entity::HANDLE, 20);

            $table->char(Entity::FREQUENCY, 10);

            $table->char(Entity::BANK_ACCOUNT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CUSTOMER_ID);

            $table->foreign(Entity::BANK_ACCOUNT_ID)
                  ->references(BankAccount::ID)
                  ->on(Table::BANK_ACCOUNT)
                  ->on_delete('restrict');

            $table->foreign(Entity::CUSTOMER_ID)
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
        Schema::table(Table::VPA, function($table)
        {
            $table->dropForeign(Table::DEVICE . '_' . Entity::CUSTOMER_ID . '_foreign');

            $table->dropForeign(Table::DEVICE . '_' . Entity::BANK_ACCOUNT_ID . '_foreign');
        });

        Schema::drop(Table::VPA);
    }
}
