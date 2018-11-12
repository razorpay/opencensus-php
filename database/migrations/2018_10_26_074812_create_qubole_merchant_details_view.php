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
            MerchantDetail::MERCHANT_ID, 
            MerchantDetail::BUSINESS_NAME,
            MerchantDetail::CONTACT_NAME,
            MerchantDetail::CONTACT_EMAIL,
            MerchantDetail::CONTACT_MOBILE,
            MerchantDetail::CONTACT_LANDLINE,
            MerchantDetail::BUSINESS_WEBSITE,
            MerchantDetail::BUSINESS_DBA,
            MerchantDetail::BUSINESS_REGISTERED_STATE,
            MerchantDetail::BUSINESS_REGISTERED_CITY,
            MerchantDetail::BUSINESS_REGISTERED_PIN,
            MerchantDetail::BUSINESS_INTERNATIONAL,
            MerchantDetail::BUSINESS_DOE,
            MerchantDetail::BUSINESS_TYPE,
            MerchantDetail::SUBMITTED,
            MerchantDetail::ACTIVATION_STATUS,
            MerchantDetail::CREATED_AT,
            MerchantDetail::SUBMITTED_AT,
            MerchantDetail::ARCHIVED_AT,
            MerchantDetail::UPDATED_AT,
            MerchantDetail::MARKETPLACE_ACTIVATION_STATUS,
            MerchantDetail::VIRTUAL_ACCOUNTS_ACTIVATION_STATUS,
            MerchantDetail::SUBSCRIPTIONS_ACTIVATION_STATUS,
        ];

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