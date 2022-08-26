<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Constants\Product;
use RZP\Constants\Procurer;
use RZP\Models\Pricing\Entity as Pricing;
use RZP\Models\Pricing\Type as PricingType;

class CreatePricing extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::PRICING, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Pricing::ID, Pricing::ID_LENGTH)
                  ->primary();

            $table->char(Pricing::PLAN_ID, Pricing::ID_LENGTH);

            $table->string(Pricing::PLAN_NAME);

            $table->string(Pricing::PRODUCT, 255)
                  ->default(Product::PRIMARY);

            $table->string(Pricing::FEATURE, 255);

            $table->string(Pricing::TYPE)
                  ->default(PricingType::PRICING);

            $table->string(Pricing::APP_NAME)
                  ->nullable();

            $table->string(Pricing::GATEWAY)
                  ->nullable();

            $table->string(Pricing::PROCURER)
                  ->nullable();

            $table->string(Pricing::PAYMENT_METHOD)
                  ->nullable();

            $table->string(Pricing::PAYMENT_METHOD_TYPE)
                  ->nullable();

            $table->string(Pricing::PAYMENT_METHOD_SUBTYPE)
                  ->nullable();

            $table->string(Pricing::AUTH_TYPE)
                  ->nullable();

            $table->string(Pricing::PAYMENT_NETWORK)
                  ->nullable();

            $table->string(Pricing::PAYMENT_ISSUER)
                  ->nullable();

            $table->integer(Pricing::EMI_DURATION)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Pricing::INTERNATIONAL)
                  ->default(0);

            $table->tinyInteger(Pricing::FEE_BEARER)
                  ->default(0); // 0 is platform fee bearer

            $table->string(Pricing::RECEIVER_TYPE)
                  ->nullable();

            $table->tinyInteger(Pricing::AMOUNT_RANGE_ACTIVE)
                  ->default(0);

            $table->unsignedBigInteger(Pricing::AMOUNT_RANGE_MIN)
                  ->nullable();

            $table->unsignedBigInteger(Pricing::AMOUNT_RANGE_MAX)
                  ->nullable();

            $table->integer(Pricing::PERCENT_RATE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Pricing::FIXED_RATE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Pricing::MIN_FEE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Pricing::MAX_FEE)
                  ->unsigned()
                  ->nullable();

            $table->string(Pricing::ACCOUNT_TYPE)
                  ->nullable();

            $table->string(Pricing::CHANNEL)
                  ->nullable();

            $table->string(Pricing::PAYOUTS_FILTER)
                  ->nullable();

            $table->char(Pricing::ORG_ID, Pricing::ID_LENGTH)
                  ->nullable();

            $table->integer(Pricing::CREATED_AT);
            $table->integer(Pricing::UPDATED_AT);

            $table->integer(Pricing::DELETED_AT)
                  ->nullable();

            $table->integer(Pricing::EXPIRED_AT)
                  ->nullable();

            $table->char(Pricing::AUDIT_ID,Pricing::ID_LENGTH)->nullable();

            $table->string(Pricing::FEE_MODEL,255)
                ->nullable();

            $table->index(Pricing::PLAN_ID);
            $table->index([Pricing::PLAN_NAME, Pricing::PLAN_ID]);
            $table->index([Pricing::ORG_ID, Pricing::PLAN_ID]);
            $table->index([Pricing::ORG_ID, Pricing::PLAN_NAME]);
            $table->index(Pricing::INTERNATIONAL);
            $table->index(Pricing::ACCOUNT_TYPE);
            $table->index(Pricing::CHANNEL);
            $table->index(Pricing::DELETED_AT);
            $table->index(Pricing::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::PRICING);
    }
}
