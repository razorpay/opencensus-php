<?php

namespace RZP\Models\Batch\Helpers;

use Illuminate\Support\Arr;
use RZP\Models\Batch\Header;
use RZP\Models\Batch\Constants;
use RZP\Models\User\Entity as User;
use RZP\Models\Merchant\Entity as Merchant;
use RZP\Models\Merchant\Detail\Entity as MDEntity;

class SubMerchant
{
    /**
     * Returns input for sub merchant creation
     *
     * @param  array $entry
     * @param  string $userId
     * @param  bool $emailAsDummy
     *
     * @return array
     */
    public static function getSubMerchantInput(
        array $entry,
        string $userId,
        bool $emailAsDummy = true): array
    {
        $merchantEmailAsDummy = (($emailAsDummy === true) || (empty($entry[Header::MERCHANT_EMAIL])));

        $merchantEmail =  $merchantEmailAsDummy === true ? null : $entry[Header::MERCHANT_EMAIL];

        return [
            User::USER_ID   => $userId,
            Merchant::EMAIL => $merchantEmail,
            Merchant::NAME  => $entry[Header::MERCHANT_NAME],
        ];
    }

    /**
     * Returns input for sub merchant detail entity creation
     *
     * @param array $e
     * @param Merchant $partner
     * @param bool $emailAsDummy
     *
     * @return array
     */
    public static function getSubMerchantDetailInput(array $e, Merchant $partner, bool $emailAsDummy = true): array
    {
        $merchantEmailAsDummy = empty($e[Header::MERCHANT_ID]) && (($emailAsDummy === true) || (empty($e[Header::MERCHANT_EMAIL])));

        $transactionReportEmail =
            $merchantEmailAsDummy === true ? $partner->getEmail() : $e[Header::TRANSACTION_REPORT_EMAIL];

        $contactEmail = $merchantEmailAsDummy === true ? $partner->getEmail() : $e[Header::CONTACT_EMAIL];

        $input = array();

        ($e[Header::CONTACT_NAME]          !== '') ? ($input[MDEntity::CONTACT_NAME]                = $e[Header::CONTACT_NAME])          : null;
        ($contactEmail                     !== '') ? ($input[MDEntity::CONTACT_EMAIL]               = $contactEmail)                     : null;
        ($transactionReportEmail           !== '') ? ($input[MDEntity::TRANSACTION_REPORT_EMAIL]    = $transactionReportEmail)           : null;
        ($e[Header::CONTACT_MOBILE]        !== '') ? ($input[MDEntity::CONTACT_MOBILE]              = $e[Header::CONTACT_MOBILE])        : null;
        ($e[Header::ORGANIZATION_TYPE]     !== '') ? ($input[MDEntity::BUSINESS_TYPE]               = $e[Header::ORGANIZATION_TYPE])     : null;
        ($e[Header::BUSINESS_NAME]         !== '') ? ($input[MDEntity::BUSINESS_NAME ]              = $e[Header::BUSINESS_NAME])         : null;
        ($e[Header::BILLING_LABEL]         !== '') ? ($input[MDEntity::BUSINESS_DBA]                = $e[Header::BILLING_LABEL])         : null;
        ($e[Header::INTERNATIONAL]         !== '') ? ($input[MDEntity::BUSINESS_INTERNATIONAL]      = $e[Header::INTERNATIONAL])         : null;
        ($e[Header::PAYMENTS_FOR]          !== '') ? ($input[MDEntity::BUSINESS_PAYMENTDETAILS]     = $e[Header::PAYMENTS_FOR])          : null;
        ($e[Header::BUSINESS_MODEL]        !== '') ? ($input[MDEntity::BUSINESS_MODEL]              = $e[Header::BUSINESS_MODEL])        : null;
        ($e[Header::REGISTERED_ADDRESS]    !== '') ? ($input[MDEntity::BUSINESS_REGISTERED_ADDRESS] = $e[Header::REGISTERED_ADDRESS])    : null;
        ($e[Header::REGISTERED_CITY]       !== '') ? ($input[MDEntity::BUSINESS_REGISTERED_CITY]    = $e[Header::REGISTERED_CITY])       : null;
        ($e[Header::REGISTERED_STATE]      !== '') ? ($input[MDEntity::BUSINESS_REGISTERED_STATE]   = $e[Header::REGISTERED_STATE])      : null;
        ($e[Header::REGISTERED_PINCODE]    !== '') ? ($input[MDEntity::BUSINESS_REGISTERED_PIN]     = $e[Header::REGISTERED_PINCODE])    : null;
        ($e[Header::OPERATIONAL_ADDRESS]   !== '') ? ($input[MDEntity::BUSINESS_OPERATION_ADDRESS]  = $e[Header::OPERATIONAL_ADDRESS])   : null;
        ($e[Header::OPERATIONAL_CITY]      !== '') ? ($input[MDEntity::BUSINESS_OPERATION_CITY]     = $e[Header::OPERATIONAL_CITY])      : null;
        ($e[Header::OPERATIONAL_STATE]     !== '') ? ($input[MDEntity::BUSINESS_OPERATION_STATE]    = $e[Header::OPERATIONAL_STATE])     : null;
        ($e[Header::OPERATIONAL_PINCODE]   !== '') ? ($input[MDEntity::BUSINESS_OPERATION_PIN]      = $e[Header::OPERATIONAL_PINCODE])   : null;
        ($e[Header::DOE]                   !== '') ? ($input[MDEntity::BUSINESS_DOE]                = $e[Header::DOE] )                  : null;
        ($e[Header::GSTIN]                 !== '') ? ($input[MDEntity::GSTIN]                       = $e[Header::GSTIN])                 : null;
        ($e[Header::PROMOTER_PAN]          !== '') ? ($input[MDEntity::PROMOTER_PAN ]               = $e[Header::PROMOTER_PAN])          : null;
        ($e[Header::PROMOTER_PAN_NAME]     !== '') ? ($input[MDEntity::PROMOTER_PAN_NAME]           = $e[Header::PROMOTER_PAN_NAME])     : null;
        ($e[Header::WEBSITE_URL]           !== '') ? ($input[MDEntity::BUSINESS_WEBSITE]            = $e[Header::WEBSITE_URL])           : null;
        ($e[Header::BANK_ACCOUNT_NAME]     !== '') ? ($input[MDEntity::BANK_ACCOUNT_NAME ]          = $e[Header::BANK_ACCOUNT_NAME])     : null;
        ($e[Header::BANK_BRANCH_IFSC]      !== '') ? ($input[MDEntity::BANK_BRANCH_IFSC]            = $e[Header::BANK_BRANCH_IFSC])      : null;
        ($e[Header::BANK_ACCOUNT_NUMBER]   !== '') ? ($input[MDEntity::BANK_ACCOUNT_NUMBER]         = $e[Header::BANK_ACCOUNT_NUMBER])   : null;
        ($e[Header::BUSINESS_CATEGORY]     !== '') ? ($input[MDEntity::BUSINESS_CATEGORY]           = $e[Header::BUSINESS_CATEGORY])     : null;
        ($e[Header::BUSINESS_SUB_CATEGORY] !== '') ? ($input[MDEntity::BUSINESS_SUBCATEGORY]        = $e[Header::BUSINESS_SUB_CATEGORY]) : null;
        ($e[Header::COMPANY_CIN]           !== '') ? ($input[MDEntity::COMPANY_CIN]                 = $e[Header::COMPANY_CIN])           : null;
        ($e[Header::COMPANY_PAN]           !== '') ? ($input[MDEntity::COMPANY_PAN]                 = $e[Header::COMPANY_PAN])           : null;
        ($e[Header::COMPANY_PAN_NAME]      !== '') ? ($input[MDEntity::COMPANY_PAN_NAME]            = $e[Header::COMPANY_PAN_NAME])      : null;

        return $input;
    }

    /**
     * Sanitizes merchant detail input
     *
     * @param array  $detailInput
     *
     * @param string $context
     *
     * @return array
     */
    public static function sanitizeMerchantDetailInput(array $detailInput, string $context = '')
    {
        $keysToSanitize = self::getKeysToSanitize()[$context] ?? [];

        return Arr::except($detailInput, $keysToSanitize);
    }

    private static function getKeysToSanitize(): array
    {
        return [
            Constants::BANK_DETAILS     => [
                MDEntity::BANK_ACCOUNT_NUMBER,
                MDEntity::BANK_BRANCH_IFSC,
                MDEntity::BANK_ACCOUNT_NAME,
            ],
            Constants::CATEGORY_DETAILS => [
                MDEntity::BUSINESS_CATEGORY,
                MDEntity::BUSINESS_SUBCATEGORY,
            ],
            Constants::CONFIG_PARAMS => [
                Merchant::PARTNER_ID,
                Merchant::USE_EMAIL_AS_DUMMY,
                Merchant::AUTOFILL_DETAILS,
                Merchant::AUTO_ACTIVATE,
                Merchant::AUTO_SUBMIT,
                Merchant::SKIP_BA_REGISTRATION,
                Merchant::AUTO_ENABLE_INTERNATIONAL,
                Merchant::DEDUPE,
            ]
        ];
    }

    public static function getConfigParamsFromEntry(array $entry): array
    {
        $configParamKeys = self::getKeysToSanitize()[Constants::CONFIG_PARAMS] ?? [];

        $configParams = array_only($entry, $configParamKeys);

        return $configParams;
    }

    public static function getInstantActivationInput(array $e): array
    {
        return [
            MDEntity::BUSINESS_CATEGORY           => $e[Header::BUSINESS_CATEGORY]      ?? null,
            MDEntity::BUSINESS_SUBCATEGORY        => $e[Header::BUSINESS_SUB_CATEGORY]  ?? null,
            MDEntity::PROMOTER_PAN                => $e[Header::PROMOTER_PAN]           ?? null,
            MDEntity::BUSINESS_NAME               => $e[Header::BUSINESS_NAME]          ?? null,
            MDEntity::BUSINESS_MODEL              => $e[Header::BUSINESS_MODEL]         ?? null,
            MDEntity::BUSINESS_WEBSITE            => $e[Header::WEBSITE_URL]            ?? null,
            MDEntity::BUSINESS_DBA                => $e[Header::BILLING_LABEL]          ?? null,
            MDEntity::BUSINESS_TYPE               => $e[Header::ORGANIZATION_TYPE]      ?? null,
            MDEntity::BUSINESS_OPERATION_ADDRESS  => $e[Header::OPERATIONAL_ADDRESS]    ?? null,
            MDEntity::BUSINESS_OPERATION_STATE    => $e[Header::OPERATIONAL_STATE]       ?? null,
            MDEntity::BUSINESS_OPERATION_CITY     => $e[Header::OPERATIONAL_CITY]      ?? null,
            MDEntity::BUSINESS_OPERATION_PIN      => $e[Header::OPERATIONAL_PINCODE]    ?? null,
            MDEntity::BUSINESS_REGISTERED_ADDRESS => $e[Header::REGISTERED_ADDRESS]     ?? null,
            MDEntity::BUSINESS_REGISTERED_STATE   => $e[Header::REGISTERED_STATE]        ?? null,
            MDEntity::BUSINESS_REGISTERED_CITY    => $e[Header::REGISTERED_CITY]       ?? null,
            MDEntity::BUSINESS_REGISTERED_PIN     => $e[Header::REGISTERED_PINCODE]     ?? null,
        ];
    }

}
