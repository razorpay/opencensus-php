<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Payment\Entity as Payment;
use RZP\Gateway\Mozart\Entity as Mozart;
use RZP\Constants\Table;
use RZP\Models\Base\UniqueIdEntity;

class CreateMozartGateway extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create(Table::MOZART, function(Blueprint $table) {

            $table->engine = 'InnoDB';

            $table->increments(Mozart::ID);

            $table->char(Mozart::PAYMENT_ID,UniqueIdEntity::ID_LENGTH);

            $table->char(Mozart::ACTION)
                ->nullable();

            $table->char(Mozart::REFUND_ID, UniqueIdEntity::ID_LENGTH)
                ->nullable();

            $table->integer(Mozart::AMOUNT);

            $table->integer(Mozart::RECEIVED)
                ->default(0);

            $table->char(Mozart::GATEWAY);

            $table->integer(Mozart::CREATED_AT);

            $table->integer(Mozart::UPDATED_AT);

            $table->integer(Mozart::DELETED_AT)
                ->unsigned()
                ->nullable();

            $table->json(Mozart::RAW)
                ->nullable();

            $table->index(Mozart::PAYMENT_ID);
            $table->index(Mozart::REFUND_ID);
            $table->index(Mozart::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MOZART);
    }
}
