<?php

namespace RZP\Models\Merchant\Detail\Upload\Processors;

use RZP\Models\Batch\Header;
use Razorpay\IFSC\Bank as Banks;
use RZP\Models\Card\Type as CardType;
use RZP\Models\Card\Network as CardNetwork;
use RZP\Constants\Product as ProductConstants;
use RZP\Models\Pricing\Entity as PricingEntity;
use RZP\Models\Merchant\FeeBearer as MFeeBearer;
use RZP\Models\Merchant\Detail\Entity as MDEntity;
use RZP\Models\Merchant\Methods\Entity as MethodEntity;
use RZP\Models\Merchant\BusinessDetail\Entity as BEntity;
use RZP\Models\Merchant\Detail\BusinessType as BusinessType;
use RZP\Models\Merchant\Detail\Upload\Constants as UConstants;
use RZP\Models\Merchant\BusinessDetail\Constants as BConstants;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;

class BulkUploadMIQParser
{
    private static array $caseInSensitiveHeaders = [
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

    private static array $miqSensitiveFieldsForLogging = [
        Header::MIQ_BANK_ACC_NUMBER,
        Header::MIQ_BENEFICIARY_NAME,
        Header::MIQ_BUSINESS_PAN,
        Header::MIQ_AUTHORISED_SIGNATORY_PAN,
        Header::MIQ_PAN_OWNER_NAME,
        Header::MIQ_CONTACT_NUMBER,
    ];

    private static array $walletPricingMapping = [
        Header::MIQ_WALLETS_FREECHARGE  => MethodEntity::FREECHARGE,
        // fee to be charged remaining wallets
        Header::MIQ_WALLETS_ANY         => Header::MIQ_WALLETS_ANY,
    ];

    private static array $netBankingPricingMapping = [
        Header::MIQ_AXIS        =>  Banks::UTIB,
        Header::MIQ_HDFC        =>  Banks::HDFC,
        Header::MIQ_ICICI       =>  Banks::ICIC,
        Header::MIQ_SBI         =>  Banks::SBIN,
        Header::MIQ_YES         =>  Banks::YESB,
        // fee to be charged remaining banks
        Header::MIQ_NB_ANY      =>  Header::MIQ_NB_ANY,
    ];

    private static array $miqbusinessDetails = [
        Header::MIQ_CONTACT_NAME                => MDEntity::CONTACT_NAME,
        Header::MIQ_CONTACT_EMAIL               => MDEntity::CONTACT_EMAIL,
        Header::MIQ_TXN_REPORT_EMAIL            => MDEntity::TRANSACTION_REPORT_EMAIL,
        Header::MIQ_PIN_CODE                    => MDEntity::BUSINESS_REGISTERED_PIN,
        Header::MIQ_ADDRESS                     => MDEntity::BUSINESS_OPERATION_ADDRESS,
        Header::MIQ_CITY                        => MDEntity::BUSINESS_OPERATION_CITY,
        Header::MIQ_STATE                       => MDEntity::BUSINESS_OPERATION_STATE,
        Header::MIQ_CONTACT_NUMBER              => MDEntity::CONTACT_MOBILE,
        Header::MIQ_MERCHANT_NAME_BUSINESS_NAME => MDEntity::BUSINESS_NAME,
        Header::MIQ_DBA_NAME                    => MDEntity::BUSINESS_DBA,
        Header::MIQ_GSTIN                       => MDEntity::GSTIN,
        Header::MIQ_BUSINESS_PAN                => MDEntity::COMPANY_PAN,
        Header::MIQ_BUSINESS_NAME               => MDEntity::COMPANY_PAN_NAME,
        Header::MIQ_AUTHORISED_SIGNATORY_PAN    => MDEntity::PROMOTER_PAN,
        Header::MIQ_PAN_OWNER_NAME              => MDEntity::PROMOTER_PAN_NAME,
        Header::MIQ_SUB_CATEGORY                => MDEntity::BUSINESS_SUBCATEGORY,
        Header::MIQ_BUSINESS_CATEGORY           => MDEntity::BUSINESS_CATEGORY,
        Header::MIQ_BUSINESS_DESCRIPTION        => MDEntity::BUSINESS_DESCRIPTION,
        Header::MIQ_CIN                         => MDEntity::COMPANY_CIN,
        Header::MIQ_WEBSITE                     => MDEntity::BUSINESS_WEBSITE,
    ];

    private static array $cardPricingMapping = [
        Header::MIQ_CREDIT_CARD_FEE_TYPE => [
            UConstants::PRICING_FEE_BEARER          => Header::MIQ_CREDIT_CARD_FEE_BEARER,
            UConstants::PRICING_METHOD_TYPE         => CardType::CREDIT,
            UConstants::PRICING_NETWORK             => '',
            UConstants::PRICING_METHOD_SUBTYPE      => '',
            UConstants::PRICING_AMOUNT_RANGE_ACTIVE => '1',
            UConstants::PRICING_AMOUNT_RANGES  => [
                Header::MIQ_CREDIT_CARD_0_2K => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '0',
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '200000', // 2k
                ],
                Header::MIQ_CREDIT_CARD_2K_1CR => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '200000', // 2k
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '1000000000'  // 1cr
                ],
            ],
        ],
        Header::MIQ_DEBIT_CARD_FEE_TYPE => [
            UConstants::PRICING_FEE_BEARER     => Header::MIQ_DEBIT_CARD_FEE_BEARER,
            UConstants::PRICING_METHOD_TYPE    => CardType::DEBIT,
            UConstants::PRICING_NETWORK        => '',
            UConstants::PRICING_METHOD_SUBTYPE => '',
            UConstants::PRICING_AMOUNT_RANGE_ACTIVE => '1',
            UConstants::PRICING_AMOUNT_RANGES  => [
                Header::MIQ_DEBIT_CARD_0_2K => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '0',
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '200000', // 2k
                ],
                Header::MIQ_DEBIT_CARD_2K_1CR => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '200000', // 2k
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '1000000000',  // 1cr
                ],
            ],
        ],
        Header::MIQ_RUPAY_FEE_TYPE => [
            UConstants::PRICING_FEE_BEARER     => Header::MIQ_CREDIT_CARD_FEE_BEARER,
            UConstants::PRICING_METHOD_TYPE    => CardType::DEBIT,
            UConstants::PRICING_NETWORK        => CardNetwork::RUPAY,
            UConstants::PRICING_METHOD_SUBTYPE => '',
            UConstants::PRICING_AMOUNT_RANGE_ACTIVE => '1',
            UConstants::PRICING_AMOUNT_RANGES  => [
                Header::MIQ_RUPAY_0_2K  => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '0',
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '200000', // 2k
                ],
                Header::MIQ_RUPAY_2K_1CR => [
                    UConstants::PRICING_AMOUNT_RANGE_MIN => '200000', // 2k
                    UConstants::PRICING_AMOUNT_RANGE_MAX => '1000000000'  // 1cr
                ],
            ],
        ],
        Header::MIQ_INTL_CARD_FEE_TYPE => [
            UConstants::PRICING_FEE_BEARER     => Header::MIQ_CREDIT_CARD_FEE_BEARER,
            UConstants::PRICING_METHOD_TYPE    => '',
            UConstants::PRICING_NETWORK        => '',
            UConstants::PRICING_METHOD_SUBTYPE => '',
            UConstants::INTERNATIONAL          => '',
            UConstants::PRICING_AMOUNT_RANGE_ACTIVE => '0',
            UConstants::PRICING_AMOUNT_RANGES  => [
                Header::MIQ_INTERNATIONAL_CARD => [],
            ],
        ],
        Header::MIQ_BUSINESS_FEE_TYPE => [
            UConstants::PRICING_FEE_BEARER     => Header::MIQ_BUSINESS_FEE_BEARER,
            UConstants::PRICING_METHOD_TYPE    => '',
            UConstants::PRICING_NETWORK        => '',
            UConstants::PRICING_METHOD_SUBTYPE => 'business',
            UConstants::PRICING_AMOUNT_RANGE_ACTIVE => '0',
            UConstants::PRICING_AMOUNT_RANGES  => [
                Header::MIQ_BUSINESS => [],
            ],
        ],
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
            MDEntity::BUSINESS_MODEL                => $entry[Header::MIQ_BUSINESS_DESCRIPTION],
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

    public static function getOrgDefinedMerchantFields(array $entry, $org): array
    {
        $isPermissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($org->getId(), Permission\Name::ORG_DEFINED_CUSTOM_MERCHANT_FIELDS);
        if ($isPermissionEnabled === false)
        {
            return [];
        }
        $additionalFields = [
            Header::FIELD1 => $entry[Header::FIELD1],
            Header::FIELD2 => $entry[Header::FIELD2],
            Header::FIELD3 => $entry[Header::FIELD3],
            Header::FIELD4 => $entry[Header::FIELD4],
            Header::FIELD5 => $entry[Header::FIELD5],
            Header::FIELD6 => $entry[Header::FIELD6],
            Header::FIELD7 => $entry[Header::FIELD7],
            Header::FIELD8 => $entry[Header::FIELD8],
            Header::FIELD9 => $entry[Header::FIELD9],
            Header::FIELD10 => $entry[Header::FIELD10],
            Header::FIELD11 => $entry[Header::FIELD11],
            Header::FIELD12 => $entry[Header::FIELD12],
            Header::FIELD13 => $entry[Header::FIELD13],
            Header::FIELD14 => $entry[Header::FIELD14],
            Header::FIELD15 => $entry[Header::FIELD15]
        ];

        $orgCustomConfig = (new Org\Service)->getOrgCustomConfig();
        $orgCustomConfig = array_key_exists($org->getId(), $orgCustomConfig) ? $orgCustomConfig[$org->getId()] : null;

        if ($orgCustomConfig === null)
        {
            return [];
        }
        $lookup = array_change_key_case($additionalFields, CASE_LOWER);

        // Iterate through $orgCustomConfig and update values from $additionalFields
        foreach ($orgCustomConfig as &$item)
        {
            $fieldKey = strtolower($item['id']);
            if (isset($lookup[$fieldKey]))
            {
                $item['value'] = $lookup[$fieldKey];
            }
        }

        return [
            BEntity::METADATA => [BEntity::ORG_DEFINED_MERCHANT_FIELDS => $orgCustomConfig]
        ];
    }
    /**
     * *
     * Map merchant detail entity.
     *
     * @param array $entry
     * @return array
     */
    public static function getUpdatedMerchantDetailInput(): array
    {
        return self::$miqbusinessDetails;
    }

    /**
     * *
     * Map website detail entity.
     *
     * @param array $entry
     * @return array
     */

    public static function getUpdatedWebsiteDetailInput(array $entry, array $websiteData): array
    {
        return [
            BEntity::WEBSITE_DETAILS => [
                BConstants::REFUND              => strtolower($entry[Header::MIQ_WEBSITE_REFUNDS]) !== 'na' ? $entry[Header::MIQ_WEBSITE_REFUNDS] : $websiteData[BConstants::REFUND]['url'],
                BConstants::ABOUT               => strtolower($entry[Header::MIQ_WEBSITE_ABOUT_US]) !== 'na' ? $entry[Header::MIQ_WEBSITE_ABOUT_US] : $websiteData['about_us']['url'],
                BConstants::CONTACT             => strtolower($entry[Header::MIQ_WEBSITE_CONTACT_US]) !== 'na' ? $entry[Header::MIQ_WEBSITE_CONTACT_US] : $websiteData['contact_us']['url'],
                BConstants::CANCELLATION        => strtolower($entry[Header::MIQ_WEBSITE_CANCELLATION]) !== 'na' ? $entry[Header::MIQ_WEBSITE_CANCELLATION] : $websiteData[BConstants::CANCELLATION]['url'],
                BConstants::PRIVACY             => strtolower($entry[Header::MIQ_WEBSITE_PRIVACY_POLICY]) !== 'na' ? $entry[Header::MIQ_WEBSITE_PRIVACY_POLICY] : $websiteData[BConstants::PRIVACY]['url'],
                BConstants::PRICING             => strtolower($entry[Header::MIQ_WEBSITE_PRODUCT_PRICING]) !== 'na' ? $entry[Header::MIQ_WEBSITE_PRODUCT_PRICING] : $websiteData[BConstants::PRICING]['url'],
                BConstants::TERMS               => strtolower($entry[Header::MIQ_WEBSITE_TERMS_CONDITIONS]) !== 'na'? $entry[Header::MIQ_WEBSITE_TERMS_CONDITIONS]: $websiteData[BConstants::TERMS]['url'],
            ],
        ];
    }

    public static function getMerchantWebsiteInput(array $input, string $website, string $mid, array $processedEntry): array
    {
       $response = [];

       foreach ($input as $key=>$value)
       {
           if($key === BConstants::ABOUT or $key === BConstants::CONTACT)
               $key = $key . '_us';

           $response[$key]=[
               "url" => $value
           ];
       }

       $response['shipping'] = $processedEntry[Header::MIQ_WEBSITE_SHIPPING_DELIVERY] !==''
           ? $processedEntry[Header::MIQ_WEBSITE_SHIPPING_DELIVERY] : null;

        return [
            'merchant_id' => $mid,
            'admin_website_details' => [
                'website' => [
                    $website => $response
                ]
            ],
        ];
    }

    public static function getUpdateMerchantWebsiteInput(array $input, string $website, string $mid, array $processedEntry): array
    {
        $response = [];

        foreach ($input as $key=>$value)
        {
            if($key === BConstants::ABOUT or $key === BConstants::CONTACT)
                $key = $key . '_us';

            $response[$key]=[
                'url' => $value
            ];
        }

        $response['shipping'] = $processedEntry[Header::MIQ_WEBSITE_SHIPPING_DELIVERY] !=='NA'
            ? $processedEntry[Header::MIQ_WEBSITE_SHIPPING_DELIVERY] : $response['shipping'];

        return [
            "merchant_id" => $mid,
            "admin_website_details" => [
                "website" => [
                    $website => $response
                ]
            ],
        ];
    }

    public function getMerchantFeeBearerType(array $entry): string
    {
        if (in_array(MFeeBearer::CUSTOMER, $entry, true) and in_array(MFeeBearer::PLATFORM, $entry, true))
        {
            return MFeeBearer::DYNAMIC;
        }
        elseif (in_array(MFeeBearer::CUSTOMER, $entry, true))
        {
            return MFeeBearer::CUSTOMER;
        }

        return MFeeBearer::PLATFORM;
    }

    /**
     * *
     * Require conversions wherever need.
     *
     * @param array $entry
     * @return void
     */
    public function preProcessMerchantEntry(array & $entry): void
    {
        foreach ($entry as $header => &$value)
        {
            // Trim spaces before and after if the value is a string
            if(is_string($value))
            {
                $value = trim($value);
            }

            if(in_array($header, self::$caseInSensitiveHeaders))
            {
                $value = strtolower($value);

                continue;
            }

            if($header === Header::MIQ_BUSINESS_TYPE)
            {
                $value = BusinessType::$typeIndexMap[strtolower($entry[Header::MIQ_BUSINESS_TYPE])] ?? null;
            }
        }
    }

    /**
     * *
     * Masked sensitive miq details from being logged.
     *
     * @param array $entry
     * @return array
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

    private function getNBRuleInput(array $entry):array
    {
        $rules = array();

        $input = $this->getCommonRuleInput();

        $input[PricingEntity::PAYMENT_METHOD] = MethodEntity::NETBANKING;

        $feeBearer = $entry[Header::MIQ_NB_FEE_BEARER];

        if($feeBearer === MFeeBearer::PLATFORM or $feeBearer === MFeeBearer::CUSTOMER)
        {
            // converting fee bearer type to lower case, avoiding case sensitivity.
            $feeBearerType = strtolower($entry[Header::MIQ_NB_FEE_TYPE]);

            $input[PricingEntity::FEE_BEARER] = $feeBearer ?? MFeeBearer::PLATFORM;

            foreach (self::$netBankingPricingMapping as $key => $value)
            {
                $rule = $input;

                if (strtolower($entry[$key]) !== UConstants::FEE_TYPE_NA and ($feeBearerType === UConstants::FEE_TYPE_FLAT  or
                        $feeBearerType=== UConstants::FEE_TYPE_PERCENT))
                {
                    if($feeBearerType === UConstants::FEE_TYPE_PERCENT)
                    {
                        $rule[PricingEntity::PERCENT_RATE] = round($entry[$key], 2) * 100;
                    }
                    else
                    {
                        $rule[PricingEntity::FIXED_RATE] = round($entry[$key], 2) * 100;
                    }

                    $rule[PricingEntity::PAYMENT_NETWORK] = $value;

                    if ($key === Header::MIQ_NB_ANY)
                    {
                        $rule[PricingEntity::PAYMENT_NETWORK] = '';
                    }

                    $rules[]=$rule;
                }
            }
        }

        return $rules;
    }

    private function getUpiRuleInput(array $entry): array
    {
        $rules = array();

        $input = $this->getCommonRuleInput();

        $input[PricingEntity::PAYMENT_METHOD] = MethodEntity::UPI;

        $feeBearer = $entry[Header::MIQ_UPI_FEE_BEARER];

        if($feeBearer === MFeeBearer::PLATFORM or $feeBearer === MFeeBearer::CUSTOMER)
        {
            // converting fee bearer type to lower case, avoiding case sensitivity.
            $feeBearerType = strtolower($entry[Header::MIQ_UPI_FEE_TYPE]);

            $input[PricingEntity::FEE_BEARER] = $feeBearer ?? MFeeBearer::PLATFORM;

            if ((strtolower($entry[Header::MIQ_UPI]) !== UConstants::FEE_TYPE_NA) and ($feeBearerType === UConstants::FEE_TYPE_PERCENT
                    or $feeBearerType === UConstants::FEE_TYPE_FLAT))
            {
                $rule = $input; // copying here to create concrete rule input array.

                if($feeBearerType === UConstants::FEE_TYPE_PERCENT)
                {
                    $rule[PricingEntity::PERCENT_RATE] = round($entry[Header::MIQ_UPI], 2) * 100;
                }
                else
                {
                    $rule[PricingEntity::FIXED_RATE] = round($entry[Header::MIQ_UPI], 2) * 100;
                }

                $rules[]=$rule;
            }
        }

        return $rules;
    }

    private function getWalletRuleInput(array $entry): array
    {
        $rules = array();

        $input = $this->getCommonRuleInput();

        $input[PricingEntity::PAYMENT_METHOD] = 'wallet';

        $feeBearer = $entry[Header::MIQ_WALLETS_FEE_BEARER];

        if($feeBearer === MFeeBearer::PLATFORM or $feeBearer=== MFeeBearer::CUSTOMER)
        {
            // converting fee bearer type to lower case, avoiding case sensitivity.
            $feeBearerType = strtolower($entry[Header::MIQ_WALLETS_FEE_TYPE]);

            $input[PricingEntity::FEE_BEARER ] = $feeBearer?? MFeeBearer::PLATFORM;

            foreach (self::$walletPricingMapping as $key => $value)
            {
                if ((strtolower($entry[$key]) !== UConstants::FEE_TYPE_NA) and ($feeBearerType === UConstants::FEE_TYPE_PERCENT or $feeBearerType === UConstants::FEE_TYPE_FLAT))
                {
                    $rule = $input; // copying here to create concrete rule input array.

                    if($feeBearerType === UConstants::FEE_TYPE_PERCENT)
                    {
                        $rule[PricingEntity::PERCENT_RATE] = round($entry[$key], 2) * 100;
                    }
                    else
                    {
                        $rule[PricingEntity::FIXED_RATE] = round($entry[$key], 2) * 100;
                    }

                    $rule[PricingEntity::PAYMENT_NETWORK] = $value;

                    if ($key === Header::MIQ_WALLETS_ANY)
                    {
                        $rule[PricingEntity::PAYMENT_NETWORK] = '';
                    }

                    $rules[]=$rule;
                }
            }
        }

        return $rules;
    }

    private function getCardRuleInput(array $entry): array
    {
        $rules = array();

        $input = $this->getCommonRuleInput();

        $input[PricingEntity::PAYMENT_METHOD] = MethodEntity::CARD;

        foreach (self::$cardPricingMapping as $key => $value)
        {
            $feeBearerHeader = $value[UConstants::PRICING_FEE_BEARER];

            // converting fee bearer type to lower case, avoiding case sensitivity.
            // possible values of key - Percent, Flat, NA
            $feeBearerType = strtolower($entry[$key]);

            $feeBearer = $entry[$feeBearerHeader];

            if (($feeBearerType === UConstants::FEE_TYPE_FLAT or $feeBearerType === UConstants::FEE_TYPE_PERCENT) and
                ($feeBearer === MFeeBearer::CUSTOMER or $feeBearer === MFeeBearer::PLATFORM))
            {
                $input[PricingEntity::PAYMENT_NETWORK] = $value[UConstants::PRICING_NETWORK];

                $input[PricingEntity::PAYMENT_METHOD_TYPE] = $value[UConstants::PRICING_METHOD_TYPE];

                $input[PricingEntity::PAYMENT_METHOD_SUBTYPE] = $value[UConstants::PRICING_METHOD_SUBTYPE];

                $input[PricingEntity::FEE_BEARER] = $feeBearer  ?? MFeeBearer::PLATFORM;

                foreach ($value[UConstants::PRICING_AMOUNT_RANGES] as $rangeHeader => $rangeValues)
                {
                    if(strtolower($entry[$rangeHeader]) !== UConstants::FEE_TYPE_NA)
                    {
                        $rule = $input; // copying here to create concrete rule input array.

                        if(strtolower($entry[$key]) === UConstants::FEE_TYPE_PERCENT)
                        {
                            $rule[PricingEntity::PERCENT_RATE] = round($entry[$rangeHeader], 2) * 100;
                        }
                        else
                        {
                            $rule[PricingEntity::FIXED_RATE] = round($entry[$rangeHeader], 2) * 100;
                        }

                        if($value[UConstants::PRICING_AMOUNT_RANGE_ACTIVE] === '1')
                        {
                            $rule[PricingEntity::AMOUNT_RANGE_MIN] = $rangeValues[UConstants::PRICING_AMOUNT_RANGE_MIN];

                            $rule[PricingEntity::AMOUNT_RANGE_MAX] = $rangeValues[UConstants::PRICING_AMOUNT_RANGE_MAX];

                            $rule[PricingEntity::AMOUNT_RANGE_ACTIVE] = $value[UConstants::PRICING_AMOUNT_RANGE_ACTIVE];
                        }

                        if($key === Header::MIQ_INTL_CARD_FEE_TYPE)
                        {
                            $rule[PricingEntity::INTERNATIONAL] = '1';
                        }

                        $rules[]=$rule;
                    }
                }
            }
        }

        return $rules;
    }

    private function getCommonRuleInput(): array
    {
        return [
            PricingEntity::PRODUCT           => ProductConstants::PRIMARY,
            PricingEntity::FEATURE           => 'payment',
            PricingEntity::TYPE              => 'pricing',
            PricingEntity::INTERNATIONAL     => '0',
        ];
    }

    public function getPricingRulesInput(array $entry): array
    {
        $rules = [];
        // upi
        $upiRules = $this->getUpiRuleInput($entry);
        if(empty($upiRules) ===  false)
        {
            array_push($rules, ...$upiRules); // appending
        }

        // wallet
        $walletRules = $this->getWalletRuleInput($entry);
        if(empty($walletRules) ===  false)
        {
            array_push($rules, ...$walletRules); // appending
        }

        // net-banking
        $netBankingRules = $this->getNBRuleInput($entry);
        if(empty($netBankingRules) ===  false)
        {
            array_push($rules, ...$netBankingRules); // appending
        }

        // card
        $cardRules = $this->getCardRuleInput($entry);
        if(empty($cardRules) ===  false)
        {
            array_push($rules, ...$cardRules); // appending
        }

        return $rules;
    }

    public function filterRules( &$rules, $entry): void
    {
        $this->filterUpiRules($rules,$entry);

        $this->filterWalletRules($rules,$entry);

        $this->filterNetBankingRules($rules,$entry);
    }
    public function filterUpiRules(&$rules, $entry): void
    {
        $filteredRules = $rules;

        $feeBearer = strtolower($entry[Header::MIQ_UPI_FEE_BEARER]);
        $feeType = strtolower($entry[Header::MIQ_UPI_FEE_TYPE]);

        if($this->isValidFilter($feeBearer, $feeType)) {
            $upiRules = $this->filterMethodRules($filteredRules, MethodEntity::UPI);
            $this->updateRules($rules, $upiRules, $feeBearer, $feeType, $entry[Header::MIQ_UPI]);
        }
    }

    public function filterWalletRules( &$rules, $entry): void
    {
        $filteredRules = $rules;

        $feeBearer = strtolower($entry[Header::MIQ_WALLETS_FEE_BEARER]);
        $feeType = strtolower($entry[Header::MIQ_WALLETS_FEE_TYPE]);

        if($this->isValidFilter($feeBearer, $feeType)) {
            $walletRules = $this->filterMethodRules($filteredRules, 'wallet');

            if (strtolower($entry[Header::MIQ_WALLETS_FREECHARGE]) !== 'na') {
                $freeChargeWalletRules = $this->filterNetworkRules($walletRules, 'wallet', MethodEntity::FREECHARGE);
                $this->updateRules($rules, $freeChargeWalletRules, $feeBearer, $feeType, $entry[Header::MIQ_WALLETS_FREECHARGE]);
            }

            if (strtolower($entry[Header::MIQ_WALLETS_ANY]) !== 'na') {
                $this->updateAnyRules($rules, $walletRules, $feeBearer, $feeType, $entry[Header::MIQ_WALLETS_ANY]);
            }
        }
    }

    public function filterNetBankingRules( &$rules, $entry): void
    {
        $filteredRules = $rules;

        $feeBearer = strtolower($entry[Header::MIQ_NB_FEE_BEARER]);
        $feeType = strtolower($entry[Header::MIQ_NB_FEE_TYPE]);

        if($this->isValidFilter($feeBearer, $feeType))
        {
            $netBankingRules = $this->filterMethodRules($filteredRules, MethodEntity::NETBANKING);
            foreach (self::$netBankingPricingMapping as $key => $value)
            {
                if($feeBearer != 'na')
                {
                    if (($key === Header::MIQ_NB_ANY && strtolower($entry[$key]) != 'na'))
                    {
                        $this->updateAnyRules($rules, $netBankingRules, $feeBearer, $feeType, $entry[Header::MIQ_NB_ANY]);
                    }
                    else if (strtolower($entry[$key]) != 'na')
                    {
                        $netBankingNetworkRules = $this->filterNetworkRules($rules, MethodEntity::NETBANKING, $value);
                        $this->updateRules($rules, $netBankingNetworkRules, $feeBearer, $feeType, $entry[$key]);
                    }
                }
            }
        }
    }

    public function filterCardsRules( &$rules, $entry): void
    {
        foreach (self::$cardPricingMapping as $key => $value)
        {
            $filteredRules = $rules;
            $feeBearerHeader = $value[UConstants::PRICING_FEE_BEARER];
            $feeBearer = strtolower($entry[$feeBearerHeader]);
            $feeType = strtolower($entry[$key]);

            if ($feeBearer != 'na' || $feeType != 'na') {
                if (isset($value[UConstants::PRICING_METHOD_TYPE])) {
                    $cardRules = $this->filterMethodTypeRules($filteredRules, MethodEntity::CARD, $value[UConstants::PRICING_METHOD_TYPE]);
                    if ($value[UConstants::PRICING_METHOD_TYPE] === CardType::DEBIT && $feeBearer === Header::MIQ_RUPAY_FEE_BEARER) {
                        $cardRules = $this->filterNetworkRules($cardRules, MethodEntity::CARD, $value[UConstants::PRICING_NETWORK]);
                    }
                }

                if ($value[UConstants::PRICING_METHOD_SUBTYPE] === 'business') {
                    $cardRules = $this->filterMethodSubTypeRules($filteredRules, MethodEntity::CARD, $value[UConstants::PRICING_METHOD_SUBTYPE]);
                }

                //add for international
                if ($value[UConstants::INTERNATIONAL] === '1') {
                    $cardRules = $this->filterInternationalRules($filteredRules, MethodEntity::CARD, $value[UConstants::INTERNATIONAL]);
                }

                foreach ($value[UConstants::PRICING_AMOUNT_RANGES] as $rangeHeader => $rangeValues) {
                    $this->updateRules($rules, $cardRules, $feeBearer, $feeType, $entry[$rangeHeader]);
                }
            }
        }
    }

    public function updateAnyRules( &$rules, $filteredRules, $feeBearer, $feeType, $amount ): void
    {
        foreach ($filteredRules as $filteredRule)
        {
            foreach ($rules as &$rule)
            {
                if (($rule['id'] == $filteredRule['id']) && ($rule[PricingEntity::PAYMENT_NETWORK] == null))
                {
                    if (($feeBearer !== 'na') && (in_array($feeBearer, [MFeeBearer::PLATFORM, MFeeBearer::CUSTOMER])))
                    {
                        $rule[PricingEntity::FEE_BEARER] = $feeBearer;
                    }
                    if (($feeType !== 'na') && (strtolower($amount) !== 'na')) {
                        $this->updateAmount($feeType, $amount, $rule);
                    }
                }
            }
        }
    }


    public function updateRules( &$rules, $filteredRules, $feeBearer, $feeType, $amount ): void
    {
        // Index rules by ID for faster lookup
        $indexedRules = [];
        foreach ($rules as &$rule)
        {
            $indexedRules[$rule['id']] = &$rule;
        }
        // Iterate through filtered rules and update corresponding rules
        foreach ($filteredRules as $filteredRule)
        {
            if (isset($indexedRules[$filteredRule['id']]))
            {
                $rule = &$indexedRules[$filteredRule['id']];

                if (($feeBearer !== 'na') && (in_array($feeBearer, [MFeeBearer::PLATFORM, MFeeBearer::CUSTOMER])))
                {
                    $rule[PricingEntity::FEE_BEARER] = $feeBearer;
                }

                if (($feeType !== 'na') && (strtolower($amount) !== 'na'))
                {
                    $this->updateAmount($feeType, $amount, $rule);
                }
            }
        }
    }

    public function isvalidFilter($feeBearer,$feeType):bool
    {
        return ($feeBearer!=='na' || $feeType!=='na');
    }

    public function filterMethodRules($rules, $method)
    {
        $filters = [
            [PricingEntity::PAYMENT_METHOD, $method, false, null],
            [PricingEntity::FEATURE, 'payment', false, null],
        ];

        return $this->applyFiltersOnRules($rules, $filters);
    }

    public function filterNetworkRules($rules, $method, $network){
        $filters = [
            [PricingEntity::PAYMENT_METHOD, $method, false, null],
            [PricingEntity::FEATURE, 'payment', false, null],
            [PricingEntity::PAYMENT_NETWORK, $network, false, null]
        ];

         return $this->applyFiltersOnRules($rules, $filters);
    }

    public function filterMethodTypeRules($rules, $method, $method_type){
        $filters = [
            [PricingEntity::PAYMENT_METHOD, $method, false, null],
            [PricingEntity::FEATURE, 'payment', false, null],
            [PricingEntity::PRODUCT, 'primary', false, null],
            [PricingEntity::PAYMENT_METHOD_TYPE, $method_type, true, null]
        ];
        return $this->applyFiltersOnRules($rules, $filters);
    }

    public function filterMethodSubTypeRules($rules, $method, $method_subtype){
        $filters = [
            [PricingEntity::PRODUCT, 'primary', false, null],
            [PricingEntity::PAYMENT_METHOD_SUBTYPE, $method_subtype, true, null]
        ];
        $this -> filterMethodRules($rules, $method);
        return $this->applyFiltersOnRules($rules, $filters);
    }

    public function filterInternationalRules(array &$rules, $method, $international){
        $filters = [
            [PricingEntity::PAYMENT_METHOD, $method, false, null],
            [PricingEntity::FEATURE, 'payment', false, null],
            [PricingEntity::INTERNATIONAL, $international, false, null]
        ];
        return $this->applyFiltersOnRules($rules, $filters);
    }

    protected function applyFiltersOnRules($rules, $filters)
    {
        foreach ($filters as $filter)
        {
            $rules = $this->filterRulesOnFieldByValue(
                $rules, $filter[0], $filter[1], $filter[2], $filter[3]);
        }
        return $rules;
    }

    protected function filterRulesOnFieldByValue(
        $rules,
        $fieldName,
        $fieldValue,
        $chooseDefault = true,
        $defaultValue = null)
    {
        $matchRules         = [];
        $defaultMatchRules  = [];

        foreach ($rules as $rule)
        {
            $value = $rule->getAttribute($fieldName);

            if ($value === $fieldValue)
            {
                $matchRules[] = $rule;
            }
            else if (($chooseDefault === true) and
                ($value === $defaultValue))
            {
                $defaultMatchRules[] = $rule;
            }
        }

        if (empty($matchRules))
        {
            if (empty($defaultMatchRules) === false)
            {
                $defaultMatchRules[0]->getAttribute('plan_id');;
            }
            return $defaultMatchRules;
        }
        return $matchRules;
    }
    public function updateAmount( $feeBearerType, $amount, &$rule ): void
    {
            if ($feeBearerType === UConstants::FEE_TYPE_PERCENT)
            {
                $rule[PricingEntity::PERCENT_RATE] = round($amount, 2) * 100;
                $rule[PricingEntity::FIXED_RATE] = 0;
            }
            else
            {
                $rule[PricingEntity::FIXED_RATE] = round($amount, 2) * 100;
                $rule[PricingEntity::PERCENT_RATE] = 0;
            }
    }
}
