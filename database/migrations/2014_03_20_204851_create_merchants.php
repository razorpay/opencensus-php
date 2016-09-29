<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;

use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\FeeBearer;

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

            $table->string(Merchant::NAME);

            $table->string(Merchant::EMAIL, 255);

            $table->tinyInteger(Merchant::ACTIVATED)
                  ->default(0);

            $table->integer(Merchant::ACTIVATED_AT)
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

            $table->integer(Merchant::SETTLEMENT_SCHEDULE);

            $table->string(Merchant::TRANSACTION_REPORT_EMAIL)
                  ->nullable();

            $table->string(Merchant::FEATURES)
                  ->nullable();

            $table->tinyInteger(Merchant::FEE_BEARER)
                  ->default(FeeBearer::getValueForBearerString(FeeBearer::PLATFORM));

            $table->char(Merchant::BRAND_COLOR, 6)
                  ->nullable();

            $table->text(Merchant::LOGO_URL)
                  ->nullable();

            $table->tinyInteger(Merchant::RISK_RATING);

            $table->tinyInteger(Merchant::RECEIPT_EMAIL_ENABLED)
                  ->default(1);

            $table->integer(Merchant::MAX_PAYMENT_AMOUNT)
                  ->nullable();

            // Columns for Method and Gateway Based Categories
            $table->string(Merchant::CATEGORY2)
                  ->nullable();

            $table->integer(Merchant::CREATED_AT);

            $table->integer(Merchant::UPDATED_AT);

            $table->index(Merchant::ACTIVATED_AT);
            $table->index(Merchant::ACTIVATED);
            $table->index(Merchant::LIVE);
            $table->index(Merchant::HOLD_FUNDS);
            $table->index(Merchant::CATEGORY);
            $table->index(Merchant::INTERNATIONAL);
            $table->index(Merchant::RECEIPT_EMAIL_ENABLED);
            $table->index(Merchant::RISK_RATING);
            $table->index(Merchant::EMAIL);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop(Table::MERCHANT);
    }
}
