<?php

namespace RZP\Models\Merchant\Detail\Upload\Processors;

use RZP\Models\Batch\Header;
use RZP\Models\Merchant\Detail;
use Razorpay\IFSC\Bank as Banks;
use RZP\Models\Merchant\Document;
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

    private static $documentsForKyc = [
        Document\Type::AADHAR_FRONT,
        Document\Type::AADHAR_BACK,
        Document\Type::BUSINESS_PROOF_URL,
        Document\Type::BUSINESS_PAN_URL,
        Document\Type::FORM_12A_URL,
        Document\Type::FORM_80G_URL
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

    private static $walletPricingMapping = [
        Header::MIQ_WALLETS_FREECHARGE  => MethodEntity::FREECHARGE,
        // fee to be charged remaining wallets
        Header::MIQ_WALLETS_ANY         => Header::MIQ_WALLETS_ANY,
    ];

    private static $netBankingPricingMapping = [
        Header::MIQ_AXIS        =>  Banks::UTIB,
        Header::MIQ_HDFC        =>  Banks::HDFC,
        Header::MIQ_ICICI       =>  Banks::ICIC,
        Header::MIQ_SBI         =>  Banks::SBIN,
        Header::MIQ_YES         =>  Banks::YESB,
        // fee to be charged remaining banks
        Header::MIQ_NB_ANY      =>  Header::MIQ_NB_ANY,
    ];

    private static $cardPricingMapping = [
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
            MDEntity::ACTIVATION_FORM_MILESTONE     => "L2",
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

                if ($entry[$key] !='' and ($feeBearerType === UConstants::FEE_TYPE_FLAT  or
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

            if ($entry[Header::MIQ_UPI] !='' and ($feeBearerType === UConstants::FEE_TYPE_PERCENT
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
                if ($entry[$key] !='' and ($feeBearerType === UConstants::FEE_TYPE_PERCENT or $feeBearerType === UConstants::FEE_TYPE_FLAT))
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
                    if($entry[$rangeHeader] != '')
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

    public static function getDummyActivationFiles(): array
    {
        $merchantDocuments = [];

        foreach (self::$documentsForKyc as $document)
        {
            $merchantDocuments[$document] = [
                Document\Constants::FILE_ID => Detail\Constants::DUMMY_ACTIVATION_FILE,
                Document\Constants::SOURCE  => Document\Source::UFH,
            ];
        }
        return $merchantDocuments;
    }
}
