<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\PaymentLink\PaymentPageItem\Entity;

class CreatePaymentPageItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PAYMENT_PAGE_ITEM, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::PAYMENT_LINK_ID, Entity::ID_LENGTH);

            $table->char(Entity::ITEM_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->char(Entity::PLAN_ID, Entity::ID_LENGTH)
                  ->nullable();

            $table->boolean(Entity::MANDATORY);

            $table->string(Entity::IMAGE_URL, 512)
                  ->nullable();

            $table->bigInteger(Entity::STOCK)
                  ->nullable();

            $table->bigInteger(Entity::QUANTITY_SOLD);

            $table->bigInteger(Entity::TOTAL_AMOUNT_PAID);

            $table->bigInteger(Entity::MIN_PURCHASE)
                  ->nullable();

            $table->bigInteger(Entity::MAX_PURCHASE)
                  ->nullable();

            $table->bigInteger(Entity::MIN_AMOUNT)
                  ->nullable();

            $table->bigInteger(Entity::MAX_AMOUNT)
                  ->nullable();

            $table->text(Entity::PRODUCT_CONFIG)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);
            $table->integer(Entity::UPDATED_AT);
            $table->integer(Entity::DELETED_AT)
                  ->nullable();

            $table->index(Entity::ITEM_ID);
            $table->index(Entity::PLAN_ID);
            $table->index(Entity::MERCHANT_ID);
            $table->index(Entity::PAYMENT_LINK_ID);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::PAYMENT_PAGE_ITEM);
    }
}
