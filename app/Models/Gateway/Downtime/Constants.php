<?php


namespace RZP\Models\Gateway\Downtime;


use Razorpay\IFSC\Bank;
use RZP\Gateway\Upi\Base\ProviderCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Card\Issuer;
use RZP\Models\Card\Network;
use RZP\Models\Payment\Method;

class Constants
{
    const SETTINGS_KEY  = ConfigKey::DOWNTIME_DETECTION_CONFIGURATION_V2;

    const DOWNTIME_KEY  = 'DOWNTIME_CREATED';

    // In ratio to total payments
    const MAX_SINGLE_MERCHANT_CONTRIBUTION = 0.5;

    // Payment Methods
    const CARD = 'card';

    const UPI = 'upi';

    const NETBANKING = 'netbanking';

    // Card Types
    const CREDIT = 'credit';

    const DEBIT = 'debit';

    // UPI Flows

    const COLLECT = 'collect';

    const INTENT = 'intent';

    // Looker dashboard constants
    const LOOKER_NOTIFICATIONS_RAZORX_KEY = 'LOOKER_NOTIFICATIONS_RAZORX_KEY';

    const LOOKER_URL = 'https://looker.razorpay.com/dashboards/';

    const CARD_LOOKER_DASHBOARD = 3372;

    const UPI_LOOKER_DASHBOARD = 3374;

    const NETBANKING_LOOKER_DASHBOARD = 3377;

    const DEFAULT_LOOKER_DASHBOARD = 3372;

    const METHOD_FILTER = 'METHOD';

    const ISSUER_FILTER = 'ISSUER';

    const NETWORK_FILTER = 'NETWORK';

    const MERCHANT_ID_FILTER = 'MERCHANT_ID';

    const CREDIT_CARD_FILTER = 'Credit Card';

    const DEBIT_CARD_FILTER = 'Debit Card';

    const UPI_COLLECT_FILTER = 'UPI Collect';

    const UPI_INTENT_FILTER = 'UPI Intent';

    const FROM_10_MINUTES_FILTER = 'FROM=10 minutes';

    const FROM = 'From';

    const TO = 'to';

    // These Constants are used for FPX Downtime Creation
    const ACTIVE = "Active";

    const RETAIL = "Retail";

    const CORPORATE = "Corporate";

    const TRANSACTION_TYPE = "transaction_type";

    protected static $lookerDashboardForMethod = [
        Constants::CARD           => Constants::CARD_LOOKER_DASHBOARD,
        Constants::UPI            => Constants::UPI_LOOKER_DASHBOARD,
        Constants::NETBANKING     => Constants::NETBANKING_LOOKER_DASHBOARD,
    ];

    protected static $lookerCardFilters = [
        Constants::CREDIT           => Constants::CREDIT_CARD_FILTER,
        Constants::DEBIT            => Constants::DEBIT_CARD_FILTER,
        Constants::CARD             => Constants::CREDIT_CARD_FILTER . "," . Constants::DEBIT_CARD_FILTER,
    ];

    protected static $lookerUpiFilters = [
        Constants::COLLECT           => Constants::UPI_COLLECT_FILTER,
        Constants::INTENT            => Constants::UPI_INTENT_FILTER,
        Constants::UPI              => Constants::UPI_COLLECT_FILTER . "," . Constants::UPI_INTENT_FILTER,
    ];

    protected static $allJobTypes = [
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::SBIN,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::HDFC,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::ICIC,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::UTIB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::CITI,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::PUNB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::KKBK,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::CNRB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::BKID,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::ISSUER,
            'value' => Issuer::BARB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::NETWORK,
            'value' => Network::AMEX,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::NETWORK,
            'value' => Network::VISA,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::NETWORK,
            'value' => Network::MC,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::NETWORK,
            'value' => Network::DICL,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::CARD,
            'key' => DowntimeDetection::NETWORK,
            'value' => Network::RUPAY,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKHDFCBANK,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKAXIS,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKICICI,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKSBI,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::UPI,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::YBL,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            "method" => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::PAYTM,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            "method" => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::APL,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKHDFCBANK,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKAXIS,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKICICI,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::OKSBI,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::UPI,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::YBL,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::PAYTM,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::UPI,
            'key' => DowntimeDetection::PROVIDER,
            'value' => ProviderCode::APL,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::HDFC,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::SBIN,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::ALLA,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::ICIC,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::PUNB,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::UTIB,
        ],
        [
            'type' => DowntimeDetection::PAYMENT_INTERVAL,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::KKBK,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::HDFC,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::SBIN,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::ALLA,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::ICIC,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::PUNB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::UTIB,
        ],
        [
            'type' => DowntimeDetection::SUCCESS_RATE,
            'method' => Method::NETBANKING,
            'key' => DowntimeDetection::BANK,
            'value' => Bank::KKBK,
        ],
    ];

    public static function getMaxSingleMerchantContribution()
    {
        return self::MAX_SINGLE_MERCHANT_CONTRIBUTION;
    }

    public static function getAllJobTypes()
    {
        return self::$allJobTypes;
    }

    public static function getLookerDashboardForMethod()
    {
        return self::$lookerDashboardForMethod;
    }

    public static function getLookerCardFilters()
    {
        return self::$lookerCardFilters;
    }

    public static function getLookerUpiFilters()
    {
        return self::$lookerUpiFilters;
    }
}
