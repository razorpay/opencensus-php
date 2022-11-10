<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Merchant\FeeModel;
use RZP\Models\Merchant\LegalEntity;
use RZP\Models\Merchant\RefundSource;
use RZP\Models\Payment\Refund\Speed as RefundSpeed;

class CreateMerchants extends Migration
{
    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Merchant::ID, Merchant::ID_LENGTH)
                  ->primary();

            $table->char(Merchant::ORG_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Merchant::NAME)
                  ->nullable();

            $table->string(Merchant::EMAIL, 255)->nullable();

            $table->string(Merchant::ACCOUNT_CODE, 255)
                  ->nullable()
                  ->default(null);

            $table->char(Merchant::PARENT_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->char(Merchant::LEGAL_ENTITY_ID, LegalEntity\Entity::ID_LENGTH)
                  ->nullable();

            $table->tinyInteger(Merchant::ACTIVATED)
                  ->default(0);

            $table->integer(Merchant::ACTIVATED_AT)
                  ->nullable();

            $table->integer(Merchant::ARCHIVED_AT)
                  ->nullable();

            $table->integer(Merchant::SUSPENDED_AT)
                  ->nullable();

            $table->tinyInteger(Merchant::LIVE)
                  ->default(0);

            $table->string(Merchant::LIVE_DISABLE_REASON)
                  ->nullable();

            $table->tinyInteger(Merchant::HOLD_FUNDS)
                  ->default(0);

            $table->string(Merchant::HOLD_FUNDS_REASON)
                  ->nullable();

            $table->char(Merchant::PRICING_PLAN_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Merchant::WEBSITE)
                  ->nullable();

            $table->char(Merchant::CATEGORY, 4)
                  ->nullable();

            $table->tinyInteger(Merchant::INTERNATIONAL)
                  ->default(0);

            $table->string(Merchant::PRODUCT_INTERNATIONAL, 50)
                  ->default('0000000000');

            $table->string(Merchant::BILLING_LABEL)
                  ->nullable();

            $table->string(Merchant::DISPLAY_NAME, 255)
                  ->nullable();

            $table->string(Merchant::CHANNEL, 32);

            $table->string(Merchant::TRANSACTION_REPORT_EMAIL)
                  ->nullable();

            $table->tinyInteger(Merchant::FEE_BEARER)
                  ->default(FeeBearer::getValueForBearerString(FeeBearer::PLATFORM));

            $table->tinyInteger(Merchant::FEE_MODEL)
                  ->default(FeeModel::getValueForFeeModelString(FeeModel::PREPAID));

            $table->bigInteger(Merchant::FEE_CREDITS_THRESHOLD)
                  ->unsigned()
                  ->nullable();

            $table->bigInteger(Merchant::AMOUNT_CREDITS_THRESHOLD)
                ->unsigned()
                ->default(null)
                ->nullable();

            $table->bigInteger(Merchant::REFUND_CREDITS_THRESHOLD)
                ->unsigned()
                ->default(null)
                ->nullable();

            $table->tinyInteger(Merchant::REFUND_SOURCE)
                  ->default(RefundSource::getValueForRefundSourceString(RefundSource::BALANCE));

            $table->tinyInteger(Merchant::LINKED_ACCOUNT_KYC)
                  ->default(0);

            $table->tinyInteger(Merchant::HAS_KEY_ACCESS)
                  ->default(0);

            $table->string(Merchant::PARTNER_TYPE, 255)
                  ->nullable();

            $table->char(Merchant::BRAND_COLOR, 6)
                  ->nullable();

            $table->char(Merchant::HANDLE, 4)
                  ->nullable();

            $table->string(Merchant::ACTIVATION_SOURCE, 255)
                  ->nullable();

            $table->string(Merchant::SIGNUP_SOURCE, 32)
                  ->nullable();

            $table->tinyInteger(Merchant::BUSINESS_BANKING)
                  ->default(0);

            $table->text(Merchant::LOGO_URL)
                  ->nullable();

            $table->text(Merchant::ICON_URL)
                  ->nullable();

            $table->string(Merchant::INVOICE_LABEL_FIELD, 50)
                  ->nullable();

            $table->tinyInteger(Merchant::RISK_RATING);

            $table->tinyInteger(Merchant::RISK_THRESHOLD)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Merchant::RECEIPT_EMAIL_ENABLED)
                  ->default(1);

            $table->tinyInteger(Merchant::RECEIPT_EMAIL_TRIGGER_EVENT)
                  ->unsigned()
                  ->default(1);

            $table->integer(Merchant::MAX_PAYMENT_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Merchant::MAX_INTERNATIONAL_PAYMENT_AMOUNT)
                ->unsigned()
                ->nullable();

            $table->integer(Merchant::AUTO_REFUND_DELAY)
                  ->nullable()
                  ->default(null);

            $table->enum(Merchant::DEFAULT_REFUND_SPEED, [RefundSpeed::NORMAL, RefundSpeed::OPTIMUM, RefundSpeed::INSTANT])
                  ->default(RefundSpeed::NORMAL);

            $table->tinyInteger(Merchant::AUTO_CAPTURE_LATE_AUTH)
                  ->default(0);

            $table->tinyInteger(Merchant::CONVERT_CURRENCY)
                  ->nullable();

            // Columns for Method and Gateway Based Categories
            $table->string(Merchant::CATEGORY2)
                  ->nullable();

            $table->char(Merchant::INVOICE_CODE, 12);

            $table->text(Merchant::NOTES)
                  ->nullable();

            $table->string(Merchant::WHITELISTED_IPS_LIVE, 255)
                  ->nullable();

            $table->string(Merchant::WHITELISTED_IPS_TEST, 255)
                  ->nullable();

            $table->text(Merchant::WHITELISTED_DOMAINS)
                  ->nullable();

            $table->tinyInteger(Merchant::SECOND_FACTOR_AUTH)
                  ->default(0);

            $table->tinyInteger(Merchant::RESTRICTED)
                  ->default(0);

            $table->text(Merchant::DASHBOARD_WHITELISTED_IPS_LIVE)
                  ->nullable();

            $table->text(Merchant::DASHBOARD_WHITELISTED_IPS_TEST)
                  ->nullable();

            $table->text(Merchant::PARTNERSHIP_URL)
                  ->nullable();

            $table->string(Merchant::EXTERNAL_ID)
                  ->nullable();

            $table->string(Merchant::PURPOSE_CODE,5)
                ->nullable();

            $table->integer(Merchant::CREATED_AT);

            $table->integer(Merchant::UPDATED_AT);

            $table->tinyInteger(Merchant::SIGNUP_VIA_EMAIL)->default(1);

            $table->bigInteger(Merchant::BALANCE_THRESHOLD)
                ->nullable();

            $table->char(Merchant::AUDIT_ID,Merchant::ID_LENGTH)->nullable();

            $table->string(Merchant::COUNTRY_CODE, 2)
                ->default('IN');

            $table->index(Merchant::ACTIVATED_AT);
            $table->index(Merchant::ACTIVATED);
            $table->index(Merchant::LIVE);
            $table->index(Merchant::LEGAL_ENTITY_ID);
            $table->index(Merchant::HOLD_FUNDS);
            $table->index(Merchant::CATEGORY);
            $table->index(Merchant::INTERNATIONAL);
            $table->index(Merchant::LINKED_ACCOUNT_KYC);
            $table->index(Merchant::RECEIPT_EMAIL_ENABLED);
            $table->index(Merchant::RISK_RATING);
            $table->index(Merchant::EMAIL);
            $table->index(Merchant::AUTO_REFUND_DELAY);
            $table->index(Merchant::EXTERNAL_ID);
            $table->index(Merchant::CREATED_AT);
            $table->index(Merchant::UPDATED_AT);
        });

        Schema::table(Table::MERCHANT, function(Blueprint $table)
        {
            $table->foreign(Merchant::PARENT_ID)
                  ->references(Merchant::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT . '_' . Merchant::PARENT_ID . '_foreign');
        });

        Schema::drop(Table::MERCHANT);
    }
}
