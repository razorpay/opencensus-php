<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Customer\Address\Entity;
use RZP\Models\Customer;

class CreateAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::ADDRESS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            //$table->char(Entity::CUSTOMER_ID, Entity::ID_LENGTH);

            $table->string(Entity::COUNTRY);
            $table->string(Entity::STATE);
            $table->string(Entity::ADDRESS_LINE_ONE);
            $table->string(Entity::ADDRESS_LINE_TWO);
            $table->string(Entity::CITY);
            $table->string(Entity::PINCODE);

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
            $table->index(Entity::UPDATED_AT);

            // $table->foreign(Entity::CUSTOMER_ID)
            //       ->references(Customer\Entity::ID)
            //       ->on(Table::CUSTOMER)
            //       ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::drop(Table::ADDRESS, function (Blueprint $table)
        // {
        //     $table->dropForeign(
        //         Table::ADDRESS . '_' . Entity::CUSTOMER_ID . '_foreign');
        // });

        Schema::drop(Table::ADDRESS);
    }
}
