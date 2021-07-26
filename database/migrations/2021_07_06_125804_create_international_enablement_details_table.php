<?php

use RZP\Constants\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use RZP\Models\Merchant\InternationalEnablement\Detail\Entity;


 class CreateInternationalEnablementDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::INTERNATIONAL_ENABLEMENT_DETAIL, function (Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Entity::ID, Entity::ID_LENGTH)
                ->primary();

            $table->char(Entity::MERCHANT_ID, Entity::ID_LENGTH);

            $table->char(Entity::REVISION_ID, Entity::ID_LENGTH);

            $table->string(Entity::GOODS_TYPE, Entity::GOODS_TYPE_FIELD_MAX_LENGTH)
                ->nullable();

            $table->text(Entity::BUSINESS_USE_CASE)
                ->nullable();

            $table->text(Entity::ALLOWED_CURRENCIES)
                ->nullable();

            $table->bigInteger(Entity::MONTHLY_SALES_INTL_CARDS_MIN)
                ->nullable();

            $table->bigInteger(Entity::MONTHLY_SALES_INTL_CARDS_MAX)
                ->nullable();

            $table->bigInteger(Entity::BUSINESS_TXN_SIZE_MIN)
                ->nullable();

            $table->bigInteger(Entity::BUSINESS_TXN_SIZE_MAX)
                ->nullable();

            $table->text(Entity::LOGISTIC_PARTNERS)
                ->nullable();

            $table->string(Entity::ABOUT_US_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::CONTACT_US_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::TERMS_AND_CONDITIONS_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::PRIVACY_POLICY_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::REFUND_AND_CANCELLATION_POLICY_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::SHIPPING_POLICY_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->string(Entity::SOCIAL_MEDIA_PAGE_LINK, Entity::LINK_FIELD_MAX_LENGTH)
                ->nullable();

            $table->text(Entity::EXISTING_RISK_CHECKS)
                ->nullable();

            $table->text(Entity::CUSTOMER_INFO_COLLECTED)
                ->nullable();

            $table->text(Entity::PARTNER_DETAILS_PLUGINS)
                ->nullable();

            $table->tinyInteger(Entity::ACCEPTS_INTL_TXNS)
                ->default(0);

            $table->char(Entity::IMPORT_EXPORT_CODE, Entity::IMPORT_EXPORT_CODE_LENGTH)
                ->nullable();

            $table->string(Entity::PRODUCTS, 255)
                ->nullable();

            $table->tinyInteger(Entity::SUBMIT)
                ->default(0);

            $table->unsignedInteger(Entity::CREATED_AT);

            $table->unsignedInteger(Entity::UPDATED_AT);

            $table->unsignedInteger(Entity::DELETED_AT)
                ->nullable();

            // TODO: check for other indexes
            $table->index(Entity::CREATED_AT);

            $table->index(Entity::MERCHANT_ID);

            $table->index(Entity::REVISION_ID);
        });
    }

     /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::INTERNATIONAL_ENABLEMENT_DETAIL);
    }
}
