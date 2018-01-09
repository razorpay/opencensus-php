<?php

namespace RZP\Models\Batch\Helpers;

use RZP\Models\Merchant;
use RZP\Models\BankAccount;
use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail\Entity as MDEntity;


class SubMerchant
{
    /**
     * Returns input for sub merchant creation
     *
     * @param  array  $entry
     *
     * @return array
     */
    public static function getSubMerchantInput(array $entry): array
    {
        return [
            Merchant\Entity::ID    => Merchant\Entity::generateUniqueId(),
            Merchant\Entity::NAME  => $entry[Header::MERCHANT_NAME],
            Merchant\Entity::EMAIL => $entry[Header::MERCHANT_EMAIL],
        ];
    }

    /**
     * Returns input for sub merchant detail entity creation
     *
     * @param  array  $e
     *
     * @return array
     */
    public static function getSubMerchantDetailInput(array $e): array
    {
        return [
            MDEntity::CONTACT_NAME                => $e[Header::CONTACT_NAME],
            MDEntity::CONTACT_EMAIL               => $e[Header::CONTACT_EMAIL],
            MDEntity::TRANSACTION_REPORT_EMAIL    => $e[Header::TRANSACTION_REPORT_EMAIL],
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
            MDEntity::TRANSACTION_VOLUME          => $e[Header::EXPECTED_ANNUAL_VOLUME],
            MDEntity::TRANSACTION_VALUE           => $e[Header::AVG_TRANSACTION_VALUE],
            MDEntity::PROMOTER_PAN                => $e[Header::PROMOTER_PAN],
            MDEntity::PROMOTER_PAN_NAME           => $e[Header::PROMOTER_PAN_NAME],
            MDEntity::BUSINESS_WEBSITE            => $e[Header::WEBSITE_URL],
            MDEntity::WEBSITE_ABOUT               => $e[Header::WEBSITE_ABOUT],
            MDEntity::WEBSITE_CONTACT             => $e[Header::WEBSITE_CONTACT],
            MDEntity::WEBSITE_PRIVACY             => $e[Header::WEBSITE_PRIVACY],
            MDEntity::WEBSITE_PRICING             => $e[Header::WEBSITE_PRICING],
            MDEntity::WEBSITE_REFUND              => $e[Header::WEBSITE_REFUND],
            MDEntity::WEBSITE_TERMS               => $e[Header::WEBSITE_TERMS],
            MDEntity::BANK_ACCOUNT_NAME           => $e[Header::BANK_ACCOUNT_NAME],
            MDEntity::BANK_BRANCH_IFSC            => $e[Header::BANK_BRANCH_IFSC],
            MDEntity::BANK_ACCOUNT_NUMBER         => $e[Header::BANK_ACCOUNT_NUMBER],
            MDEntity::BANK_ACCOUNT_TYPE           => $e[Header::BANK_ACCOUNT_TYPE],
            MDEntity::BANK_BENEFICIARY_ADDRESS1   => $e[Header::BANK_ACCOUNT_ADDRESS_1],
            MDEntity::BANK_BENEFICIARY_CITY       => $e[Header::BANK_ACCOUNT_CITY],
            MDEntity::BANK_BENEFICIARY_STATE      => $e[Header::REGISTERED_STATE],
            MDEntity::BANK_BENEFICIARY_PIN        => $e[Header::BANK_ACCOUNT_PINCODE],
            MDEntity::SUBMIT                      => '1',
        ];
    }
}
