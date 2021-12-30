<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\CardMandate\CardMandateNotification\Entity;

class CreateCardMandateNotificationsTable extends Migration
{
    const VARCHAR_LEN = 255;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::CARD_MANDATE_NOTIFICATION, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::CARD_MANDATE_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYMENT_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::REMINDER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->bigInteger(Entity::AMOUNT)
                  ->nullable();

            $table->char(Entity::CURRENCY, 3)
                  ->nullable();

            $table->text(Entity::PURPOSE)
                  ->nullable();

            $table->text(Entity::NOTES)
                  ->nullable();

            $table->string(Entity::STATUS, self::VARCHAR_LEN);

            $table->string(Entity::NOTIFICATION_ID, self::VARCHAR_LEN)
                  ->nullable();

            $table->unsignedInteger(Entity::NOTIFIED_AT)
                  ->nullable();

            $table->unsignedInteger(Entity::VERIFIED_AT)
                  ->nullable();

            $table->unsignedInteger(Entity::DEBIT_AT)
                  ->nullable();

            $table->tinyInteger(Entity::AFA_REQUIRED)
                  ->nullable();

            $table->string(Entity::AFA_STATUS, 20)
                  ->nullable();

            $table->unsignedInteger(Entity::AFA_COMPLETED_AT)
                  ->nullable();

            $table->unsignedInteger(Entity::CREATED_AT);

            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->unsignedInteger(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::NOTIFICATION_ID);

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
        Schema::dropIfExists(Table::CARD_MANDATE_NOTIFICATION);
    }
}
