<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\MerchantNotificationConfig;
use RZP\Models\Merchant\MerchantNotificationConfig\Entity;

class CreateMerchantNotificationConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_NOTIFICATION_CONFIG, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->string(Entity::CONFIG_STATUS)
                  ->default(MerchantNotificationConfig\Status::ENABLED);

            $table->text(Entity::NOTIFICATION_EMAILS)
                  ->nullable();

            $table->text(Entity::NOTIFICATION_MOBILE_NUMBERS)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->string(Entity::NOTIFICATION_TYPE)
                  ->default('bene_bank_downtime');

            // indices
            $table->index(Entity::CREATED_AT);

            $table->index([Entity::MERCHANT_ID, Entity::CREATED_AT]);

            $table->index([Entity::MERCHANT_ID, Entity::CONFIG_STATUS]);

            $table->index([Entity::CONFIG_STATUS, Entity::CREATED_AT, Entity::ID]);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT_NOTIFICATION_CONFIG);
    }
}
