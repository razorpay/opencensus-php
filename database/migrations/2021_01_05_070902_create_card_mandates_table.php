<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\CardMandate\Entity;

class CreateCardMandatesTable extends Migration
{
    const VARCHAR_LEN = 255;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CARD_MANDATE, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::MANDATE_SUMMARY_URL, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::MANDATE_ID, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::STATUS, self::VARCHAR_LEN);

            $table->string(Entity::MANDATE_CARD_ID, 20)
                  ->nullable();

            $table->string(Entity::MANDATE_CARD_NAME, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::MANDATE_CARD_LAST4, 4)
                  ->nullable();

            $table->string(Entity::MANDATE_CARD_NETWORK, 20)
                  ->nullable();

            $table->string(Entity::MANDATE_CARD_TYPE, 10)
                  ->nullable();

            $table->string(Entity::MANDATE_CARD_ISSUER, 10)
                  ->nullable();

            $table->tinyInteger(Entity::MANDATE_CARD_INTERNATIONAL)
                  ->nullable();

            $table->string(Entity::DEBIT_TYPE, 20)
                  ->nullable();

            $table->char(Entity::CURRENCY, 3)
                  ->nullable();

            $table->bigInteger(Entity::MAX_AMOUNT)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->nullable();

            $table->unsignedInteger(Entity::START_AT)
                  ->nullable();

            $table->bigInteger(Entity::END_AT)
                  ->nullable();

            $table->Integer(Entity::TOTAL_CYCLES)
                  ->nullable();

            $table->Integer(Entity::MANDATE_INTERVAL)
                  ->nullable();

            $table->string(Entity::FREQUENCY, 20)
                  ->nullable();

            $table->string(Entity::PAUSED_BY, 30)
                  ->nullable();

            $table->string(Entity::CANCELLED_BY, 30)
                  ->nullable();

            $table->string(Entity::MANDATE_HUB, 30)
                  ->nullable();

            $table->string(Entity::VAULT_TOKEN_PAN, 50)
                ->nullable();

            $table->char(Entity::TERMINAL_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);

            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->unsignedInteger(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::CARD_MANDATE);
    }
}
