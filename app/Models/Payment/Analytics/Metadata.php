<?php

namespace RZP\Models\Payment\Analytics;

class Metadata
{
    // Platform values
    const BROWSER       = 'browser';
    const MOBILE_SDK    = 'mobile_sdk';
    const CORDOVA       = 'cordova';
    const SERVER        = 'server';

    const PLATFORM_VALUES = array(
        self::BROWSER       => 1,
        self::MOBILE_SDK    => 2,
        self::CORDOVA       => 3,
        self::SERVER        => 4
    );

    // Device values
    const DESKTOP       = 'desktop';
    const TABLET        = 'tablet';
    const MOBILE        = 'mobile';

    const DEVICE_VALUES = array(
        self::DESKTOP   => 1,
        self::TABLET    => 2,
        self::MOBILE    => 3);

    // OS values
    const LINUX         = 'linux';
    const WINDOWS       = 'windows';
    const MACOS         = 'macos';
    const ANDROID       = 'android';
    const IOS           = 'ios';

    const OS_VALUES = array(
        self::LINUX       => 1,
        self::WINDOWS     => 2,
        self::MACOS       => 3,
        self::ANDROID     => 4,
        self::IOS         => 5,
    );

    // Library values
    const CHECKOUTJS    = 'checkoutjs';
    const RAZORPAYJS    = 'razorpayjs';
    const DIRECT        = 'direct';

    const LIBRARY_VALUES = array(
        self::CHECKOUTJS    => 1,
        self::RAZORPAYJS    => 2,
        self::DIRECT        => 3,
    );

    // Browser values
    const CHROME        = 'chrome';
    const IE            = 'ie';
    const FIREFOX       = 'firefox';
    const SAFARI        = 'safari';
    const UCWEB         = 'ucweb';
    const OPERA         = 'opera';
    const EDGE          = 'edge';

    const BROWSER_VALUES = array(
        self::CHROME          => 1,
        self::IE              => 2,
        self::FIREFOX         => 3,
        self::SAFARI          => 4,
        self::UCWEB           => 5,
        self::OPERA           => 6,
        self::EDGE            => 7,
    );

    // Integration values

    const WOO_COMMERCE  = 'woo_commerce';
    const MAGENTO       = 'magento';
    const CS_CART       = 'ca_cart';
    const OPEN_CART     = 'open_cart';
    const SHOPIFY       = 'shopify';
    const WHMCS         = 'whmcs';
    const ARASTTA       = 'arastta';
    const PRESTASHOP    = 'prestashop';

    const INTEGRATION_VALUES = array(
        self::WOO_COMMERCE  => 1,
        self::MAGENTO       => 2,
        self::CS_CART       => 3,
        self::OPEN_CART     => 4,
        self::SHOPIFY       => 5,
        self::WHMCS         => 6,
        self::ARASTTA       => 7,
        self::PRESTASHOP    => 8,
    );

    public static function validateIntegration($integration)
    {
        if (!$integration)
        {
            return true;
        }

        return array_key_exists(strtolower($integration), self::INTEGRATION_VALUES);
    }

    public static function getValueForIntegration($integration)
    {
        return (!$integration) ? null : self::INTEGRATION_VALUES[strtolower($integration)];
    }

    public static function getStringForIntegrationValue($value)
    {
        $values = array_flip(self::INTEGRATION_VALUES);

        return $values[strtolower($value)];
    }

    public static function validatePlatform($platform)
    {
        if (!$platform)
        {
            return true;
        }

        return array_key_exists(strtolower($platform), self::PLATFORM_VALUES);
    }

    public static function getValueForPlatform($platform)
    {
        return (!$platform) ? null : self::PLATFORM_VALUES[strtolower($platform)];
    }

    public static function getStringForPlatformValue($value)
    {
        $values = array_flip(self::PLATFORM_VALUES);

        return $values[strtolower($value)];
    }

    public static function validateOs($os)
    {
        if (!$os)
        {
            return true;
        }

        if (strtolower($os) === 'os x')
        {
            $os = self::MACOS;
        }

        return array_key_exists(strtolower($os), self::OS_VALUES);
    }

    public static function getValueForOs($os)
    {
        if (strtolower($os) === 'os x')
        {
            $os = self::MACOS;
        }

        return (!$os) ? null : self::OS_VALUES[strtolower($os)];
    }

    public static function validateLibrary($library)
    {
        return array_key_exists(strtolower($library), self::LIBRARY_VALUES);
    }

    public static function getValueForLibrary($library)
    {
        return (!$library) ? null : self::LIBRARY_VALUES[strtolower($library)];
    }

    public static function getStringForLibraryValue($value)
    {
        $values = array_flip(self::LIBRARY_VALUES);

        return $values[strtolower($value)];
    }

    public static function validateBrowser($browser)
    {
        if (!$browser)
        {
            return true;
        }

        return array_key_exists(strtolower($browser), self::BROWSER_VALUES);
    }

    public static function getValueForBrowser($browser)
    {
        return (!$browser) ? null : self::BROWSER_VALUES[strtolower($browser)];
    }

    public static function validateDevice($device)
    {
        if (!$device)
        {
            return true;
        }

        return array_key_exists(strtolower($device), self::DEVICE_VALUES);
    }

    public static function getValueForDevice($device)
    {

        return (!$device) ? null : self::DEVICE_VALUES[strtolower($device)];
    }
}