<?php

namespace RZP\Models\Payout\Configurations\DirectAccounts\PayoutModeConfig;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    const CREATE_PAYOUT_MODE_CONFIG        = 'create_payout_mode_config';

    const PAYOUT_MODE_CONFIG_FIELDS_CREATE = 'payout_mode_config_fields_create';

    const EDIT_PAYOUT_MODE_CONFIG          = 'edit_payout_mode_config';

    const PAYOUT_MODE_CONFIG_FIELDS_EDIT   = 'payout_mode_config_fields_edit';

    const FETCH_PAYOUT_MODE_CONFIG         = 'fetch_payout_mode_config';

    protected static $createPayoutModeConfigRules = [
        Constants::MERCHANT_ID => 'required|string',
        Constants::FIELDS      => 'required|array|custom:create_fields',
    ];

    protected static $payoutModeConfigFieldsCreateRules = [
        Constants::ALLOWED_UPI_CHANNELS => 'required|array'
    ];

    protected static $editPayoutModeConfigRules = [
        Constants::MERCHANT_ID => 'required|string',
        Constants::FIELDS      => 'required|array|custom:edit_fields',
    ];

    protected static $payoutModeConfigFieldsEditRules = [
        Constants::ALLOWED_UPI_CHANNELS => 'required|array'
    ];

    protected static $fetchPayoutModeConfigRules = [
        Constants::MERCHANT_ID => 'required|string',
    ];

    protected function validateCreateFields($attribute, $fields)
    {
        $this->validateInput(self::PAYOUT_MODE_CONFIG_FIELDS_CREATE, $fields);
    }

    protected function validateEditFields($attribute, $fields)
    {
        if(!array_key_exists(Constants::ALLOWED_UPI_CHANNELS, $fields)) {
            throw new Exception\BadRequestValidationFailureException(
                'Required parameter ' . Constants::ALLOWED_UPI_CHANNELS . ' is not Provided');
        }

        $this->validateInput(self::PAYOUT_MODE_CONFIG_FIELDS_EDIT, $fields);
    }
}
