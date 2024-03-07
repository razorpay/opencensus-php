<?php

namespace RZP\Models\Upi\Turbo;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static array $customerRecordConsentRules = [
        Constants::TYPE                                       => 'required|string|in:upi_turbo_prefetch',
        Constants::MESSAGE                                    => 'required|string',
        Constants::CUSTOMER_IDENTIFIER_TYPE                   => 'required|string',
        Constants::CUSTOMER_IDENTIFIER_VALUE                  => 'required|string',
        Constants::ACKNOWLEDGE                                => 'required|bool',
        Constants::TIMESTAMP                                  => 'required|epoch',
        Constants::METADATA                                   => 'required|array',
        Constants::METADATA . '.' . Constants::PREFETCH_BANK  => 'required|array',
        Constants::METADATA . '.' . Constants::PREFETCH_BANK . '.*.' . Constants::PRIORITY      => 'required|string',
        Constants::METADATA . '.' . Constants::PREFETCH_BANK . '.*.' . Constants::IIN           => 'required|string',
        Constants::METADATA . '.' . Constants::PREFETCH_BANK . '.*.' . Constants::DISPLAY_NAME  => 'required|string',
        Constants::METADATA . '.' . Constants::PREFETCH_BANK . '.*.' . Constants::BANK_LOGO     => 'required|string'
    ];
}
