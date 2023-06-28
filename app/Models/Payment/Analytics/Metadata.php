<?php

namespace RZP\Models\Payment\Analytics;

use RZP\Exception;
use RZP\Error\ErrorCode;

class Metadata
{
    const OTHERS = 'others';

    // Randomly picked a large number
    const OTHERS_VALUE = 99;

    // Platform values
    const BROWSER       = 'browser';
    const MOBILE_SDK    = 'mobile_sdk';
    const CORDOVA       = 'cordova';
    const SERVER        = 'server';

    const PLATFORM_VALUES = [
        self::BROWSER       => 1,
        self::MOBILE_SDK    => 2,
        self::CORDOVA       => 3,
        self::SERVER        => 4,
    ];

    // Device values
    const DESKTOP       = 'desktop';
    const TABLET        = 'tablet';
    const MOBILE        = 'mobile';

    const DEVICE_VALUES = [
        self::DESKTOP   => 1,
        self::TABLET    => 2,
        self::MOBILE    => 3
    ];

    // OS values
    const LINUX         = 'linux';
    const WINDOWS       = 'windows';
    const MACOS         = 'macos';
    const ANDROID       = 'android';
    const IOS           = 'ios';
    const UBUNTU        = 'ubuntu';

    const OS_VALUES = [
        self::LINUX       => 1,
        self::WINDOWS     => 2,
        self::MACOS       => 3,
        self::ANDROID     => 4,
        self::IOS         => 5,
        self::UBUNTU      => 6,
    ];

    // Library values
    //
    // Regular checkout: Our code, our design, most of our livelihood
    const CHECKOUTJS    = 'checkoutjs';
    // Custom checkout: Still our code, but not as good-looking
    const RAZORPAYJS    = 'razorpayjs';
    // Custom checkout (Android): Still our code, but not as good-looking
    const CUSTOM        = 'custom';
    // Fake checkout: Not our code, merchant imitating checkout on public auth
    const DIRECT        = 'direct';
    // Fake checkout: Not our code, merchant imitating checkout on private auth
    const S2S           = 's2s';
    // No checkout: Our code, merchant not involved, for push payments only
    const PUSH          = 'push';
    // Payment UI made for Legacy Browsers like IE 8
    const LEGACYJS      = 'legacyjs';

    const HOSTED        = 'hosted';
    const EMBEDDED      = 'embedded';

    const LIBRARY_VALUES = [
        self::CHECKOUTJS    => 1,
        self::RAZORPAYJS    => 2,
        self::S2S           => 3,
        self::CUSTOM        => 4,
        self::DIRECT        => 5,
        self::PUSH          => 6,
        self::LEGACYJS      => 7,
        self::HOSTED        => 8,
        self::EMBEDDED      => 9,
    ];

    // Browser values
    const CHROME        = 'chrome';
    const IE            = 'ie';
    const FIREFOX       = 'firefox';
    const SAFARI        = 'safari';
    const UCWEB         = 'ucweb';
    const OPERA         = 'opera';
    const EDGE          = 'edge';

    const BROWSER_VALUES = [
        self::CHROME          => 1,
        self::IE              => 2,
        self::FIREFOX         => 3,
        self::SAFARI          => 4,
        self::UCWEB           => 5,
        self::OPERA           => 6,
        self::EDGE            => 7,
    ];

    // Integration values

    const WOOCOMMERCE               = 'woocommerce';
    const MAGENTO                   = 'magento';
    const CSCART                    = 'cscart';
    const OPENCART                  = 'opencart';
    const SHOPIFY                   = 'shopify';
    const WHMCS                     = 'whmcs';
    const ARASTTA                   = 'arastta';
    const PRESTASHOP                = 'prestashop';
    const WIX                       = 'wix';
    const GRAVITYFORMS              = 'gravityforms';
    const WOOCOMMERCE_SUBSCRIPTION  = 'woocommerce-subscription';
    const EDD                       = 'edd';
    const QUICK_PAYMENT             = 'quick-payment';
    const MAGENTO_SUBSCRIPTION      = 'magento-subscription';
    const OPENCART_SUBSCRIPTION     = 'opencart-subscription';
    const BIGCOMMERCE               = 'bigcommerce';
    const DRUPAL                    = 'drupal';

    const INTEGRATION_VALUES = [
        self::WOOCOMMERCE               => 1,
        self::MAGENTO                   => 2,
        self::CSCART                    => 3,
        self::OPENCART                  => 4,
        self::SHOPIFY                   => 5,
        self::WHMCS                     => 6,
        self::ARASTTA                   => 7,
        self::PRESTASHOP                => 8,
        self::WIX                       => 9,
        self::GRAVITYFORMS              => 10,
        self::WOOCOMMERCE_SUBSCRIPTION  => 11,
        self::EDD                       => 12,
        self::QUICK_PAYMENT             => 13,
        self::MAGENTO_SUBSCRIPTION      => 14,
        self::OPENCART_SUBSCRIPTION     => 15,
        self::BIGCOMMERCE               => 16,
        self::DRUPAL                    => 17,
    ];

    // fraud detection keys
    const RISK_SCORE  = 'risk_score';
    const RISK_ENGINE = 'risk_engine';

    // types of risk engines
    const MAXMIND     = 'maxmind';
    const MAXMIND_V2  = 'maxmind_v2';
    const SHIELD      = 'shield';
    const SHIELD_V2   = 'shield_v2';

    // enum for risk engine
    const RISK_ENGINE_VALUES = [
        self::MAXMIND    => 1,
        self::SHIELD     => 2,
        self::MAXMIND_V2 => 3,
        self::SHIELD_V2  => 4,
    ];

    const ADDRESS_UNSUPPORTED_LIBRARIES = [
        self::PUSH,
        self::LEGACYJS,
    ];

    const OPGSP_SUPPORTED_LIBRARIES = [
        self::S2S,
    ];

    const ADDRESS_COLLECTION_VIA_REDIRECT_LIBS = [
        self::RAZORPAYJS,
        self::CUSTOM,
        self::DIRECT,
        self::EMBEDDED,
        self::S2S,
    ];

    const DCC_SUPPORTED_LIBRARIES = [
        self::RAZORPAYJS,
        self::CUSTOM,
        self::DIRECT,
        self::EMBEDDED,
    ];

    const DCC_SUPPORTED_LIBRARIES_ON_FEATURE_FLAG = [
        self::DIRECT,
    ];

    const SUPPORTED_LIBRARIES_FOR_INTERNATIONAL_APPS = [
        self::CHECKOUTJS,
        self::HOSTED,
        self::S2S,
        self::CUSTOM,
        self::DIRECT,
        self::RAZORPAYJS,
        self::EMBEDDED,
    ];

    public static function getStringForValue($value, array $map)
    {
        if ($value === null)
        {
            return null;
        }

        $values = array_flip($map);

        return $values[$value] ?? self::OTHERS;
    }

    public static function isInvalid($value)
    {
        return ($value === self::OTHERS);
    }

    public static function isValidIntegration($integration)
    {
        return isset(self::INTEGRATION_VALUES[$integration]);
    }

    public static function getValueForIntegration($integration)
    {
        if (empty($integration) === true)
        {
            return;
        }

        $integration = strtolower($integration);

        if (self::isValidIntegration($integration))
        {
            return self::INTEGRATION_VALUES[$integration];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidPlatform($platform)
    {
        return isset(self::PLATFORM_VALUES[$platform]);
    }

    public static function getValueForPlatform($platform)
    {
        if (empty($platform) === true)
        {
            return;
        }

        $platform = strtolower($platform);

        if (self::isValidPlatform($platform))
        {
            return self::PLATFORM_VALUES[$platform];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidOs($os)
    {
        return isset(self::OS_VALUES[$os]);
    }

    public static function getValueForOs($os)
    {
        if (empty($os) === true)
        {
            return;
        }

        $os = strtolower($os);

        if (self::isValidOs($os))
        {
            return self::OS_VALUES[$os];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidLibrary($library)
    {
        return isset(self::LIBRARY_VALUES[$library]);
    }

    public static function getValueForLibrary($library)
    {
        if (empty($library) === true)
        {
            return;
        }

        $library = strtolower($library);

        if (self::isValidLibrary($library))
        {
            return self::LIBRARY_VALUES[$library];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidBrowser($browser)
    {
        return isset(self::BROWSER_VALUES[$browser]);
    }

    public static function getValueForBrowser($browser)
    {
        if (empty($browser) === true)
        {
            return;
        }

        $browser = strtolower($browser);

        if (self::isValidBrowser($browser))
        {
            return self::BROWSER_VALUES[$browser];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidDevice($device)
    {
        return isset(self::DEVICE_VALUES[$device]);
    }

    public static function getValueForDevice($device)
    {
        if ($device === null)
        {
            return;
        }

        $device = strtolower($device);

        if (self::isValidDevice($device))
        {
            return self::DEVICE_VALUES[$device];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidRiskEngine($engine)
    {
        return isset(self::RISK_ENGINE_VALUES[$engine]);
    }

    public static function getValueForRiskEngine($engine)
    {
        if ($engine === null)
        {
            return;
        }

        $engine = strtolower($engine);

        if (self::isValidRiskEngine($engine))
        {
            return self::RISK_ENGINE_VALUES[$engine];
        }

        return self::OTHERS_VALUE;
    }
}
