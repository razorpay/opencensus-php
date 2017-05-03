<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Aadhaar\Entity as Aadhaar;
use RZP\Models\Base\UniqueIdEntity;

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

            $table->string(Aadhaar::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Aadhaar::BANK)->nullable();

            $table->string(Aadhaar::MERCHANT_ID)->nullable();

            $table->string(Aadhaar::NUMBER)->nullable();

            $table->integer(Aadhaar::CREATED_AT);

            $table->integer(Aadhaar::UPDATED_AT);

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
