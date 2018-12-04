<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Vpa\Entity;
use RZP\Models\Customer\Entity as Customer;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\BankAccount\Entity as BankAccount;

class CreateVpas extends Migration
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

            $table->char(Entity::ENTITY_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::ENTITY_TYPE, 40)
                  ->nullable();

            $table->char(Entity::USERNAME, 50);

            $table->char(Entity::HANDLE, 20);

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            //
            // $table->foreign(Entity::MERCHANT_ID)
            //       ->references(Merchant::ID)
            //       ->on(Table::MERCHANT)
            //       ->on_delete('restrict');

            $table->unique([Entity::USERNAME, Entity::HANDLE]);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table(Table::VPA, function($table)
        // {
        //     $table->dropForeign(Table::VPA . '_' . Entity::MERCHANT_ID . '_foreign');
        // });

        Schema::drop(Table::VPA);
    }
}
