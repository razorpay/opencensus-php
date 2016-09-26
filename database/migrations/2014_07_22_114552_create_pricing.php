<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Pricing\Feature;
use RZP\Models\Pricing\Entity as Pricing;

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

            $table->string(Pricing::FEATURE, 20);

            $table->string(Pricing::GATEWAY)
                  ->nullable();

            $table->string(Pricing::PAYMENT_METHOD);

            $table->string(Pricing::PAYMENT_METHOD_TYPE)
                  ->nullable();

            $table->string(Pricing::PAYMENT_NETWORK)
                  ->nullable();

            $table->string(Pricing::PAYMENT_ISSUER)
                  ->nullable();

            $table->tinyInteger(Pricing::INTERNATIONAL)
                  ->default(0);

            $table->tinyInteger(Pricing::AMOUNT_RANGE_ACTIVE)
                  ->default(0);

            $table->integer(Pricing::AMOUNT_RANGE_MIN)
                  ->unsigned()
                  ->nullable();

            $table->integer(Pricing::AMOUNT_RANGE_MAX)
                  ->unsigned()
                  ->nullable();

            $table->integer(Pricing::PERCENT_RATE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Pricing::FIXED_RATE)
                  ->unsigned()
                  ->default(0);

            $table->integer(Pricing::CREATED_AT);
            $table->integer(Pricing::UPDATED_AT);

            $table->integer(Pricing::EXPIRED_AT)
                  ->nullable();

            $table->index(Pricing::PLAN_ID);
            $table->index(Pricing::INTERNATIONAL);
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
