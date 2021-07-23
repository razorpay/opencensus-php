<?php

use RZP\Constants\Table;
use RZP\Models\Reward\Entity as Reward;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRewards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REWARD, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Reward::ID, Reward::ID_LENGTH)
                ->primary();

            $table->string(Reward::NAME, Reward::NAME_LENGTH)
                ->nullable();

            $table->char(Reward::ADVERTISER_ID, Reward::ID_LENGTH)
                ->nullable(false);

            $table->string(Reward::COUPON_CODE)
                ->nullable();

            $table->integer(Reward::PERCENT_RATE)
                ->nullable();

            $table->integer(Reward::MIN_AMOUNT)
                ->nullable();

            $table->integer(Reward::MAX_CASHBACK)
                ->nullable();

            $table->integer(Reward::FLAT_CASHBACK)
                ->nullable();

            $table->integer(Reward::STARTS_AT)
                ->nullable(false);

            $table->integer(Reward::ENDS_AT)
                ->nullable(false);

            $table->string(Reward::DISPLAY_TEXT, Reward::DISPLAY_TEXT_LENGTH)
                ->nullable();

            $table->string(Reward::LOGO)
                ->nullable();

            $table->text(Reward::TERMS)
                ->nullable();

            $table->boolean(Reward::IS_DELETED)
                ->default(false);

            $table->string(Reward::MERCHANT_WEBSITE_REDIRECT_LINK)
                ->nullable();

            $table->integer(Reward::CREATED_AT);

            $table->integer(Reward::UPDATED_AT);

            $table->index(Reward::STARTS_AT);

            $table->index(Reward::ENDS_AT);

            $table->index(Reward::NAME);

            $table->index(Reward::CREATED_AT);

            $table->string(Reward::BRAND_NAME, Reward::BRAND_NAME_LENGTH)
                ->nullable();

            $table->boolean(Reward::UNIQUE_COUPONS_EXIST)
                ->default(false);

            $table->boolean(Reward::UNIQUE_COUPONS_EXHAUSTED)
                ->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::REWARDS);
    }
}
