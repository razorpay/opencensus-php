<?php

namespace RZP\Models\Feature;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use Illuminate\Http\Request;
use RZP\Models\Base\PublicEntity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::NAME        => 'required|string|max:25|custom'
    );

    protected static $onboardingRules = array(
        Constants::MARKETPLACE                                     => 'filled|array|max:3',
        Constants::MARKETPLACE . "." . Constants::USE_CASE         => 'filled|string',
        Constants::MARKETPLACE . "." . Constants::SETTLING_TO      => 'filled|string',
        Constants::MARKETPLACE . "." . Constants::VENDOR_AGREEMENT => 'sometimes|file',

        Constants::SUBSCRIPTIONS                                    => 'filled|array|max:3',
        Constants::SUBSCRIPTIONS . "." . Constants::BUSINESS_MODEL  => 'filled|string',
        Constants::SUBSCRIPTIONS . "." . Constants::SAMPLE_PLANS    => 'filled|string',
        Constants::SUBSCRIPTIONS . "." . Constants::WEBSITE_DETAILS => 'filled|string|max:50',

        Constants::VIRTUAL_ACCOUNTS                                             => 'filled|array|max:2',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::USE_CASE                 => 'filled|string',
        Constants::VIRTUAL_ACCOUNTS . "." . Constants::EXPECTED_MONTHLY_REVENUE => 'filled|string',
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

   /**
    * Validates that the feature is not in already assigned list of merchant
    * features.
    *
    * @param array $assignedFeatureNames
    *
    * @throws Exception\BadRequestException
    */
   public function validateFeatureIsNotAlreadyAssigned(array $assignedFeatureNames)
   {
        $feature = $this->entity;

        $name = $feature->getName();

        if (in_array($name, $assignedFeatureNames, true) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED,
                Entity::FEATURE,
                [
                    Entity::ID                => $feature->getId(),
                    Entity::NAME              => $feature->getName(),
                    Entity::OLD_FEATURES      => $assignedFeatureNames,
                    PublicEntity::MERCHANT_ID => $feature->getMerchantId(),
                ]);
        }
   }
}
