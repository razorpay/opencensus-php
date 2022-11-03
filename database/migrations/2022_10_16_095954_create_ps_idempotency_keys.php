<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Models\Merchant;
use RZP\Models\IdempotencyKey\Entity;


class CreatePsIdempotencyKeys extends Migration
{
    /**
     * This table doesn't exist on prod. It only exists on CI.
     * This is only to run test cases related to idempotency key feature migration to payouts service.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ps_idempotency_keys', function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::IDEMPOTENCY_KEY, 255);

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Entity::SOURCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->string(Entity::SOURCE_TYPE, 255)
                  ->nullable();

            $table->string(Entity::REQUEST_HASH, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ps_idempotency_keys');
    }
}
