<?php

namespace RZP\Models\Upi\Turbo;

use RZP\Base;
use RZP\Models\Upi\Turbo\RewardProcessor\Base as RPBase;

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

    protected static array $credRewardEligibilityRules = [
        RPBase::CONTACT                         => 'required|string|regex:/^[0-9]{10}$/',
        RPBase::ACTION                          => 'required|string|in:onboarding,payment',
        RPBase::DATA                        => 'sometimes|array',
        RPBase::DATA . '.' . RPBase::AMOUNT => 'sometimes|required_if:action,payment'
    ];

    protected static array $credRewardAllotmentRules = [
        RPBase::CONTACT                                 => 'required|string|regex:/^[0-9]{10}$/',
        RPBase::ACTION                                  => 'required|string|in:onboarding,payment',
        RPBase::DATA                                => 'required|array',
        RPBase::DATA . '.' . RPBase::PAYMENT_ID     => 'required_if:action,payment|size:18|string',
        RPBase::DATA . '.' . RPBase::SDK_SESSION_ID => 'required_if:action,onboarding|string',
    ];
}
