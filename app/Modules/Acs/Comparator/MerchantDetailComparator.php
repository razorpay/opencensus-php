<?php

namespace RZP\Modules\Acs\Comparator;

class MerchantDetailComparator extends Base
{
    protected $excludedKeys = [
        "created_at" => true,
        "updated_at" => true,
        "business_international" => true,
        "business_paymentdetails" => true,
        "promoter_pan" => true,
        "promoter_pan_name" => true,
        "transaction_report_email" => true,
        "steps_finished" => true,
        "activation_progress" => true,
        "locked" => true,
        "activation_status" => true,
        "poi_verification_status" => true,
        "poa_verification_status" => true,
        "bank_details_verification_status" => true,
        "activation_flow" => true,
        "international_activation_flow" => true,
        "custom_fields->tos_acceptance" => true,
        "submitted" => true,
        "submitted_at" => true,
        "company_pan_verification_status" => true,
        "company_pan_doc_verification_status" => true,
        "activation_form_milestone" => true
    ];

    function __construct()
    {
        parent::__construct();
    }
}
