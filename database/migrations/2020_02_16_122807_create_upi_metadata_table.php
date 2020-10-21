<?php

use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Payment\UpiMetadata\Entity;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUpiMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::UPI_METADATA, function (Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::PAYMENT_ID, Payment\Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::FLOW, 25);

            $table->char(Entity::TYPE, 25);

            $table->integer(Entity::START_TIME)
                  ->nullable();

            $table->integer(Entity::END_TIME)
                  ->nullable();

            $table->string(Entity::VPA, 100)
                  ->nullable();

            $table->integer(Entity::EXPIRY_TIME)
                  ->nullable();

            $table->string(Entity::PROVIDER)
                  ->nullable();

            $table->char(Entity::MODE, 25)
                ->nullable();

            $table->string(Entity::REFERENCE, 100)
                  ->nullable();

            $table->string(Entity::NPCI_TXN_ID, 100)
                  ->nullable();

            $table->char(Entity::RRN, 12)
                ->nullable();

            $table->string(Entity::UMN)
                  ->nullable();

            $table->string(Entity::INTERNAL_STATUS, 100)
                  ->nullable();

            $table->string(Entity::REMINDER_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->integer(Entity::REMIND_AT)
                  ->nullable();

            // Timestamps
            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);

            $table->string(Entity::APP, 255)
                  ->nullable();

            $table->string(Entity::ORIGIN, 100)
                  ->nullable();

            $table->string(Entity::FLAG, 255)
                  ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::UPI_METADATA);
    }
}
