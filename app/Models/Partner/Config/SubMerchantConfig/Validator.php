<?php

namespace RZP\Models\Partner\Config\SubMerchantConfig;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail\BusinessType;
use RZP\Models\Partner\Config\Constants;
use RZP\Models\Partner\Config\Entity;

class Validator extends Base\Validator
{
    /**
     * Checks for valid attribute and parameters for partner sub merchant config input
     *
     * @param array           $input
     *
     * @throws Exception\BadRequestException
     */

    public function validatePartnersSubmerchantConfigInput(array $input)
    {
        $attributeName = $input[Constants::ATTRIBUTE_NAME];

        $parameters = $input[Constants::PARAMETERS];

        $attributeParameterMap = Constants::attributesParamsMap;

        if(in_array($attributeName, Constants::attributes, true) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                Constants::ATTRIBUTE_NAME,
                $input);
        }

        foreach($parameters as $key => $value)
        {
            if(in_array($key, $attributeParameterMap[$attributeName], true) === false)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                    $key,
                    $input);
            }
            $this->validateParameters($key,$value);
        }
    }

    public function validateParameters(string $parameterName,string $parameterValue )
    {
        switch ($parameterName)
        {
            case Constants::BUSINESS_TYPE:
                if(BusinessType::isValidBusinessType($parameterValue) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                        Constants::BUSINESS_TYPE,
                       [ $parameterName,$parameterValue]);
                }
                break;

        }
    }
}
