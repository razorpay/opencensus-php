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
     * @param  array  $e
     * @param  Merchant $partner
     * @param  bool $emailAsDummy
     *
     * @return array
     */
    public static function getSubMerchantDetailInput(array $e, Merchant $partner, bool $emailAsDummy = true): array
    {
        $merchantEmailAsDummy = (($emailAsDummy === true) || (empty($e[Header::MERCHANT_EMAIL])));

        $transactionReportEmail =
            $merchantEmailAsDummy === true ? $partner->getEmail() : $e[Header::TRANSACTION_REPORT_EMAIL];

        $contactEmail = $merchantEmailAsDummy === true ? $partner->getEmail() : $e[Header::CONTACT_EMAIL];

        return [
            MDEntity::CONTACT_NAME                => $e[Header::CONTACT_NAME],
            MDEntity::CONTACT_EMAIL               => $contactEmail,
            MDEntity::TRANSACTION_REPORT_EMAIL    => $transactionReportEmail,
            MDEntity::CONTACT_MOBILE              => $e[Header::CONTACT_MOBILE],
            MDEntity::BUSINESS_TYPE               => $e[Header::ORGANIZATION_TYPE],
            MDEntity::BUSINESS_NAME               => $e[Header::BUSINESS_NAME],
            MDEntity::BUSINESS_DBA                => $e[Header::BILLING_LABEL],
            MDEntity::BUSINESS_INTERNATIONAL      => $e[Header::INTERNATIONAL],
            MDEntity::BUSINESS_PAYMENTDETAILS     => $e[Header::PAYMENTS_FOR],
            MDEntity::BUSINESS_MODEL              => $e[Header::BUSINESS_MODEL],
            MDEntity::BUSINESS_REGISTERED_ADDRESS => $e[Header::REGISTERED_ADDRESS],
            MDEntity::BUSINESS_REGISTERED_CITY    => $e[Header::REGISTERED_CITY],
            MDEntity::BUSINESS_REGISTERED_STATE   => $e[Header::REGISTERED_STATE],
            MDEntity::BUSINESS_REGISTERED_PIN     => $e[Header::REGISTERED_PINCODE],
            MDEntity::BUSINESS_OPERATION_ADDRESS  => $e[Header::OPERATIONAL_ADDRESS],
            MDEntity::BUSINESS_OPERATION_CITY     => $e[Header::OPERATIONAL_CITY],
            MDEntity::BUSINESS_OPERATION_STATE    => $e[Header::OPERATIONAL_STATE],
            MDEntity::BUSINESS_OPERATION_PIN      => $e[Header::OPERATIONAL_PINCODE],
            MDEntity::BUSINESS_DOE                => $e[Header::DOE],
            MDEntity::GSTIN                       => $e[Header::GSTIN],
            MDEntity::PROMOTER_PAN                => $e[Header::PROMOTER_PAN],
            MDEntity::PROMOTER_PAN_NAME           => $e[Header::PROMOTER_PAN_NAME],
            MDEntity::BUSINESS_WEBSITE            => $e[Header::WEBSITE_URL],
            MDEntity::BANK_ACCOUNT_NAME           => $e[Header::BANK_ACCOUNT_NAME],
            MDEntity::BANK_BRANCH_IFSC            => $e[Header::BANK_BRANCH_IFSC],
            MDEntity::BANK_ACCOUNT_NUMBER         => $e[Header::BANK_ACCOUNT_NUMBER],
            MDEntity::BUSINESS_CATEGORY           => $e[Header::BUSINESS_CATEGORY],
            MDEntity::BUSINESS_SUBCATEGORY        => $e[Header::BUSINESS_SUB_CATEGORY],
            MDEntity::COMPANY_CIN                 => $e[Header::COMPANY_CIN],
            MDEntity::COMPANY_PAN                 => $e[Header::COMPANY_PAN],
            MDEntity::COMPANY_PAN_NAME            => $e[Header::COMPANY_PAN_NAME],
        ];
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
            ]
        ];
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
            MDEntity::BUSINESS_OPERATION_STATE    => $e[Header::OPERATIONAL_CITY]       ?? null,
            MDEntity::BUSINESS_OPERATION_CITY     => $e[Header::OPERATIONAL_STATE]      ?? null,
            MDEntity::BUSINESS_OPERATION_PIN      => $e[Header::OPERATIONAL_PINCODE]    ?? null,
            MDEntity::BUSINESS_REGISTERED_ADDRESS => $e[Header::REGISTERED_ADDRESS]     ?? null,
            MDEntity::BUSINESS_REGISTERED_STATE   => $e[Header::REGISTERED_CITY]        ?? null,
            MDEntity::BUSINESS_REGISTERED_CITY    => $e[Header::REGISTERED_STATE]       ?? null,
            MDEntity::BUSINESS_REGISTERED_PIN     => $e[Header::REGISTERED_PINCODE]     ?? null,
        ];
    }
}
