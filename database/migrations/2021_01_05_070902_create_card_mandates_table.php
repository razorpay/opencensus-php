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

            $table->string(Entity::MANDATE_REGISTER_ID, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::MANDATE_ID, self::VARCHAR_LEN)
                  ->nullable();

            $table->string(Entity::STATUS, self::VARCHAR_LEN);

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
