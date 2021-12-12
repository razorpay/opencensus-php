<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\User\Entity as User;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\DeviceDetail\Attribution\Entity as AttributionEntity;

class CreateAppAttributionDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::APP_ATTRIBUTION_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(AttributionEntity::ID, AttributionEntity::ID_LENGTH)
                ->primary();

            $table->char(Merchant::MERCHANT_ID, Merchant::ID_LENGTH);

            $table->char(User::USER_ID, User::ID_LENGTH);

            $table->string(AttributionEntity::APPSFLYER_ID, 50)->nullable();

            $table->string(AttributionEntity::INSTALL_TIME, 50)->nullable();
            $table->string(AttributionEntity::EVENT_TYPE, 50)->nullable();
            $table->string(AttributionEntity::EVENT_TIME, 50)->nullable();

            $table->json(AttributionEntity::CAMPAIGN_ATTRIBUTES)->nullable();
            $table->json(AttributionEntity::CONTRIBUTOR_1_ATTRIBUTES)->nullable();
            $table->json(AttributionEntity::CONTRIBUTOR_2_ATTRIBUTES)->nullable();
            $table->json(AttributionEntity::CONTRIBUTOR_3_ATTRIBUTES)->nullable();

            $table->string(AttributionEntity::DEVICE_TYPE, 50)->nullable();
            $table->string(AttributionEntity::DEVICE_CATEGORY, 50)->nullable();
            $table->string(AttributionEntity::PLATFORM, 50)->nullable();
            $table->string(AttributionEntity::OS_VERSION, 50)->nullable();
            $table->string(AttributionEntity::APP_VERSION, 50)->nullable();

            $table->integer(AttributionEntity::CREATED_AT);

            $table->integer(AttributionEntity::UPDATED_AT);

            $table->index(Merchant::MERCHANT_ID);

            $table->index(User::USER_ID);

            $table->index(AttributionEntity::CREATED_AT);

            $table->index(AttributionEntity::UPDATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::APP_ATTRIBUTION_DETAIL);
    }
}
