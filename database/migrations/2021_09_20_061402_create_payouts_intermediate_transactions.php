<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Payout\PayoutsIntermediateTransactions\Entity;

class CreatePayoutsIntermediateTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYOUTS_INTERMEDIATE_TRANSACTIONS, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::PAYOUT_ID, Entity::ID_LENGTH);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH);

            $table->bigInteger(Entity::CLOSING_BALANCE)
                  ->nullable();

            $table->integer(Entity::TRANSACTION_CREATED_AT)
                  ->nullable();

            $table->string(Entity::STATUS, 255)
                  ->nullable();

            $table->unsignedBigInteger(Entity::AMOUNT);

            $table->integer(Entity::PENDING_AT)
                  ->nullable();
            $table->integer(Entity::COMPLETED_AT)
                  ->nullable();
            $table->integer(Entity::REVERSED_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // check indexes

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::PAYOUT_ID);

            $table->index([Entity::STATUS, Entity::PENDING_AT]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PAYOUTS_INTERMEDIATE_TRANSACTIONS);
    }
}
