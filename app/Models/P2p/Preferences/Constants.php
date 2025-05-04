<?php

namespace RZP\Models\P2p\Preferences;

use RZP\Models\P2p\BankAccount\Type as AccountType;

class Constants
{
    const MERCHANT     = 'merchant';
    const FEATURES     = 'features';
    const MERCHANT_ID  = 'merchant_id';
    const DISPLAY_NAME = 'display_name';
    const PAYER_ACCOUNT_TYPE_MAPPINGS = 'payer_account_type_mappings';
    const DISPLAY_CONTROLS = 'DisplayControls';
    const REWARD_CONFIGS = 'RewardConfigs';

    // Timeout related constants
    const TIMEOUTS           = 'timeouts';
    const OLIVE_SDK_TIMEOUT  = 'olive_sdk_timeout';
    const SENTRY_CONFIG      = 'configs';
    const UPI_TURBO_SENTRY_TXN_SAMPLING_RATE = 'sentry_txn_sampling_rate';

    const SUPPORTED_PAYER_ACCOUNT_TYPES = 'supported_payer_account_types';

    const ORDER_ID_NOT_BELONG_TO_CUSTOMER = 'order_id does not belong to the customer. Please check the order ID and try again.';

    const TURBO_PAYEE_EXECUTION_HOLD_TIME               = 2;
    const TURBO_GATEWAY_TXN_HOLD_TTL                    = 600;
    const TURBO_PAYEE_PAYMENT_CREATED_AT_RANGE          = 2;
    const TURBO_PAYMENT_LOOK_UP_BACK_SECONDS            = 20;

    // Autopay constants
    const AUTOPAY_ENABLED = 'autopay_enabled';

    // Prefetch Constants
    const PREFETCH           = 'prefetch';
    const CONSENT_MESSAGE    = 'consent_message';
    const FETCH_RETRY        = 'fetch_retry';
    const FETCH_CONCURRENT   = 'fetch_concurrent';

    const FETCH_TIMEOUT      = 'fetch_timeout';

    const BANKS              = 'banks';

    const FUNDSOURCE_METADATA = 'fundsource_metadata';
    const SOD = 'SOD';

    private static array $defaultTimeouts = [
        self::OLIVE_SDK_TIMEOUT => 30
    ];

    private static array $defaultSamplingRate = [
        self::UPI_TURBO_SENTRY_TXN_SAMPLING_RATE => 0.005
    ];

    const METADATA     = 'metadata';
    const X_PG_SERVICE = 'X-PG-Service';
    const API          = 'api';
    private static array $popularBanksListInProd = [
        [
            "priority"     => "0",
            "iin"          => "607153",
            "display_name" => "AXIS",
            "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
        ],
        [
            "priority"     => "1",
            "iin"          => "607152",
            "display_name" => "HDFC",
            "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
        ],
        [
            "priority"     => "2",
            "iin"          => "508534",
            "display_name" => "ICICI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/ICIC.gif"
        ],
        [
            "priority"     => "3",
            "iin"          => "508548",
            "display_name" => "SBI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/SBIN.gif",
        ],
        [
            "priority"     => "4",
            "iin"          => "607420",
            "display_name" => "Kotak",
            "bank_logo"    => "https://cdn.razorpay.com/bank/KKBK.gif"
        ],
        [
            "priority"     => "5",
            "iin"          => "508568",
            "display_name" => "PNB",
            "bank_logo"    => "https://cdn.razorpay.com/bank/PUNB.gif"
        ],
        [
            "priority"     => "6",
            "iin"          => "606985",
            "display_name" => "BOB",
            "bank_logo"    => "https://cdn.razorpay.com/bank/BARB.gif"
        ],
        [
            "priority"     => "7",
            "iin"          => "607189",
            "display_name" => "INDUSIND",
            "bank_logo"    => "https://cdn.razorpay.com/bank/INDB.gif"
        ]
    ];

    private static array $popularBanksListInUAT = [
        [
            "priority"     => "0",
            "iin"          => "607153",
            "display_name" => "AXIS",
            "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
        ],
        [
            "priority"     => "1",
            "iin"          => "901345",
            "display_name" => "HDFC",
            "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
        ],
        [
            "priority"     => "2",
            "iin"          => "508534",
            "display_name" => "ICICI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/ICIC.gif"
        ],
        [
            "priority"     => "3",
            "iin"          => "508548",
            "display_name" => "SBI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/SBIN.gif",
        ],
        [
            "priority"     => "4",
            "iin"          => "190070",
            "display_name" => "Kotak"
        ],
        [
            "priority"     => "5",
            "iin"          => "189025",
            "display_name" => "PNB"
        ],
        [
            "priority"     => "6",
            "iin"          => "612353",
            "display_name" => "BOB"
        ],
        [
            "priority"     => "7",
            "iin"          => "612355",
            "display_name" => "INDUSIND"
        ]
    ];

    public static array $prefetchBankListInProd = [
        [
            "priority"     => "0",
            "iin"          => "508548",
            "display_name" => "SBI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/SBIN.gif"
        ],
        [
            "priority"     => "1",
            "iin"          => "607152",
            "display_name" => "HDFC",
            "bank_logo"    => "https://cdn.razorpay.com/bank/HDFC.gif"
        ],
        [
            "priority"     => "2",
            "iin"          => "508534",
            "display_name" => "ICICI",
            "bank_logo"    => "https://cdn.razorpay.com/bank/ICIC.gif"
        ],
        [
            "priority"     => "3",
            "iin"          => "607420",
            "display_name" => "Kotak",
            "bank_logo"    => "https://cdn.razorpay.com/bank/KKBK.gif"
        ],
        [
            "priority"     => "4",
            "iin"          => "607153",
            "display_name" => "Axis",
            "bank_logo"    => "https://cdn.razorpay.com/bank/UTIB.gif"
        ],
        [
            "priority"     => "5",
            "iin"          => "607095",
            "display_name" => "IDBI",
            "bank_logo"    => "https://www.axisbank.com/bank_logos/idbi.png"
        ],
        [
            "priority"     => "6",
            "iin"          => "508568",
            "display_name" => "PNB",
            "bank_logo"    => "https://cdn.razorpay.com/bank/PUNB.gif"
        ],
        [
            "priority"     => "7",
            "iin"          => "607189",
            "display_name" => "IndusInd",
            "bank_logo"    => "https://cdn.razorpay.com/bank/INDB.gif"
        ]
    ];

    public static array $prefetchBankListInUAT = [
        [
            "priority"     => "0",
            "iin"          => "607153",
            "display_name" => "Axis",
        ],
        [
            "priority"     => "1",
            "iin"          => "504432",
            "display_name" => "MyPSP",
        ],
        [
            "priority"     => "2",
            "iin"          => "508548",
            "display_name" => "SBI",
            "bank_logo" => "https://cdn.razorpay.com/bank/SBIN.gif"
        ]
    ];


    private static $supportedPayerAccountTypes = [
        AccountType::SAVINGS,
        AccountType::CURRENT,
    ];

    private static array $payerAccountTypeMappings = [
        'p2m_upi_axis_olive' => [
            'credit' => 'credit_card',
            'current' => 'bank_account',
            'savings' => 'bank_account',
        ],
        'default' => [
            'credit' => 'credit_card',
            'current' => 'bank_account',
            'savings' => 'bank_account',
        ]
    ];

    private static array $defaultPrefetchConfigs = [
        self::CONSENT_MESSAGE    => 'Automatically fetch & link my active UPI accounts from top banks',
        self::FETCH_RETRY        => 0,
        self::FETCH_CONCURRENT   => 10,
        self::FETCH_TIMEOUT      => 5,
    ];

    public static function getStaticPopularBanksList(): array
    {
        if((app()->isEnvironmentProduction() === true))
        {
            return self::$popularBanksListInProd;
        }
        else
        {
            return self::$popularBanksListInUAT;
        }
    }

    public static function getDefaultTimeoutsForSDK(): array
    {
        return self::$defaultTimeouts;
    }

    public static function getDefaultSamplingRate(): array
    {
        return self::$defaultSamplingRate;
    }

    public static function getSupportedPayerAccountTypes(): array
    {
        return self::$supportedPayerAccountTypes;
    }

    public static function getDefaultAutopayEnabledFeatureFlag(): bool
    {
        return false;
    }

    public static function getPayerAccountTypeMappings($gateway)
    {
        return self::$payerAccountTypeMappings[$gateway] ?? self::$payerAccountTypeMappings['default'];
    }

    public static function getDefaultPrefetchConfigs()
    {
        $defaultPrefetchConfigs = self::$defaultPrefetchConfigs;

        $defaultPrefetchConfigs[self::BANKS] = self::getPrefetchBankList();

        return $defaultPrefetchConfigs;
    }

    public static function getPrefetchBankList(): array
    {
        if((app()->isEnvironmentProduction() === true))
        {
            return self::$prefetchBankListInProd;
        }

        return self::$prefetchBankListInUAT;
    }

    public static function getInAppCreditFundSourceMetaData(): array
    {
        return self::$fundSourceMetaData;
    }

    public static array $fundSourceMetaData = [
        [
            "fundsource_provider_name" => "icici_bank",
            "otp_length" => "6",
            "iin" => "508534",
            "logo_url" => "https://www.axisbank.com/bank_logos/ICICI_logo.png",
            "activation_fee" => "₹500",
            "late_fee" => "₹100+3% interest",
            "service_charges" => [
                [
                    "monthly_utilised_amount" => "0-₹3000",
                    "fee" => "nil"
                ],
                [
                    "monthly_utilised_amount" => "₹3001-₹6000",
                    "fee" => "₹75+18% GST"
                ],
                [
                    "monthly_utilised_amount" => "₹6001-₹9000",
                    "fee" => "₹150+18% GST"
                ],
                [
                    "monthly_utilised_amount" => "₹9001-₹12000",
                    "fee" => "₹225+18% GST"
                ],
                [
                    "monthly_utilised_amount" => "₹12001-₹15000",
                    "fee" => "₹300+18% GST"
                ],
                [
                    "monthly_utilised_amount" => "₹15001-₹18000",
                    "fee" => "₹375+18% GST"
                ],
                [
                    "monthly_utilised_amount" => "₹18001&above",
                    "fee" => "₹450+18% GST"
                ]
            ]
        ]
    ];
}
