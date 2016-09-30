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

    const PLATFORM_VALUES = array(
        self::BROWSER       => 1,
        self::MOBILE_SDK    => 2,
        self::CORDOVA       => 3,
        self::SERVER        => 4,
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
    const CS_CART       = 'cs_cart';
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

    public static function getStringForValue($value, array $map)
    {
        if ($value === null)
        {
            return null;
        }

        $values = array_flip($map);

        return array_key_exists($value, $values) ? $values[$value] : self::OTHERS;
    }

    public static function isInvalidValue($value)
    {
        return $value === self::OTHERS_VALUE;
    }

    public static function isValidIntegration($integration)
    {
        return array_key_exists($integration, self::INTEGRATION_VALUES);
    }

    public static function getValueForIntegration($integration)
    {
        $integration = strtolower($integration);

        if (self::isValidIntegration($integration))
        {
            return self::INTEGRATION_VALUES[$integration];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidPlatform($platform)
    {
        return array_key_exists($platform, self::PLATFORM_VALUES);
    }

    public static function getValueForPlatform($platform)
    {
        $platform = strtolower($platform);

        if (self::isValidPlatform($platform))
        {
            return self::PLATFORM_VALUES[$platform];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidOs($os)
    {
        return array_key_exists($os, self::OS_VALUES);
    }

    public static function getValueForOs($os)
    {
        $os = strtolower($os);

        if (self::isValidOs($os))
        {
            return self::OS_VALUES[$os];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidLibrary($library)
    {
        return array_key_exists($library, self::LIBRARY_VALUES);
    }

    public static function getValueForLibrary($library)
    {
        $library = strtolower($library);

        if (self::isValidlibrary($library))
        {
            return self::LIBRARY_VALUES[$library];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidBrowser($browser)
    {
        return array_key_exists($browser, self::BROWSER_VALUES);
    }

    public static function validateBrowser($browser)
    {
        $browser = strtolower($browser);

        if (self::isValidBrowser($browser) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_BROWSER);
        }
    }

    public static function getValueForBrowser($browser)
    {
        $browser = strtolower($browser);

        if (self::isValidBrowser($browser))
        {
            return self::BROWSER_VALUES[$browser];
        }

        return self::OTHERS_VALUE;
    }

    public static function isValidDevice($device)
    {
        return array_key_exists($device, self::DEVICE_VALUES);
    }

    public static function validateDevice($device)
    {
        $device = strtolower($device);

        if (self::isValidDevice($device) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_DEVICE);
        }
    }

    public static function getValueForDevice($device)
    {
        $device = strtolower($device);

        if (self::isValidDevice($device))
        {
            return self::DEVICE_VALUES[$device];
        }

        return self::OTHERS_VALUE;
    }
}