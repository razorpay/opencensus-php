<?php

namespace RZP\Models\Merchant;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
    const STEP_MAP = [
        'contact_name'                => 1,
        'contact_email'               => 1,
        'transaction_report_email'    => 1,
        'contact_mobile'              => 1,
        'contact_landline'            => 1,

        'business_type'               => 2,
        'business_name'               => 2,
        'business_dba'                => 2,
        'business_international'      => 2,
        'business_paymentdetails'     => 2,
        'business_model'              => 2,
        'business_registered_address' => 2,
        'business_registered_state'   => 2,
        'business_registered_city'    => 2,
        'business_registered_pin'     => 2,
        'business_operation_address'  => 2,
        'business_operation_state'    => 2,
        'business_operation_city'     => 2,
        'business_operation_pin'      => 2,
        'business_doe'                => 2,
        'transaction_volume'          => 2,
        'transaction_value'           => 2,
        'gstin'                       => 2,
        'p_gstin'                     => 2,
        'promoter_pan'                => 2,
        'promoter_pan_name'           => 2,

        'business_website'            => 3,
        'website_about'               => 3,
        'website_contact'             => 3,
        'website_privacy'             => 3,
        'website_terms'               => 3,
        'website_refund'              => 3,
        'website_pricing'             => 3,

        'bank_branch_ifsc'            => 4,
        'bank_account_number'         => 4,
        'bank_account_type'           => 4,
        'bank_account_name'           => 4,
        'bank_beneficiary_address1'   => 4,
        'bank_beneficiary_address2'   => 4,
        'bank_beneficiary_address3'   => 4,
        'bank_beneficiary_city'       => 4,
        'bank_beneficiary_state'      => 4,
        'bank_beneficiary_pin'        => 4,

        'business_proof_url'          => 5,
        'business_pan_url'            => 5,
        'address_proof_url'           => 5,
        'promoter_address_url'        => 5,
    ];

    const STEP_MAP_ACCOUNT = [
        'business_type'               => 1,
        'business_name'               => 1,
        'company_pan'                 => 1,
        'promoter_pan'                => 1,

        'bank_branch_ifsc'            => 2,
        'bank_account_number'         => 2,
        'bank_account_type'           => 2,
        'bank_account_name'           => 2,

        'address_proof_url'           => 3,
        'promoter_pan_url'            => 3,
    ];
}