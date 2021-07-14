<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Base;
use RZP\Models\Pincode;
use RZP\Models\BankingAccount;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const ACCOUNT_INFO_WEBHOOK        = 'account_info_webhook';
    const PRE_ACCOUNT_INFO_WEBHOOK    = 'pre_account_info_webhook';
    const ACCOUNT_UPDATE              = 'account_update';
    const ACCOUNT_AVAILABILITY        = 'availability';
    const ACCOUNT_DETAILS_UPDATE      = 'account_details_update';
    const ACCOUNT_ACTIVATE            = 'account_activate';

    protected static $availabilityRules = [
        BankingAccount\Entity::CHANNEL => 'required|string|in:rbl',
        BankingAccount\Entity::PINCODE => 'required|custom',
    ];

    protected static $preAccountInfoWebhookRules = [
        Fields::RZP_ALERT_NOTIFICATION_REQUEST                                                  => 'required',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::BODY                             => 'required|array',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::HEADER . '.' . Fields::TRAN_ID   => 'required|string',
    ];

    protected static $accountInfoWebhookRules = [
        Fields::CUSTOMER_NAME          => 'required|string',
        Fields::ACCOUNT_NO             => 'required|string|max:40',
        Fields::IFSC                   => 'required|alpha_num|size:11',
        Fields::PINCODE                => 'required|integer|digits:6',
        Fields::ADDR_1                 => 'required|string',
        Fields::ADDR_2                 => 'required|string',
        Fields::ADDR_3                 => 'required|string',
        Fields::CUSTOMER_ID            => 'required|string',
        Fields::CITY                   => 'required|string',
        Fields::STATE                  => 'required|string',
        Fields::COUNTRY                => 'required|string',
        Fields::RZP_REFERENCE_NUMBER   => 'required|string|size:5',
        Fields::ACTIVATION_DATE        => 'required|date',
        Fields::PHONE_NO               => 'required|string',
        Fields::EMAIL_ID               => 'required|email',
    ];

    protected static $accountUpdateRules = [
        BankingAccount\Entity::BANK_INTERNAL_STATUS            => 'filled|string|custom',
        BankingAccount\Entity::BANK_REFERENCE_NUMBER           => 'filled|string|size:5',
        BankingAccount\Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
    ];

    protected static $accountDetailsUpdateRules = [
        Fields::MERCHANT_PASSWORD => 'sometimes|string',
        Fields::MERCHANT_EMAIL    => 'sometimes|email',
        Fields::CLIENT_ID         => 'sometimes|string',
        Fields::CLIENT_SECRET     => 'sometimes|string',
        Fields::BCAGENT           => 'sometimes|string',
        Fields::BCAGENT_USERNAME  => 'sometimes|string',
        Fields::BCAGENT_PASSWORD  => 'sometimes|string',
        Fields::HMAC_KEY          => 'sometimes|string',
        Fields::PAYER_VPA         => 'sometimes|string',
        Fields::MRCH_ORG_ID       => 'sometimes|string',
        Fields::AGGR_ORG_ID       => 'sometimes|string',
    ];

    public static $accountActivateRules = [
        BankingAccount\Entity::ACCOUNT_NUMBER       => 'required|string',
        BankingAccount\Entity::ACCOUNT_IFSC         => 'required|string',
        BankingAccount\Entity::USERNAME             => 'required|string',
        BankingAccount\Entity::PASSWORD             => 'required|string',
        BankingAccount\Entity::REFERENCE1           => 'required|string',
        BankingAccount\Entity::BENEFICIARY_NAME     => 'required|string',
        BankingAccount\Entity::BENEFICIARY_EMAIL    => 'required|email',
        BankingAccount\Entity::BENEFICIARY_MOBILE   => 'required|string',
        BankingAccount\Entity::BENEFICIARY_STATE    => 'required|string',
        BankingAccount\Entity::BENEFICIARY_COUNTRY  => 'required|string',
        BankingAccount\Entity::BENEFICIARY_ADDRESS1 => 'required|string',
        BankingAccount\Entity::ACCOUNT_TYPE         => 'required|string',
        Fields::CLIENT_ID                           => 'required|string',
        Fields::CLIENT_SECRET                       => 'required|string',
    ];

    protected function validateStatus(string $attribute, string $status = null)
    {
        BankingAccount\Status::isValidStatus($status);
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
                BankingAccount\Entity::PINCODE,
                [
                    BankingAccount\Entity::PINCODE => $pincode,
                ]
            );
        }
    }
}
