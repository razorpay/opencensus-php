<?php

namespace RZP\Models\EMandate;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    public function validateDebitGateway($gateway)
    {
        if (Gateway::isFileBasedEMandateDebitGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid eMandate debit gateway. ' . $gateway);
        }
    }
    
    public function validateGetEmandateConfigs(array $merchantIds)
    {
        if(count($merchantIds) >= 50)
        {
            throw new Exception\BadRequestValidationFailureException(
                "merchant_ids count shouldn't be greater than 50. Presently
                " . count($merchantIds) . " merchant_ids are provided. Please provide 50 or less.");
        }
    }
    
    public function validateCreateEmandateConfigs(array $input)
    {
        $requiredFields = [
            Constants::MERCHANT_IDS,
            Constants::RETRY_ATTEMPTS,
            Constants::COOLDOWN_PERIOD,
            Constants::TEMPORARY_ERRORS_ENABLE_FLAG
        ];
    
        if(count($input[Constants::MERCHANT_IDS]) >= 50)
        {
            throw new Exception\BadRequestValidationFailureException(
                "merchant_ids count shouldn't be greater than 50. Presently
                " . count($input[Constants::MERCHANT_IDS]) . " merchant_ids are provided. Please provide 50 or less.");
        }
        
        foreach ($requiredFields as $key)
        {
            if(!array_key_exists($key, $input)) {
                throw new Exception\BadRequestValidationFailureException(
                    'Required parameter ' . $key . ' is not Provided');
            }
        }
    }
    
    public function validateEditEmandateConfigs(array $input)
    {
        $optionalFields = [
            Constants::RETRY_ATTEMPTS,
            Constants::COOLDOWN_PERIOD,
            Constants::TEMPORARY_ERRORS_ENABLE_FLAG
        ];
    
        if(count($input[Constants::MERCHANT_IDS]) >= 50)
        {
            throw new Exception\BadRequestValidationFailureException(
                "merchant_ids count shouldn't be greater than 50. Presently
                " . count($input[Constants::MERCHANT_IDS]) . " merchant_ids are provided. Please provide 50 or less.");
        }
        
        if(!array_key_exists(Constants::MERCHANT_IDS, $input)) {
            throw new Exception\BadRequestValidationFailureException(
                'Required parameter ' . Constants::MERCHANT_IDS . ' is not Provided');
        }
    
        foreach ($optionalFields as $key)
        {
            if(array_key_exists($key, $input)) {
                return;
            }
        }
    
        throw new Exception\BadRequestValidationFailureException(
            'One Of the these parameters ' . implode(",", $optionalFields) . ' should be Provided');
    }
}
