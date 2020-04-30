<?php


namespace RZP\Models\Gateway\Downtime;


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
    ];

    public static function getMaxSingleMerchantContribution()
    {
        return self::MAX_SINGLE_MERCHANT_CONTRIBUTION;
    }

    public static function getAllJobTypes()
    {
        return self::$allJobTypes;
    }
}