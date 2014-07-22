<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Constants\Table;
use Models\Pricing\Entity as Pricing;

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

            $table->string(Pricing::PLAN);

            $table->string(Pricing::GATEWAY);

            $table->string(Pricing::PAYMENT_METHOD);

            $table->string(Pricing::PAYMENT_METHOD_SUBTYPE);

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
