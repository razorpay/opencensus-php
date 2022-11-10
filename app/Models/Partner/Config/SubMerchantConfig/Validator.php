<?php

namespace RZP\Models\Partner\Config\SubMerchantConfig;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\AccountV2;
use RZP\Models\Partner\Config\Entity;
use RZP\Models\Partner\Config\Constants;
use RZP\Models\Merchant\Detail\BusinessType;

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

        $partnerId = $input[Constants::PARTNER_ID];

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
            $this->validateParameters($key, $value, $partnerId);
        }
    }

    public function validateParameters(string $parameterName,string $parameterValue, string $partnerId)
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
            case Constants::SET_FOR:
                if(in_array($parameterValue, Constants::gmvLimitSetFor, true) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                        Constants::SET_FOR,
                        [ $parameterName,$parameterValue]);
                }

                $this->validatePartnerIfApplicable($parameterValue, $partnerId);
                break;
        }
    }

    public function validatePartnerIfApplicable(string $parameterValue, string $partnerId)
    {
        switch ($parameterValue)
        {
            case Constants::NO_DOC_SUBMERCHANTS:
                if((new AccountV2\Core())->isSubmNoDocOnboardingEnabledForMid($partnerId) === false)
                {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_SUBM_NO_DOC_ONBOARDING_NOT_ENABLED_FOR_PARTNER,
                        Constants::PARTNER_ID,
                        $partnerId);
                }
        }
    }

    /**
     * Checks for valid attribute and parameters for partner bulk update onboarding source input
     *
     * @param array           $input
     *
     * @throws Exception\BadRequestException
     */
    public function validateBulkOnboardingSourceUpdateInput(array $input)
    {

        $merchantIds = $input[Constants::MERCHANT_IDS];

        $onboardingSourece = $input[Constants::ONBOARDING_SOURCE];

        if(empty($merchantIds) === true or empty($onboardingSourece) === true)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_CONFIGURATION_INVALID,
                Constants::MERCHANT_IDS,
                $input);
        }
    }
}
