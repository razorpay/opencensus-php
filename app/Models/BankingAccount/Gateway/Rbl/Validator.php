<?php

namespace RZP\Models\BankingAccount\Gateway\Rbl;

use RZP\Base;
use RZP\Models\BankingAccount\Entity;

class Validator extends Base\Validator
{
    const ACCOUNT_INFO_WEBHOOK        = 'account_info_webhook';
    const PRE_ACCOUNT_INFO_WEBHOOK    = 'pre_account_info_webhook';
    const ACCOUNT_UPDATE              = 'account_update';
    const ACCOUNT_AVAILABILITY        = 'availability';

    protected static $preAccountInfoWebhookRules = [
        Fields::RZP_ALERT_NOTIFICATION_REQUEST                                                  => 'required',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::BODY                             => 'required|array',
        Fields::RZP_ALERT_NOTIFICATION_REQUEST . '.' . Fields::HEADER . '.' . Fields::TRAN_ID   => 'required|string',
    ];

    protected static $accountInfoWebhookRules = [
        Fields::ACCT_NAME       => 'required|string',
        Fields::FORACID         => 'required|string',
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
        Fields::EMAIL_ID        => 'required|string',
    ];

    protected static $accountUpdateRules = [
        Entity::ACCOUNT_NUMBER                  => 'required_with:account_ifsc|max:40',
        Entity::ACCOUNT_IFSC                    => 'required_with:account_number|size:11',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_INTERNAL_STATUS            => 'required_if:status,processing,processed,cancelled|string|custom',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_REFERENCE_NUMBER           => 'filled|string|size:5',
        Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
        Entity::PINCODE                         => 'filled|integer|digits:6',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'filled|string|date',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
    ];

    protected static $availabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected function validateStatus(string $attribute, string $status = null)
    {
        \RZP\Models\BankingAccount\Status::isValidStatus($status);
    }

    protected function validateBankInternalStatus(string $attribute, string $bankInternalStatus = null)
    {
        Status::validate($bankInternalStatus);
    }
}
