<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Reward\MerchantReward\Entity as MerchantReward;

class CreateMerchantRewards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_REWARD, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantReward::MERCHANT_ID, MerchantReward::ID_LENGTH);

            $table->char(MerchantReward::REWARD_ID, MerchantReward::ID_LENGTH);

            $table->string(MerchantReward::STATUS);

            $table->integer(MerchantReward::ACTIVATED_AT)
                ->nullable();

            $table->integer(MerchantReward::ACCEPTED_AT)
                ->nullable();

            $table->unique([MerchantReward::MERCHANT_ID, MerchantReward::REWARD_ID]);

            $table->integer(MerchantReward::CREATED_AT);

            $table->integer(MerchantReward::UPDATED_AT);

            $table->index(MerchantReward::STATUS);

            $table->index(MerchantReward::ACCEPTED_AT);

            $table->integer(MerchantReward::DEACTIVATED_AT)
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
        Schema::dropIfExists(Table::MERCHANT_REWARDS);
    }
}
