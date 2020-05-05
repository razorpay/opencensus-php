<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use RZP\Models\Merchant\Reminders\Entity;
use Illuminate\Database\Migrations\Migration;

class CreateMerchantReminders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_REMINDERS, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->integer(Entity::REMINDER_COUNT)
                ->unsigned()
                ->nullable();

            $table->char(Entity::REMINDER_ID, Entity::ID_LENGTH)
                ->nullable();

            $table->string(Entity::REMINDER_STATUS, 255)
                ->nullable();

            $table->string(Entity::REMINDER_NAMESPACE, 255)
                ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_REMINDERS);
    }
}
