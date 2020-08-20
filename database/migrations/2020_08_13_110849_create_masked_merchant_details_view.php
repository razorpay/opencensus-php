<?php

use RZP\Constants\Table;
use Illuminate\Database\Migrations\Migration;

class CreateMaskedMerchantDetailsView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $columns = [
            'business_type',
            '"*redacted*" AS business_registered_address',
            '"*redacted*" AS business_operation_address_l2',
            '"*redacted*" AS p_gstin',
            'transaction_value',
            'bank_branch',
            'website_about',
            '"*redacted*" AS business_operation_proof_url',
            '"*redacted*" AS transaction_report_email',
            'poi_verification_status',
            'kyc_additional_details',
            'marketplace_activation_status',
            '"*redacted*" AS authorized_signatory_residential_address',
            'cin_verification_status',
            'business_name',
            '"*redacted*" AS business_registered_address_l2',
            'business_operation_state',
            'company_cin',
            'promoter_pan',
            'bank_branch_ifsc',
            '"*redacted*" AS website_contact',
            'business_pan_url',
            'role',
            'poa_verification_status',
            'kyc_id',
            'virtual_accounts_activation_status',
            'authorized_signatory_dob',
            'business_description',
            'business_registered_state',
            'business_operation_city',
            '"*redacted*" AS company_pan',
            '"*redacted*" AS promoter_pan_name',
            '"*redacted*" AS bank_beneficiary_address1',
            'website_privacy',
            '"*redacted*" AS address_proof_url',
            'department',
            'bank_details_verification_status',
            'archived_at',
            'subscriptions_activation_status',
            'platform',
            'merchant_id',
            'business_dba',
            'business_registered_city',
            'business_operation_district',
            'company_pan_name',
            'date_of_birth',
            '"*redacted*" AS bank_beneficiary_address2',
            'website_terms',
            'promoter_proof_url',
            'comment',
            'activation_flow',
            'reviewer_id',
            'steps_finished'
        ];

        $columnStr = implode(',', $columns);

        $view = DB::getConfig('view_db') . '.masked_merchant_details_view';

        $statement = 'CREATE ALGORITHM=MERGE VIEW ' . wrap_db_table($view) . ' AS
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
        DB::statement('DROP VIEW IF EXISTS masked_merchant_details_view');
    }
}
