<?php

namespace RZP\Models\Feature;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant;
use Illuminate\Http\Request;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::ENTITY_ID   => 'required|string|max:20',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::NAME        => 'required|string|max:25|custom'
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
                null,
                [
                    Entity::ID                => $feature->getId(),
                    Entity::NAME              => $feature->getName(),
                    Entity::OLD_FEATURES      => $assignedFeatureNames,
                    PublicEntity::MERCHANT_ID => $feature->getMerchantId(),
                ]);
        }
   }
}
