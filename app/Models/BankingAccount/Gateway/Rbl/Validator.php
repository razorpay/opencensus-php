<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Base;
use RZP\Models\Pincode;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const ACCOUNT_INFO_WEBHOOK        = 'account_info_webhook';
    const PRE_ACCOUNT_INFO_WEBHOOK    = 'pre_account_info_webhook';
    const ACCOUNT_UPDATE              = 'account_update';
    const ACCOUNT_AVAILABILITY        = 'availability';
    const ADD_CREDENTIALS             = 'add_credentials';

    protected static $availabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required|custom',
    ];

    protected static $preAccountInfoWebhookRules = [
        Fields::RZP_ALERT_NOTIFICATION_REQUEST                                                  => 'required',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::BODY                             => 'required|array',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::HEADER . '.' . Fields::TRAN_ID   => 'required|string',
    ];

    protected static $accountInfoWebhookRules = [
        Fields::ACCT_NAME       => 'required|string',
        Fields::FORACID         => 'required|string|max:40',
        Fields::IFSC            => 'required|alpha_num|size:11',
        Fields::PINCODE         => 'required|integer|digits:6',
        Fields::ADDR_1          => 'required|string',
        Fields::ADDR_2          => 'required|string',
        Fields::ADDR_3          => 'required|string',
        Fields::CIF_ID          => 'required|string',
        Fields::CITY            => 'required|string',
        Fields::STATE           => 'required|string',
        Fields::COUNTRY         => 'required|string',
        Fields::REF_NUM_1       => 'required|string|size:5',
        Fields::ACTIVATION_DATE => 'required|string',
        Fields::PHONE_NUM       => 'required|string',
        Fields::EMAIL_ID        => 'required|email',
    ];

    protected static $accountUpdateRules = [
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_INTERNAL_STATUS            => 'required_if:status,processing,processed,cancelled|string|custom',
        Entity::BANK_REFERENCE_NUMBER           => 'filled|string|size:5',
        Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
    ];

    // ToDO add proper validations here after confirming with RBL
    protected static $addCredentialsRules = [
        Fields::SUBCORP_ID               => 'required|string',
        Fields::SUBCORP_USER_ID          => 'required|string',
        Fields::SUBCORP_USER_PASSWORD    => 'required|string',
    ];

    protected function validateStatus(string $attribute, string $status = null)
    {
        \RZP\Models\BankingAccount\Status::isValidStatus($status);
    }

    protected function validateBankInternalStatus(string $attribute, string $bankInternalStatus = null)
    {
        Status::validate($bankInternalStatus);
    }

    protected function validatePincode(string $attribute, string $pincode)
    {
        $pincodeValidator = new Pincode\Validator(Pincode\Pincode::IN);

        if ($pincodeValidator->validate($pincode) === false)
        {
            throw new BadRequestValidationFailureException(
                'Pincode is not valid',
                Entity::PINCODE,
                [
                    Entity::PINCODE => $pincode,
                ]
            );
        }
    }
}
