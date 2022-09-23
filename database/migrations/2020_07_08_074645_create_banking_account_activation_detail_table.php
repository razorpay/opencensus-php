<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use RZP\Constants\Table;
use RZP\Models\BankingAccount\Activation\Detail\Entity;

class CreateBankingAccountActivationDetailTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(Table::BANKING_ACCOUNT_ACTIVATION_DETAIL, function (Blueprint $table)
        {
            $table->char(Entity::ID, Entity::ID_LENGTH)
                  ->primary();

            $table->char(Entity::BANKING_ACCOUNT_ID, Entity::ID_LENGTH)
                  ->unique();

            $table->string(Entity::MERCHANT_POC_NAME)
                  ->nullable();

            $table->string(Entity::MERCHANT_POC_EMAIL)
                  ->nullable();

            $table->string(Entity::MERCHANT_POC_DESIGNATION)
                  ->nullable();

            $table->string(Entity::MERCHANT_POC_PHONE_NUMBER)
                  ->nullable();

            $table->string(Entity::MERCHANT_DOCUMENTS_ADDRESS)
                  ->nullable();

            $table->string(Entity::MERCHANT_CITY)
                  ->nullable();

            $table->string(Entity::MERCHANT_STATE)
                  ->nullable();

            $table->string(Entity::MERCHANT_REGION)
                  ->nullable();

            $table->string(Entity::BUSINESS_PAN)
                  ->nullable();

            $table->string(Entity::BUSINESS_NAME)
                  ->nullable();

            $table->string(Entity::BUSINESS_TYPE)
                  ->nullable();

            $table->string(Entity::APPLICATION_TYPE)
                  ->nullable();

            $table->unsignedBigInteger(Entity::AVERAGE_MONTHLY_BALANCE)
                  ->nullable();

            $table->unsignedBigInteger(Entity::EXPECTED_MONTHLY_GMV)
                  ->nullable();

            $table->unsignedBigInteger(Entity::INITIAL_CHEQUE_VALUE)
                  ->nullable();

            $table->string(Entity::BUSINESS_CATEGORY)
                  ->nullable();

            $table->string(Entity::ACCOUNT_TYPE)
                  ->nullable();

            $table->boolean(Entity::IS_DOCUMENTS_WALKTHROUGH_COMPLETE)
                  ->default(0);

            $table->json(Entity::ADDITIONAL_DETAILS)
                  ->nullable()
                  ->default(null);

            $table->boolean(Entity::CONTACT_VERIFIED)
                  ->default(0);

            $table->integer(Entity::VERIFICATION_DATE)
                  ->nullable();

            $table->boolean(Entity::DECLARATION_STEP)
                  ->nullable();

            $table->string(Entity::BUSINESS_PAN_VALIDATION)
                  ->nullable();

            $table->string(Entity::SALES_TEAM)
                  ->nullable();

            $table->string(Entity::SALES_POC_PHONE_NUMBER)
                ->nullable();

            $table->text(Entity::COMMENT)
                  ->nullable();

            $table->string(Entity::ASSIGNEE_TEAM)
                  ->nullable();

            $table->integer(Entity::BOOKING_DATE_AND_TIME)
                  ->nullable();

            $table->string(Entity::RM_NAME)
                  ->nullable();

            $table->string(Entity::RM_PHONE_NUMBER)
                  ->nullable();

            $table->char(Entity::BANK_POC_USER_ID, Entity::ID_LENGTH)
                  ->nullable();
            
            $table->json(Entity::RBL_ACTIVATION_DETAILS)
                  ->nullable()
                  ->default(null);

            $table->integer(Entity::CUSTOMER_APPOINTMENT_DATE)
                  ->nullable();

            $table->string(Entity::BRANCH_CODE)
                  ->nullable();

            $table->string(Entity::RM_EMPLOYEE_CODE)
                  ->nullable();

            $table->string(Entity::RM_ASSIGNMENT_TYPE)
                  ->nullable();

            $table->integer(Entity::DOC_COLLECTION_DATE)
                  ->nullable();

            $table->integer(Entity::ACCOUNT_OPENING_IR_CLOSE_DATE)
                  ->nullable();

            $table->boolean(Entity::ACCOUNT_OPENING_FTNR)
                  ->nullable();

            $table->text(Entity::ACCOUNT_OPENING_FTNR_REASONS)
                  ->nullable();

            $table->integer(Entity::API_IR_CLOSED_DATE)
                  ->nullable();

            $table->integer(Entity::LDAP_ID_MAIL_DATE)
                  ->nullable();

            $table->boolean(Entity::API_ONBOARDING_FTNR)
                  ->nullable();

            $table->text(Entity::API_ONBOARDING_FTNR_REASONS)
                  ->nullable();

            $table->integer(Entity::RZP_CA_ACTIVATED_DATE)
                  ->nullable();

            $table->integer(Entity::UPI_CREDENTIAL_RECEIVED_DATE)
                  ->nullable();

            $table->integer(Entity::DROP_OFF_DATE)
                  ->nullable();

            $table->integer(Entity::ACCOUNT_OPEN_DATE)
                  ->nullable();

            $table->integer(Entity::ACCOUNT_LOGIN_DATE)
                  ->nullable();

            $table->integer(Entity::CREATED_AT);

            $table->integer(Entity::UPDATED_AT);

            $table->index(Entity::CREATED_AT);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(Table::BANKING_ACCOUNT_ACTIVATION_DETAIL);
    }
}
