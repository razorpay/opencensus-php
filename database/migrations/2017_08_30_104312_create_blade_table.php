<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Gateway\Mpi\Base\Entity as Mpi;
use RZP\Models\Payment\Entity as Payment;
use RZP\Models\Payment\Refund\Entity as Refund;

class CreateBladeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MPI, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->increments(Mpi::ID);

            $table->string(Mpi::ACTION)
                  ->nullable();

            $table->char(Mpi::ACQUIRER, 10)
                  ->nullable();

            $table->char(Mpi::GATEWAY, 64)
                  ->nullable();

            $table->string(Mpi::PAYMENT_ID, Payment::ID_LENGTH);

            $table->char(Mpi::REFUND_ID, Refund::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Mpi::AMOUNT);

            $table->string(Mpi::CURRENCY, 3)
                  ->nullable();

            $table->char(Mpi::STATUS, 1)
                  ->nullable();

            $table->char(Mpi::XID, 40)
                  ->nullable();

            $table->char(Mpi::CAVV, 40)
                  ->nullable();

            $table->char(Mpi::ACC_ID, 40)
                  ->nullable();

            $table->char(Mpi::CAVV_ALGORITHM, 1)
                  ->nullable();

            $table->char(Mpi::ENROLLED, 1)
                  ->nullable();

            $table->char(Mpi::ECI, 2)
                  ->nullable();

            $table->string(Mpi::GATEWAY_PAYMENT_ID)
                  ->nullable();

            $table->string(Mpi::RESPONSE_CODE)
                  ->nullable();

            $table->string(Mpi::RESPONSE_DESCRIPTION)
                  ->nullable();

//            Max length of URL is 2083 characters. Providing some buffer for URL encoding, if applicable
            $table->string(Mpi::ACS_URL, 3000)
                  ->nullable()
                  ->default(null);

            $table->integer(Mpi::RECEIVED)
                  ->default(0);

            $table->integer(Mpi::CREATED_AT);

            $table->integer(Mpi::UPDATED_AT);

            $table->foreign(Mpi::PAYMENT_ID)
                  ->references(Mpi::ID)
                  ->on(Table::PAYMENT)
                  ->onDelete('restrict');

            $table->index(Mpi::STATUS);
            $table->index(Mpi::RECEIVED);
            $table->index(Mpi::CREATED_AT);
            $table->index(Mpi::REFUND_ID);
            $table->index(Mpi::GATEWAY_PAYMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MPI, function($table)
        {
            $table->dropForeign(Table::MPI . '_' . Mpi::PAYMENT_ID . '_foreign');
        });

        Schema::drop(Table::MPI);
    }
}
