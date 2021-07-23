<?php

use RZP\Constants\Table;
use RZP\Models\Reward\RewardCoupon\Entity as RewardCoupon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRewardCoupons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::REWARD_COUPON, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(RewardCoupon::REWARD_ID, RewardCoupon::ID_LENGTH)
                ->nullable(false);

            $table->string(RewardCoupon::COUPON_CODE)
                ->nullable(false);

            $table->string(RewardCoupon::STATUS, RewardCoupon::STATUS_LENGTH);

            $table->integer(RewardCoupon::CREATED_AT);

            $table->integer(RewardCoupon::UPDATED_AT);

            $table->primary([RewardCoupon::REWARD_ID, RewardCoupon::COUPON_CODE]);

            $table->index(RewardCoupon::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::REWARD_COUPON);
    }
}
