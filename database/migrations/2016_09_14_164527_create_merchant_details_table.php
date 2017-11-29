<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class CreateMerchantDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::MERCHANT_DETAIL, function(Blueprint $table)
        {
            $table->engine = 'InnoDB';

            $table->char(MerchantDetail::MERCHANT_ID, MerchantDetail::ID_LENGTH)
                  ->primary();

            $table->string(MerchantDetail::CONTACT_NAME)
                  ->nullable();

            $table->string(MerchantDetail::CONTACT_EMAIL)
                  ->nullable();

            $table->string(MerchantDetail::CONTACT_MOBILE)
                  ->nullable();

            $table->string(MerchantDetail::CONTACT_LANDLINE)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_TYPE)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_NAME)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_DBA)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_WEBSITE)
                  ->nullable();

             $table->boolean(MerchantDetail::BUSINESS_INTERNATIONAL)
                  ->default(0);

            $table->string(MerchantDetail::BUSINESS_PAYMENTDETAILS)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_REGISTERED_ADDRESS)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_REGISTERED_STATE)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_REGISTERED_CITY)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_REGISTERED_PIN)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_OPERATION_ADDRESS)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_OPERATION_STATE)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_OPERATION_CITY)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_OPERATION_PIN)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_DOE)
                  ->nullable();

            $table->string(MerchantDetail::GSTIN)
                  ->nullable();

            $table->string(MerchantDetail::P_GSTIN)
                  ->nullable();

            $table->string(MerchantDetail::COMPANY_CIN)
                  ->nullable();

            $table->string(MerchantDetail::COMPANY_PAN)
                  ->nullable();

            $table->string(MerchantDetail::COMPANY_PAN_NAME)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_MODEL)
                  ->nullable();

            $table->integer(MerchantDetail::TRANSACTION_VOLUME)
                  ->unsigned()
                  ->nullable();

            $table->integer(MerchantDetail::TRANSACTION_VALUE)
                  ->unsigned()
                  ->nullable();

            $table->string(MerchantDetail::PROMOTER_PAN)
                  ->nullable();

            $table->string(MerchantDetail::PROMOTER_PAN_NAME)
                  ->nullable();

            $table->string(MerchantDetail::BANK_NAME)
                  ->nullable();

            $table->string(MerchantDetail::BANK_ACCOUNT_NUMBER)
                  ->nullable();

            $table->string(MerchantDetail::BANK_ACCOUNT_NAME)
                  ->nullable();

            $table->string(MerchantDetail::BANK_ACCOUNT_TYPE)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BRANCH)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BRANCH_IFSC)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_ADDRESS1)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_ADDRESS2)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_ADDRESS3)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_CITY)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_STATE)
                  ->nullable();

            $table->string(MerchantDetail::BANK_BENEFICIARY_PIN)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_ABOUT)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_CONTACT)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_PRIVACY)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_TERMS)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_REFUND)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_PRICING)
                  ->nullable();

            $table->string(MerchantDetail::WEBSITE_LOGIN)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_PROOF_URL)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_OPERATION_PROOF_URL)
                  ->nullable();

            $table->string(MerchantDetail::BUSINESS_PAN_URL)
                  ->nullable();

            $table->string(MerchantDetail::ADDRESS_PROOF_URL)
                  ->nullable();

            $table->string(MerchantDetail::PROMOTER_PROOF_URL)
                  ->nullable();

            $table->string(MerchantDetail::PROMOTER_PAN_URL)
                  ->nullable();

            $table->string(MerchantDetail::PROMOTER_ADDRESS_URL)
                  ->nullable();

            $table->string(MerchantDetail::TRANSACTION_REPORT_EMAIL)
                  ->nullable();

            $table->string(MerchantDetail::ROLE)
                  ->nullable();

            $table->string(MerchantDetail::DEPARTMENT)
                  ->nullable();

            $table->string(MerchantDetail::COMMENT)
                  ->nullable();

            $table->string(MerchantDetail::STEPS_FINISHED)
                  ->nullable()
                  ->default('[]');

            $table->integer(MerchantDetail::ACTIVATION_PROGRESS)
                  ->default(0);

            $table->boolean(MerchantDetail::LOCKED)
                  ->default(0);

            $table->string(MerchantDetail::ACTIVATION_STATUS, 255)
                  ->nullable();

            $table->string(MerchantDetail::CLARIFICATION_MODE, 255)
                  ->nullable();

            $table->integer(MerchantDetail::ARCHIVED_AT)
                  ->nullable();

            $table->string(MerchantDetail::MARKETPLACE_ACTIVATION_STATUS, 30)
                ->nullable();

            $table->string(MerchantDetail::SUBSCRIPTIONS_ACTIVATION_STATUS, 30)
                ->nullable();

            $table->string(MerchantDetail::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS, 30)
                ->nullable();

            $table->boolean(MerchantDetail::SUBMITTED)
                  ->default(0);

            $table->integer(MerchantDetail::SUBMITTED_AT)
                  ->nullable();

            $table->integer(MerchantDetail::CREATED_AT);

            $table->integer(MerchantDetail::UPDATED_AT);

            $table->foreign(MerchantDetail::MERCHANT_ID)
                  ->references(Merchant\Entity::ID)
                  ->on(Table::MERCHANT)
                  ->on_delete('restrict');

            $table->index(MerchantDetail::ACTIVATION_STATUS);

            $table->index(MerchantDetail::ARCHIVED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(Table::MERCHANT_DETAIL, function(Blueprint $table)
        {
            $table->dropForeign(Table::MERCHANT_DETAIL .'_' .MerchantDetail::MERCHANT_ID .'_foreign');
        });

        Schema::drop(Table::MERCHANT_DETAIL);
    }
}
