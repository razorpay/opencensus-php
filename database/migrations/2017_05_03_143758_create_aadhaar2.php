<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Aadhaar\Entity as Aadhaar;

class CreateAadhaar2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::AADHAAR2, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Aadhaar::ID);

            $table->string(Aeps::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Aeps::BANK)->nullable();

            $table->string(Aeps::MERCHANT_ID)->nullable();

            $table->string(Aeps::NUMBER)->nullable();

            $table->integer(Aeps::CREATED_AT);

            $table->integer(Aeps::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
