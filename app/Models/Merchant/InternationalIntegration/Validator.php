<?php

namespace RZP\Models\Merchant\InternationalIntegration;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::INTEGRATION_ENTITY     => 'required|string',
        Entity::INTEGRATION_KEY        => 'required|string',
        Entity::NOTES                  => 'sometimes',
        Entity::BANK_ACCOUNT           => 'sometimes',
        Entity::REFERENCE_ID           => 'sometimes',
        Entity::PAYMENT_METHODS        => 'sometimes'
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::INTEGRATION_ENTITY     => 'required|string',
        Entity::INTEGRATION_KEY        => 'required|string',
        Entity::NOTES                  => 'sometimes',
        Entity::BANK_ACCOUNT           => 'sometimes',
        Entity::REFERENCE_ID           => 'sometimes',
        Entity::PAYMENT_METHODS        => 'sometimes'
    ];

    protected static $postEmerchantpayRequestDataRules = [
        'instruments'                                           => 'sometimes|array',
        'merchant_info'                                         => 'sometimes|array',
        'merchant_info.merchant_international_integration_id'   => 'sometimes|string|size:14',
        'merchant_info.service_offered'                         => 'sometimes|string',
        'merchant_info.average_delivery_in_days'                => 'sometimes|integer',
        'merchant_info.registration_number'                     => 'sometimes|string',
        'merchant_info.gst_number'                              => 'sometimes|string|size:15',
        'merchant_info.date_of_incorporation'                   => 'sometimes|date_format:"Y-m-d"|before:"today"',
        'merchant_info.physical_delivery'                       => 'sometimes|string|in:yes,no',
        'merchant_info.purpose_code'                            => 'sometimes|string|max:5',
        'merchant_info.iec_code'                                => 'sometimes|string|max:20',
        'merchant_info.address_line1'                           => 'sometimes|string|max:255',
        'merchant_info.address_line2'                           => 'sometimes|string|max:255',
        'merchant_info.city'                                    => 'sometimes|string|max:64',
        'merchant_info.zipcode'                                 => 'sometimes|string|max:16',
        'merchant_info.state'                                   => 'sometimes|string|max:64',
        'merchant_info.country'                                 => 'sometimes|string|max:64',
        'merchant_info.documents'                               => 'sometimes|array',
        'owner_details'                                         => 'sometimes|array',
        'owner_details.*.id'                                    => 'sometimes|string|size:14',
        'owner_details.*.first_name'                            => 'sometimes|string|max:255',
        'owner_details.*.last_name'                             => 'sometimes|string|max:255',
        'owner_details.*.position'                              => 'sometimes|string|max:45',
        'owner_details.*.ownership_percentage'                  => 'sometimes|numeric|between:0.01,100|regex:/^\d+(\.\d{1,2})?$/',
        'owner_details.*.date_of_birth'                         => 'sometimes|date_format:"Y-m-d"|before:"today"',
        'owner_details.*.passport_number'                       => 'sometimes|string|regex:/^[A-Za-z]{1}[0-9]{7}$/',
        'owner_details.*.aadhaar_number'                        => 'sometimes|string|regex:/^[2-9]{1}[0-9]{3}\s{0,1}[0-9]{4}\s{0,1}[0-9]{4}$/',
        'owner_details.*.pan_number'                            => 'sometimes|string|regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]{1}$/',
        'owner_details.*.address_line1'                         => 'sometimes|string|max:255',
        'owner_details.*.address_line2'                         => 'sometimes|string|max:255',
        'owner_details.*.city'                                  => 'sometimes|string|max:64',
        'owner_details.*.zipcode'                               => 'sometimes|string|max:16',
        'owner_details.*.state'                                 => 'sometimes|string|max:64',
        'owner_details.*.country'                               => 'sometimes|string|max:64',
        'owner_details.*.documents'                             => 'sometimes|array',
        'submitted'                                             => 'sometimes|boolean'
    ];
}
