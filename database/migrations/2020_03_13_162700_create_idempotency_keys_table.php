<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\IdempotencyKey\Entity;

class CreateIdempotencyKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::IDEMPOTENCY_KEY, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->string(Entity::IDEMPOTENCY_KEY, 255);

            $table->char(Entity::MERCHANT_ID, Merchant\Entity::ID_LENGTH);

            $table->char(Entity::SOURCE_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::SOURCE_TYPE, 255)
                  ->nullable();

            $table->string(Entity::REQUEST_HASH, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::SOURCE_ID);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::IDEMPOTENCY_KEY);

            $table->unique([
                               Entity::IDEMPOTENCY_KEY,
                               Entity::MERCHANT_ID,
                           ], 'idempotency_keys_idempotency_key_merchant_id_unique');

            // TODO: Remove before running on production
            $table->foreign(Entity::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::IDEMPOTENCY_KEY, function($table)
        {
            $table->dropForeign(Table::IDEMPOTENCY_KEY . '_' . Entity::MERCHANT_ID . '_foreign');
        });

        Schema::drop(Table::IDEMPOTENCY_KEY);
    }
}
