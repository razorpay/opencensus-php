<?php

namespace RZP\Models\Merchant\Account;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Merchant;

class Validator extends Merchant\Validator
{
     protected static $createRules = [
        Entity::NAME                        => 'required|alpha_space_num|max:200',
        Entity::EMAIL                       => 'required|email',
    ];

    protected static $filesRules = [
        FileType::BUSINESS_PROOF            => 'sometimes|file',
        FileType::BUSINESS_PAN              => 'sometimes|file',
        FileType::BUSINESS_OPERATION_PROOF  => 'sometimes|file',
        FileType::ADDRESS_PROOF             => 'sometimes|file',
        FileType::PROMOTER_PROOF            => 'sometimes|file',
        FileType::PROMOTER_PAN              => 'sometimes|file',
        FileType::PROMOTER_ADDRESS          => 'sometimes|file',
    ];

    protected static $updateDetailsRules = [
        'contact'                           => 'sometimes|array',
        'contact.name'                      => 'sometimes|string',
        'contact.email'                     => 'sometimes|email',
        'contact.mobile'                    => 'sometimes|string',
        'contact.landline'                  => 'sometimes|string',
        'business'                          => 'sometimes|array',
        'business.type'                     => 'sometimes|string',
        'business.name'                     => 'sometimes|string',
        'business.dba'                      => 'sometimes|string',
        'business.website'                  => 'sometimes|url',
        'business.international'            => 'sometimes|string',
        'business.paymentdetails'           => 'sometimes|string',
        'business.model'                    => 'sometimes|string',
        'company'                           => 'sometimes|array',
        'company.cin'                       => 'sometimes|string',
        'company.pan'                       => 'sometimes|string',
        'company.pan_name'                  => 'sometimes|string',
        'transaction'                       => 'sometimes|array',
        'transaction.volume'                => 'sometimes',
        'transaction.value'                 => 'sometimes',
        'promoter'                          => 'sometimes|array',
        'promoter.pan'                      => 'sometimes|string',
        'promoter.pan_name'                 => 'sometimes|string',
        'promoter'                          => 'sometimes|array',
        'bank'                              => 'sometimes|array',
        'website'                           => 'sometimes|array',
        'submit'                            => 'sometimes'
    ];
}
