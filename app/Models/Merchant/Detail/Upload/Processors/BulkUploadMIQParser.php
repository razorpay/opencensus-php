<?php

namespace RZP\Models\Merchant\Detail\Upload\Processors;

use RZP\Models\Batch\Header;
use RZP\Models\Merchant\FeeBearer as MFeeBearer;
use RZP\Models\Merchant\Detail\Entity as MDEntity;
use RZP\Models\Merchant\BusinessDetail\Entity as BEntity;
use RZP\Models\Merchant\Detail\BusinessType as BusinessType;
use RZP\Models\Merchant\BusinessDetail\Constants as BConstants;

class BulkUploadMIQParser
{
    /**
     * Required website details if website present in Upload MIQ file.
     * @var array
     */
    public static $miqWebsiteEntries = [
        Header::MIQ_WEBSITE_REFUNDS,
        Header::MIQ_WEBSITE_ABOUT_US,
        Header::MIQ_WEBSITE_CONTACT_US,
        Header::MIQ_WEBSITE_CANCELLATION,
        Header::MIQ_WEBSITE_PRIVACY_POLICY,
        Header::MIQ_WEBSITE_PRODUCT_PRICING,
        Header::MIQ_WEBSITE_TERMS_CONDITIONS,
        Header::MIQ_WEBSITE_SHIPPING_DELIVERY,
    ];

    /**
     * Allowed Business Types.
     * @var array
     */
    public static $miqBusinessTypes = [
        BusinessType::TYPE1 ,
        BusinessType::TYPE3,
        BusinessType::TYPE4,
        BusinessType::TYPE5,
        BusinessType::TYPE6,
        BusinessType::TYPE7,
        BusinessType::TYPE9,
        BusinessType::TYPE11 ,
    ];

    private static $caseInSensitiveHeaders = [
        Header::MIQ_FEE_MODEL,
        Header::MIQ_INTERNATIONAL,
        Header::MIQ_NB_FEE_BEARER,
        Header::MIQ_UPI_FEE_BEARER,
        Header::MIQ_RUPAY_FEE_BEARER,
        Header::MIQ_WALLETS_FEE_BEARER,
        Header::MIQ_BUSINESS_FEE_BEARER,
        Header::MIQ_INTL_CARD_FEE_BEARER,
        Header::MIQ_DEBIT_CARD_FEE_BEARER,
        Header::MIQ_CREDIT_CARD_FEE_BEARER,
    ];

    private static $merchantFeeBearers = [
        Header::MIQ_NB_FEE_BEARER,
        Header::MIQ_UPI_FEE_BEARER,
        Header::MIQ_RUPAY_FEE_BEARER,
        Header::MIQ_WALLETS_FEE_BEARER,
        Header::MIQ_BUSINESS_FEE_BEARER,
        Header::MIQ_INTL_CARD_FEE_BEARER,
        Header::MIQ_DEBIT_CARD_FEE_BEARER,
        Header::MIQ_CREDIT_CARD_FEE_BEARER,
    ];

    private static $miqSensitiveFieldsForLogging = [
        Header::MIQ_BANK_ACC_NUMBER,
        Header::MIQ_BENEFICIARY_NAME,
        Header::MIQ_BUSINESS_PAN,
        Header::MIQ_AUTHORISED_SIGNATORY_PAN,
        Header::MIQ_PAN_OWNER_NAME,
        Header::MIQ_CONTACT_NUMBER,
    ];

    /**
     * *
     * Map merchant detail entity.
     *
     * @param array $entry
     * @return array
     */
    public static function getMerchantDetailInput(array $entry): array
    {
        return [
            MDEntity::CONTACT_NAME                  => $entry[Header::MIQ_CONTACT_NAME],
            MDEntity::CONTACT_EMAIL                 => $entry[Header::MIQ_CONTACT_EMAIL],
            MDEntity::CONTACT_MOBILE                => $entry[Header::MIQ_CONTACT_NUMBER],
            MDEntity::BUSINESS_TYPE                 => $entry[Header::MIQ_BUSINESS_TYPE],
            MDEntity::BUSINESS_NAME                 => $entry[Header::MIQ_BUSINESS_NAME],
            MDEntity::BUSINESS_DBA                  => $entry[Header::MIQ_DBA_NAME],
            MDEntity::BUSINESS_REGISTERED_ADDRESS   => $entry[Header::MIQ_ADDRESS],
            MDEntity::BUSINESS_REGISTERED_CITY      => $entry[Header::MIQ_CITY],
            MDEntity::BUSINESS_REGISTERED_STATE     => $entry[Header::MIQ_STATE],
            MDEntity::BUSINESS_REGISTERED_PIN       => $entry[Header::MIQ_PIN_CODE],
            MDEntity::BUSINESS_OPERATION_ADDRESS    => $entry[Header::MIQ_ADDRESS],
            MDEntity::BUSINESS_OPERATION_CITY       => $entry[Header::MIQ_CITY],
            MDEntity::BUSINESS_OPERATION_STATE      => $entry[Header::MIQ_STATE],
            MDEntity::BUSINESS_OPERATION_PIN        => $entry[Header::MIQ_PIN_CODE],
            MDEntity::GSTIN                         => $entry[Header::MIQ_GSTIN],
            MDEntity::COMPANY_PAN                   => $entry[Header::MIQ_BUSINESS_PAN],
            MDEntity::COMPANY_PAN_NAME              => $entry[Header::MIQ_BUSINESS_NAME],
            MDEntity::PROMOTER_PAN                  => $entry[Header::MIQ_AUTHORISED_SIGNATORY_PAN],
            MDEntity::PROMOTER_PAN_NAME             => $entry[Header::MIQ_PAN_OWNER_NAME],
            MDEntity::BUSINESS_SUBCATEGORY          => $entry[Header::MIQ_SUB_CATEGORY],
            MDEntity::BANK_ACCOUNT_NAME             => $entry[Header::MIQ_BENEFICIARY_NAME],
            MDEntity::BANK_BRANCH_IFSC              => $entry[Header::MIQ_BRANCH_IFSC_CODE],
            MDEntity::BANK_ACCOUNT_NUMBER           => $entry[Header::MIQ_BANK_ACC_NUMBER],
            MDEntity::TRANSACTION_REPORT_EMAIL      => $entry[Header::MIQ_TXN_REPORT_EMAIL],
            MDEntity::BUSINESS_CATEGORY             => $entry[Header::MIQ_BUSINESS_CATEGORY],
            MDEntity::BUSINESS_DESCRIPTION          => $entry[Header::MIQ_BUSINESS_DESCRIPTION],
            MDEntity::DATE_OF_ESTABLISHMENT         => $entry[Header::MIQ_ESTD_DATE],
            MDEntity::BUSINESS_INTERNATIONAL        => $entry[Header::MIQ_INTERNATIONAL] === 'yes' ? 1  : 0,
            MDEntity::COMPANY_CIN                   => $entry[Header::MIQ_CIN] !== '' ? $entry[Header::MIQ_CIN] : null,
            MDEntity::BUSINESS_WEBSITE              => $entry[Header::MIQ_WEBSITE] !== '' ? $entry[Header::MIQ_WEBSITE]  : null,
        ];
    }

    /**
     * *
     * Map website detail entity.
     *
     * @param array $entry
     * @return array
     */
    public static function getWebsiteDetailInput(array $entry): array
    {
        return [
            BEntity::WEBSITE_DETAILS => [
                BConstants::REFUND              => $entry[Header::MIQ_WEBSITE_REFUNDS] !=='' ? $entry[Header::MIQ_WEBSITE_REFUNDS] : null,
                BConstants::ABOUT               => $entry[Header::MIQ_WEBSITE_ABOUT_US] !=='' ? $entry[Header::MIQ_WEBSITE_ABOUT_US] : null,
                BConstants::CONTACT             => $entry[Header::MIQ_WEBSITE_CONTACT_US] !=='' ? $entry[Header::MIQ_WEBSITE_CONTACT_US] : null,
                BConstants::CANCELLATION        => $entry[Header::MIQ_WEBSITE_CANCELLATION] !=='' ? $entry[Header::MIQ_WEBSITE_CANCELLATION] : null,
                BConstants::PRIVACY             => $entry[Header::MIQ_WEBSITE_PRIVACY_POLICY] !=='' ? $entry[Header::MIQ_WEBSITE_PRIVACY_POLICY] : null,
                BConstants::PRICING             => $entry[Header::MIQ_WEBSITE_PRODUCT_PRICING] !=='' ? $entry[Header::MIQ_WEBSITE_PRODUCT_PRICING] : null,
                BConstants::TERMS               => $entry[Header::MIQ_WEBSITE_TERMS_CONDITIONS] !==''? $entry[Header::MIQ_WEBSITE_TERMS_CONDITIONS]: null,
            ],
        ];
    }

    /**
     * *
     * Response format to be returned to batch service.
     *
     * @param array $entry
     * @return array
     */
    public static function getDefaultBatchResponse(array $entry): array
    {
        return [
            Header::MIQ_OUT_FEE_BEARER          => '',
            Header::MIQ_OUT_MERCHANT_ID         => '',
            Header::STATUS                      => '',
            Header::ERROR_CODE                  => '',
            Header::ERROR_DESCRIPTION           => '',
            Header::MIQ_OUT_MERCHANT_NAME       => $entry[Header::MIQ_MERCHANT_NAME],
            Header::MIQ_OUT_MERCHANT_EMAIL      => $entry[Header::MIQ_CONTACT_EMAIL],
        ];
    }

    public function getMerchantFeeBearerType(array $entry): string
    {
        $feeBearers = array();

        // default fee bearer, required in merchant creation/activation
        $feeBearer = MFeeBearer::PLATFORM;

        foreach (self::$merchantFeeBearers as $feeBearerHeader)
        {
            $feeBearerValue = $entry[$feeBearerHeader];

            if($feeBearerValue === MFeeBearer::MERCHANT)
            {
                $feeBearerValue = MFeeBearer::PLATFORM;
            }
            $feeBearers[$feeBearerValue] = true;
        }

        if((empty($feeBearers[MFeeBearer::CUSTOMER]) === false) and (empty($feeBearers[MFeeBearer::PLATFORM]) === false))
        {
            $feeBearer = MFeeBearer::DYNAMIC;
        }
        elseif (empty($feeBearers[MFeeBearer::CUSTOMER]) === false)
        {
            $feeBearer = MFeeBearer::CUSTOMER;
        }

        return $feeBearer;
    }

    /**
     * *
     * Require conversions wherever need.
     *
     * @param array $entry
     * @return void
     */
    public function preProcessMerchantEntry(array & $entry)
    {
        foreach (self::$caseInSensitiveHeaders as $header)
        {
            $entry[$header] = strtolower($entry[$header]);
        }

        if(in_array($entry[Header::MIQ_BUSINESS_TYPE], self::$miqBusinessTypes))
        {
            $entry[Header::MIQ_BUSINESS_TYPE] = BusinessType::$typeIndexMap[strtolower($entry[Header::MIQ_BUSINESS_TYPE])];
        }
    }

    /**
     * *
     * Masked sensitive miq details from being logged.
     *
     * @param array $entry
     * @return void
     */
    public function getMaskedEntryForLogging(array $entry): array
    {
        $maskedEntry = [];

        foreach ($entry as $header => $value)
        {
            $maskedEntry[$header] = $value;

            if (empty($value) === false and in_array($header, self::$miqSensitiveFieldsForLogging, true) === true)
            {
                $maskedEntry[$header] = mask_except_last4($value);
            }
        }

        return $maskedEntry;
    }
}
