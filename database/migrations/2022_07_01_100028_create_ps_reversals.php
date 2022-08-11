<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Reversal\Entity;

class CreatePsReversals extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to data migration of Payouts.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_reversals', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYOUT_ID, Entity::ID_LENGTH);

            $table->string(Entity::BALANCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::AMOUNT)
                  ->unsigned();

            $table->integer('fees')
                  ->unsigned()
                  ->default(0);

            $table->integer(Entity::TAX)
                  ->unsigned()
                  ->default(0);

            $table->char(Entity::CURRENCY, 3);

            // TODO: Remove null-able after code deploy and backfilling
            $table->string(Entity::CHANNEL, 255)
                  ->nullable();

            // TODO: Figure out uniqueness stuff
            $table->string(Entity::UTR)
                  ->nullable();

            $table->text(Entity::NOTES);

            $table->char(Entity::TRANSACTION_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_reversals');
    }
}
