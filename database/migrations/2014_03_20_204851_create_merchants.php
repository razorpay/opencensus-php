<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\FeeBearer;
use RZP\Models\Merchant\FeeModel;

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

            $table->string(Merchant::EMAIL, 255);

            $table->char(Merchant::PARENT_ID, Merchant::ID_LENGTH)
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

            $table->tinyInteger(Merchant::HOLD_FUNDS)
                  ->default(0);

            $table->char(Merchant::PRICING_PLAN_ID, Merchant::ID_LENGTH)
                  ->nullable();

            $table->string(Merchant::WEBSITE)
                  ->nullable();

            $table->char(Merchant::CATEGORY, 4)
                  ->nullable();

            $table->tinyInteger(Merchant::INTERNATIONAL)
                  ->default(0);

            $table->string(Merchant::BILLING_LABEL)
                  ->nullable();

            $table->string(Merchant::CHANNEL, 32);

            $table->string(Merchant::TRANSACTION_REPORT_EMAIL)
                  ->nullable();

            $table->tinyInteger(Merchant::FEE_BEARER)
                  ->default(FeeBearer::getValueForBearerString(FeeBearer::PLATFORM));

            $table->tinyInteger(Merchant::FEE_MODEL)
                  ->default(FeeModel::getValueForFeeModelString(FeeModel::PREPAID));

            $table->tinyInteger(Merchant::LINKED_ACCOUNT_KYC)
                  ->default(0);

            $table->char(Merchant::BRAND_COLOR, 6)
                  ->nullable();

            $table->char(Merchant::HANDLE, 4)
                  ->nullable();

            $table->text(Merchant::LOGO_URL)
                  ->nullable();

            $table->tinyInteger(Merchant::RISK_RATING);

            $table->tinyInteger(Merchant::RISK_THRESHOLD)
                  ->unsigned()
                  ->nullable();

            $table->tinyInteger(Merchant::RECEIPT_EMAIL_ENABLED)
                  ->default(1);

            $table->integer(Merchant::MAX_PAYMENT_AMOUNT)
                  ->unsigned()
                  ->nullable();

            $table->integer(Merchant::AUTO_REFUND_DELAY)
                  ->nullable()
                  ->default(null);

            $table->tinyInteger(Merchant::AUTO_CAPTURE_LATE_AUTH)
                  ->default(0);

            $table->tinyInteger(Merchant::CONVERT_CURRENCY)
                  ->nullable();

            // Columns for Method and Gateway Based Categories
            $table->string(Merchant::CATEGORY2)
                  ->nullable();

            $table->char(Merchant::INVOICE_CODE, 12);

            $table->integer(Merchant::CREATED_AT);

            $table->integer(Merchant::UPDATED_AT);

            $table->index(Merchant::ACTIVATED_AT);
            $table->index(Merchant::ACTIVATED);
            $table->index(Merchant::LIVE);
            $table->index(Merchant::HOLD_FUNDS);
            $table->index(Merchant::CATEGORY);
            $table->index(Merchant::INTERNATIONAL);
            $table->index(Merchant::LINKED_ACCOUNT_KYC);
            $table->index(Merchant::RECEIPT_EMAIL_ENABLED);
            $table->index(Merchant::RISK_RATING);
            $table->index(Merchant::EMAIL);
            $table->index(Merchant::AUTO_REFUND_DELAY);
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
