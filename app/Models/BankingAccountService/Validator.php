<?php

namespace RZP\Models\BankingAccountService;

use RZP\Base;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;

class Validator extends Base\Validator
{
    const BVS_INITIATE_VALIDATION   = 'bvs_initiate_validation';
    const ARCHIVE_BANKING_ACCOUNT   = 'archive_banking_account';
    const UNARCHIVE_BANKING_ACCOUNT = 'unarchive_banking_account';

    const HANDLE_NOTIFICATION_VALIDATION = 'handle_notification_validation';

    const SUPPORTED_ARTEFACT_TYPE = [
      Constant::BUSINESS_PAN,
      Constant::PERSONAL_PAN
    ];

    protected static $createRules = [
        Constants::CHANNEL        => 'required|string|custom',
        Constants::ACCOUNT_NUMBER => 'required|string',
    ];

    protected static $businessRules = [
        Constants::BUSINESS_ID => 'required|string|max:14',
    ];

    protected static $bvsInitiateValidationRules = [
        Constant::ARTEFACT_TYPE     => 'required|string|custom:artefactType',
        Constant::OWNER_TYPE        => 'required|string|in:bas_document',
        Constant::OWNER_ID          => 'required|string',
        Constant::DETAILS           => 'required|array',
    ];

    protected static $handleNotificationValidationRules = [
        Constants::NOTIFICATION_TYPE                  => 'required|string',
        Constants::VALIDATOR_OP                       => 'required_if:notification_type,x_pro_activation',
        Constants::BANKING_ACCOUNT                    => 'required|array',
        Constants::BANKING_ACCOUNT_STATUS_CHANGED     => 'required_if:notification_type,status_change',
        Constants::BANKING_ACCOUNT_SUB_STATUS_CHANGED => 'required_if:notification_type,status_change',
        'banking_account.id'                                 => 'required|string',
        'banking_account.merchant_id'                        => 'required|string',
        'banking_account.bank_reference_number'              => 'required|string',
        'banking_account.created_at'                         => 'required|epoch',
        'banking_account.pincode'                            => 'required|string',
        'banking_account.status'                             => 'required|string',
        'banking_account.sub_status'                         => 'sometimes',
        'banking_account.spocs'                              => 'required_if:status,api_onboarding,account_activation,processed,rejected,archived,activated',
        'banking_account.reviewers'                          => 'required_if:status,api_onboarding,account_activation,processed',
        'banking_account.banking_account_activation_details' => 'required|array',
        'banking_account.banking_account_activation_details.assignee_name'      => 'required_if:notification_type,x_pro_activation|string',
        'banking_account.banking_account_activation_details.additional_details' => 'sometimes|array',
        'banking_account.banking_account_activation_details.contact_verified'   => 'required_if:notification_type,status_change|integer',
        'banking_account.banking_account_activation_details.sales_team'         => 'required_if:notification_type,x_pro_activation|string',
    ];

    protected static $archiveBankingAccountRules = [
        Constant::MERCHANT_ID     => 'required|string|max:14',
        Constants::BALANCE_ID     => 'required|string|max:14',
    ];

    protected static $unarchiveBankingAccountRules = [
        Constant::MERCHANT_ID     => 'required|string|max:14',
        Constants::BUSINESS_ID    => 'required|string|max:14',
        Constants::PARTNER_BANK   => 'required|string|in:RBL,ICICI',
    ];

    protected function validateArtefactType($attribute, $artefactType): bool
    {
        return in_array($artefactType, self::SUPPORTED_ARTEFACT_TYPE);
    }

    protected function validateChannel($attribute, $channel)
    {
        Channel::isValidDirectTypeChannel($channel);
    }
}
