<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant\Website\Entity;

class CreateMerchantWebsiteTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_WEBSITE, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, \RZP\Models\Merchant\Entity::ID_LENGTH);

            $table->string(Entity::DELIVERABLE_TYPE, 255)
                  ->nullable();

            $table->string(Entity::SHIPPING_PERIOD, 255)
                  ->nullable();

            $table->string(Entity::REFUND_REQUEST_PERIOD, 255)
                  ->nullable();

            $table->string(Entity::REFUND_PROCESS_PERIOD, 255)
                  ->nullable();

            $table->string(Entity::WARRANTY_PERIOD, 255)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::CREATED_AT);

            $table->index(Entity::UPDATED_AT);

            $table->json(Entity::MERCHANT_WEBSITE_DETAILS)
                  ->nullable();

            $table->string(Entity::STATUS, 30)
                  ->nullable();

            $table->json(Entity::ADMIN_WEBSITE_DETAILS)
                  ->nullable();

            $table->json(Entity::ADDITIONAL_DATA)
                  ->nullable();

            $table->char(Entity::AUDIT_ID, Entity::ID_LENGTH)->nullable();


            $table->boolean(Entity::SEND_COMMUNICATION)
                  ->default(1);

            $table->tinyInteger(Entity::GRACE_PERIOD)
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
        Schema::dropIfExists(Table::MERCHANT_WEBSITE);
    }
}
