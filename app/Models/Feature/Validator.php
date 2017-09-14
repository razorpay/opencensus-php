<?php

namespace RZP\Models\Feature;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use Illuminate\Http\Request;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::NAME        => 'required|string|max:25|custom'
    );

    protected static $onboardingRules = array(
        Constants::MARKETPLACE                                     => 'sometimes|array|max:3',
        Constants::MARKETPLACE . "." . Constants::USE_CASE         => 'sometimes|string',
        Constants::MARKETPLACE . "." . Constants::SETTLING_TO      => 'sometimes|string',
        Constants::MARKETPLACE . "." . Constants::VENDOR_AGREEMENT => 'sometimes|file',

        Constants::SUBSCRIPTIONS                                    => 'sometimes|array|max:3',
        Constants::SUBSCRIPTIONS . "." . Constants::BUSINESS_MODEL  => 'sometimes|string',
        Constants::SUBSCRIPTIONS . "." . Constants::SAMPLE_PLANS    => 'sometimes|string',
        Constants::SUBSCRIPTIONS . "." . Constants::WEBSITE_DETAILS => 'sometimes|string|max:50',

        Constants::VIRTUAL_ACCOUNTS                                             => 'sometimes|array|max:2',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::USE_CASE                 => 'sometimes|string',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::EXPECTED_MONTHLY_REVENUE => 'sometimes|string',
    );

    protected function validateName($attribute, $value)
    {
        $allFeatures = array_keys(Constants::$featureValueMap);

        if (in_array($value, $allFeatures) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid feature',
                $attribute,
                $value);
        }
   }

   public function validateZoho(Request $request)
   {
        if (Merchant\Preferences::checkZohoHeaders($request->headers) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Payment failed');
        }
   }
}
