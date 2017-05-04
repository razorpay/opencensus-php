<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Aeps\Base\Entity as Aeps;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;

class CreateAeps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::AEPS, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Aeps::ID);

            $table->string(Aeps::PAYMENT_ID, UniqueIdEntity::ID_LENGTH);

            $table->string(Aeps::REFUND_ID, UniqueIdEntity::ID_LENGTH)->nullable();

            $table->integer(Aeps::AMOUNT);

            $table->tinyInteger(Aeps::RECEIVED)->default(0);

            $table->tinyInteger(Aeps::REVERSED)->default(0);

            $table->string(Aeps::ERROR_CODE)->nullable();

            $table->string(Aeps::REV_ERROR_CODE)->nullable();

            $table->string(Aeps::REV_ERROR_DESCRIPTION)->nullable();

            $table->string(Aeps::AADHAAR_NUMBER)->nullable();

            $table->string(Aeps::ERROR_DESCRIPTION)->nullable();

            $table->string(Aeps::COUNTER)->nullable();

            $table->string(Aeps::RRN)->nullable();

            $table->integer(Aeps::CREATED_AT);

            $table->integer(Aeps::UPDATED_AT);

            $table->foreign(Aeps::PAYMENT_ID)
                  ->references(Payment\Entity::ID)
                  ->on(Table::PAYMENT)
                  ->on_delete('restrict');

            $table->index(Aeps::PAYMENT_ID);

            $table->index(Aeps::CREATED_AT);

            $table->index(Aeps::UPDATED_AT);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::AEPS, function($table)
        {
            $table->dropForeign('aeps_payment_id_foreign');
        });

        Schema::drop(Table::AEPS);
    }
}
