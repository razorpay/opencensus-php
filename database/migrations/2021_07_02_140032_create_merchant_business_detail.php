<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
class CreateMerchantBusinessDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_BUSINESS_DETAIL, function(Blueprint $table) {
            $table->engine = 'InnoDB';

            $table->char(BusinessDetailEntity::ID, BusinessDetailEntity::ID_LENGTH)
                  ->primary();

            $table->char(BusinessDetailEntity::MERCHANT_ID, BusinessDetailEntity::ID_LENGTH);

            $table->json(BusinessDetailEntity::WEBSITE_DETAILS)
                  ->nullable();

            $table->json(BusinessDetailEntity::PLUGIN_DETAILS)
                ->nullable();

            $table->json(BusinessDetailEntity::APP_URLS)
                ->nullable();

            $table->string(BusinessDetailEntity::BLACKLISTED_PRODUCTS_CATEGORY, 255)
                ->nullable();

            $table->string(BusinessDetailEntity::BUSINESS_PARENT_CATEGORY, 255)
                ->nullable();

            $table->string(BusinessDetailEntity::ONBOARDING_SOURCE, 255)
                  ->nullable();

            $table->json(BusinessDetailEntity::METADATA)
                ->nullable();

            $table->integer(BusinessDetailEntity::CREATED_AT);

            $table->integer(BusinessDetailEntity::UPDATED_AT);

            $table->integer(BusinessDetailEntity::MIQ_SHARING_DATE)
                ->default(0);

            $table->integer(BusinessDetailEntity::TESTING_CREDENTIALS_DATE)
                ->default(0);

            $table->char(BusinessDetailEntity::AUDIT_ID,BusinessDetailEntity::ID_LENGTH)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::MERCHANT_BUSINESS_DETAIL);
    }
}
