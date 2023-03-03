<?php

use RZP\Constants\Table;
use RZP\Models\Admin\Org\Constants;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Merchant\Entity as Merchant;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOrgs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::create(Table::ORG, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(Org::ID, Org::ID_LENGTH)
                  ->primary();

            $table->string(Org::BUSINESS_NAME);

            $table->string(Org::DISPLAY_NAME);

            $table->string(Org::EMAIL)
                  ->unique();

            $table->string(Org::TYPE)
                  ->nullable();

            $table->string(Org::AUTH_TYPE);

            $table->text(Org::EMAIL_DOMAINS);

            $table->boolean(Org::ALLOW_SIGN_UP)
                  ->default(0);

            $table->boolean(Org::CROSS_ORG_ACCESS)
                  ->default(0);

            $table->string(Org::LOGIN_LOGO_URL)
                  ->nullable();

            $table->string(Org::MAIN_LOGO_URL)
                  ->nullable();

            $table->string(Org::INVOICE_LOGO_URL)
                  ->nullable();

            $table->string(Org::PAYMENT_BTN_LOGO_URL)
                  ->nullable();

            $table->string(Org::PAYMENT_APPS_LOGO_URL)
                  ->nullable();

            $table->string(Org::CHECKOUT_LOGO_URL)
                ->nullable();

            $table->string(Org::EMAIL_LOGO_URL)
                ->nullable();

            $table->string(Org::BACKGROUND_IMAGE_URL)
                ->nullable();

            $table->string(Org::CUSTOM_CODE, 255)
                  ->nullable()
                  ->unique();

            $table->string(Org::FROM_EMAIL)
                  ->nullable();

            $table->string(Org::SIGNATURE_EMAIL)
                  ->nullable();

            $table->char(Org::DEFAULT_PRICING_PLAN_ID, Org::ID_LENGTH)
                  ->nullable();

            $table->boolean(Org::MERCHANT_SECOND_FACTOR_AUTH)
                  ->default(0);

            $table->integer(Org::MERCHANT_MAX_WRONG_2FA_ATTEMPTS)
                  ->default(9);

            $table->unsignedSmallInteger(ORG::MERCHANT_SESSION_TIMEOUT_IN_SECONDS)
                ->default(Org::DEFAULT_MERCHANT_SESSION_TIMEOUT_IN_SECONDS);

            $table->boolean(Org::ADMIN_SECOND_FACTOR_AUTH)
                  ->default(0);

            $table->integer(Org::ADMIN_MAX_WRONG_2FA_ATTEMPTS)
                  ->default(9);

            $table->string(Org::SECOND_FACTOR_AUTH_MODE)
                  ->default(Constants::SMS);

            $table->string(Org::EXTERNAL_REDIRECT_URL)
                ->nullable();

            $table->text(Org::EXTERNAL_REDIRECT_URL_TEXT)
                ->nullable();

            // Adds created_at and updated_at columns to the table
            $table->integer(Org::CREATED_AT);

            $table->integer(Org::UPDATED_AT);

            $table->integer(Org::DELETED_AT)
                  ->unsigned()
                  ->nullable();

            $table->json(Org::MERCHANT_STYLES)
                ->nullable();

            $table->index(Org::CREATED_AT);
            $table->index(Org::UPDATED_AT);
            $table->index(Org::DELETED_AT);
        });

        Schema::table(Table::MERCHANT, function(Blueprint $table)
        {
            $table->foreign(Merchant::ORG_ID)
                  ->references(Org::ID)
                  ->on(Table::ORG)
                  ->on_delete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT, function($table)
        {
            $table->dropForeign(
                Table::MERCHANT.'_'.Merchant::ORG_ID.'_foreign');
        });

        Schema::drop(Table::ORG);
    }
}
