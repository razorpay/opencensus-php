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
