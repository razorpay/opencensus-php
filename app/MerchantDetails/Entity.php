<?php

namespace App\MerchantDetails;

use App\Base;

class Entity extends Base\Entity
{
    protected $table = 'merchant_details';

    protected $primaryKey = 'merchant_id';

    public $incrementing = false;

    protected $fillable = array(
        'merchant_id',
        'contact_name',
        'contact_email',
        'contact_mobile',
        'contact_landline',
        'business_type',
        'business_name',
        'business_dba',
        'business_international',
        'business_paymentdetails',
        'business_registered_address',
        'business_registered_state',
        'business_registered_city',
        'business_registered_pin',
        'business_operation_address',
        'business_operation_state',
        'business_operation_city',
        'business_operation_pin',
        'promoter_pan',
        'promoter_pan_name',
        'business_doe',
        'gstin',
        'p_gstin',
        'company_cin',
        'company_pan',
        'company_pan_name',
        'business_model',
        'transaction_volume',
        'transaction_value',
        'business_website',
        'website_about',
        'website_contact',
        'website_privacy',
        'website_terms',
        'website_refund',
        'website_pricing',
        'website_login',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'bank_account_type',
        'bank_branch',
        'bank_branch_ifsc',
        'bank_beneficiary_address1',
        'bank_beneficiary_address2',
        'bank_beneficiary_address3',
        'bank_beneficiary_city',
        'bank_beneficiary_state',
        'bank_beneficiary_pin',
        'business_proof_url',
        'business_operation_proof_url',
        'business_pan_url',
        'address_proof_url',
        'promoter_proof_url',
        'promoter_pan_url',
        'promoter_address_url',
        'steps_finished',
        'submitted',
        'locked',
        'comment',
        'submitted_at',
        'transaction_report_email',
        'role',
        'department'
    );

    const UPLOAD_KEYS = [
        'business_proof'           => 'business_proof_url',
        'business_operation_proof' => 'business_operation_proof_url',
        'business_pan_proof'       => 'business_pan_url',
        'address_proof'            => 'address_proof_url',
        'promoter_proof'           => 'promoter_proof_url',
        'promoter_pan_proof'       => 'promoter_pan_url',
        'promoter_address_proof'   => 'promoter_address_url'
    ];

    public function getBillingLabel()
    {
        return $this->getAttribute('business_dba');
    }
}
