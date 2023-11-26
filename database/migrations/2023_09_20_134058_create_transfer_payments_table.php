<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RZP\Constants\Table;
use RZP\Models\Transfer\Payment\Entity;

class CreateTransferPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::TRANSFER_PAYMENT, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->string(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::PAYMENT_ID, 14);

            $table->bigInteger(Entity::AMOUNT)
                  ->unsigned();

            $table->bigInteger(Entity::AMOUNT_TRANSFERRED)
                  ->unsigned()
                  ->default(0);

            $table->integer(Entity::CREATED_AT)->nullable();

            $table->integer(Entity::UPDATED_AT)
                  ->nullable();

            // Indices
           $table->index(Entity::PAYMENT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::TRANSFER_PAYMENT);
    }
}
