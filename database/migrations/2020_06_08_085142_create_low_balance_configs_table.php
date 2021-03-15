<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Balance\LowBalanceConfig\Entity;

class CreateLowBalanceConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::LOW_BALANCE_CONFIG, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::BALANCE_ID, Entity::ID_LENGTH);

            $table->integer(Entity::TYPE)
                  ->nullable();

            // TODO: use unsigned integer or unsigned big integer ?
            $table->unsignedBigInteger(Entity::THRESHOLD_AMOUNT);

            $table->unsignedBigInteger(Entity::AUTOLOAD_AMOUNT);

            $table->text(Entity::NOTIFICATION_EMAILS);

            $table->string(Entity::STATUS, Entity::ID_LENGTH);

            $table->unsignedInteger(Entity::NOTIFY_AFTER);

            $table->integer(Entity::NOTIFY_AT)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            // indexes TODO: check and refactor indexes
            $table->index(Entity::CREATED_AT);

            $table->index([Entity::BALANCE_ID, Entity::MERCHANT_ID, Entity::STATUS]);

            $table->index([Entity::STATUS, Entity::CREATED_AT, Entity::ID]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::LOW_BALANCE_CONFIG);
    }
}
