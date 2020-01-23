<?php

use Illuminate\Database\Migrations\Migration;
use RZP\Constants\Table;
use RZP\Models\Merchant\Detail\Entity as MerchantDetail;

class CreateQuboleMerchantDetailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        $columns = [
            MerchantDetail::ACTIVATION_FLOW,
            MerchantDetail::ACTIVATION_PROGRESS,
            MerchantDetail::ACTIVATION_STATUS,
            MerchantDetail::ARCHIVED_AT,
            MerchantDetail::BUSINESS_CATEGORY,
            MerchantDetail::BUSINESS_DBA,
            MerchantDetail::BUSINESS_DOE,
            MerchantDetail::BUSINESS_INTERNATIONAL,
            MerchantDetail::BUSINESS_MODEL,
            MerchantDetail::BUSINESS_NAME,
            MerchantDetail::BUSINESS_OPERATION_ADDRESS,
            MerchantDetail::BUSINESS_OPERATION_CITY,
            MerchantDetail::BUSINESS_OPERATION_PIN,
            MerchantDetail::BUSINESS_OPERATION_STATE,
            MerchantDetail::BUSINESS_PAYMENTDETAILS,
            MerchantDetail::BUSINESS_REGISTERED_ADDRESS,
            MerchantDetail::BUSINESS_REGISTERED_CITY,
            MerchantDetail::BUSINESS_REGISTERED_PIN,
            MerchantDetail::BUSINESS_REGISTERED_STATE,
            MerchantDetail::BUSINESS_SUBCATEGORY,
            MerchantDetail::BUSINESS_TYPE,
            MerchantDetail::BUSINESS_WEBSITE,
            MerchantDetail::CLARIFICATION_MODE,
            MerchantDetail::COMMENT,
            MerchantDetail::COMPANY_CIN,
            MerchantDetail::CONTACT_EMAIL,
            MerchantDetail::CONTACT_LANDLINE,
            MerchantDetail::CONTACT_MOBILE,
            MerchantDetail::CONTACT_NAME,
            MerchantDetail::CREATED_AT,
            MerchantDetail::DEPARTMENT,
            MerchantDetail::GSTIN,
            MerchantDetail::INTERNAL_NOTES,
            MerchantDetail::ISSUE_FIELDS,
            MerchantDetail::ISSUE_FIELDS_REASON,
            MerchantDetail::LIVE_TRANSACTION_DONE,
            MerchantDetail::LOCKED,
            MerchantDetail::MARKETPLACE_ACTIVATION_STATUS,
            MerchantDetail::MERCHANT_ID,
            MerchantDetail::P_GSTIN,
            MerchantDetail::REVIEWER_ID,
            MerchantDetail::ROLE,
            MerchantDetail::STEPS_FINISHED,
            MerchantDetail::SUBMITTED,
            MerchantDetail::SUBMITTED_AT,
            MerchantDetail::SUBSCRIPTIONS_ACTIVATION_STATUS,
            MerchantDetail::TRANSACTION_VALUE,
            MerchantDetail::TRANSACTION_VOLUME,
            MerchantDetail::UPDATED_AT,
            MerchantDetail::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
            MerchantDetail::WEBSITE_ABOUT,
            MerchantDetail::WEBSITE_CONTACT,
            MerchantDetail::WEBSITE_PRICING,
            MerchantDetail::WEBSITE_PRIVACY,
            MerchantDetail::WEBSITE_REFUND,
            MerchantDetail::WEBSITE_TERMS,
        ];

        $sha2_columns = [
            MerchantDetail::COMPANY_PAN,
            MerchantDetail::COMPANY_PAN_NAME,
            MerchantDetail::PROMOTER_PAN,
            MerchantDetail::BANK_NAME,
            MerchantDetail::BANK_ACCOUNT_NUMBER,
        ];
        
        $sha2_columns = array_map(function ($v)
        {
            return "sha2({$v}, 256) as {$v}";
        },
        $sha2_columns);

        $columns = array_merge($columns, $sha2_columns);

        $columnStr = implode(',', $columns);

        $statement = 'CREATE ALGORITHM=MERGE VIEW qubole_merchant_details_view AS 
                        SELECT ' . $columnStr .
                        ' FROM ' . Table::MERCHANT_DETAIL;

        DB::statement($statement);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('DROP VIEW IF EXISTS qubole_merchant_details_view');
    }
}