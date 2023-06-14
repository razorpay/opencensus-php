<?php

namespace RZP\Services;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class WhatCmsService extends Base\Service
{
    //plugin types
    const ARASTTA                   = 'Arastta';
    const EASYDIGITALDOWNLOADS      = 'EasyDigitalDownloads';
    const CSCART                    = 'CS-Cart';
    const GRAVITYFORMS              = 'gravityforms';
    const MAGENTO                   = 'Magento';
    const OPENCART                  = 'OpenCart';
    const PRESTASHOP                = 'PrestaShop';
    const SHOPIFY                   = 'Shopify';
    const WHMCS                     = 'WHMCS';
    const WIX                       = 'Wix';
    const WOOCOMMERCE               = 'WooCommerce';
    const WORDPRESS                 = 'WordPress';
    const BIGCOMMERCE               = 'BigCommerce';

    const PLUGIN_TYPES = [
        self::ARASTTA,
        self::EASYDIGITALDOWNLOADS,
        self::CSCART,
        self::GRAVITYFORMS,
        self::MAGENTO,
        self::OPENCART,
        self::PRESTASHOP,
        self::SHOPIFY,
        self::WHMCS,
        self::WIX,
        self::WOOCOMMERCE,
        self::WORDPRESS,
    ];

    const PLUGIN_TYPES_FOR_LEAD_SCORE = [
        self::MAGENTO,
        self::SHOPIFY,
        self::WOOCOMMERCE,
        self::BIGCOMMERCE,
    ];

    const merchantPluginTypesMap  = [
        [
            "name"              => self::ARASTTA,
            "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Arastta.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/arastta/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::EASYDIGITALDOWNLOADS,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/EasyDigitalDownload.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/easy-digital-downloads/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::CSCART,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/CSCart.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/cs-cart/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::GRAVITYFORMS,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/GravityForms.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/gravity-forms",
            "integration_url"   => ""
        ],
        [
            "name"              => self::MAGENTO,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/Magento.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/magento/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::OPENCART,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/OpenCart.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/open-cart/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::PRESTASHOP,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/Prestashop.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/prestashop/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::SHOPIFY,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/Shopify.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/shopify/",
            "integration_url"   => "%s/admin/settings/payments/alternative-providers/1058839"
        ],
        [
            "name"              => self::WHMCS,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/WHMCS.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/whmcs/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::WIX,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/Wix.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wix/",
            "integration_url"   => "https://support.wix.com/en/article/connecting-razorpay-as-a-payment-provider"
        ],
        [
            "name"              => self::WOOCOMMERCE,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/WooCommerce.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/woocommerce/",
            "integration_url"   => ""
        ],
        [
            "name"              => self::WORDPRESS,
            "icon"              =>"https://cdn.razorpay.com/static/assets/product-led-onboarding/Wordpress.svg",
            "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wordpress/",
            "integration_url"   => ""
        ],
    ];


    protected static $plugintypeIndexMap = [
        self::ARASTTA               => 1,
        self::EASYDIGITALDOWNLOADS  => 2,
        self::CSCART                => 3,
        self::GRAVITYFORMS          => 4,
        self::MAGENTO               => 5,
        self::OPENCART              => 6,
        self::PRESTASHOP            => 7,
        self::SHOPIFY               => 8,
        self::WHMCS                 => 9,
        self::WIX                   => 10,
        self::WOOCOMMERCE           => 11,
        self::WORDPRESS             => 12,
        null                        => 0,
    ];

    public static function getIndexFromKey(string $key)
    {
        return self::$plugintypeIndexMap[$key];
    }

    public function getPrecedenceOfPluginType($pluginType) : int
    {
        switch($pluginType)
        {
            case self::WOOCOMMERCE:
            case self::GRAVITYFORMS:
            case self::EASYDIGITALDOWNLOADS:
                return 2;
            case null:
                return 0;
            default:
                return 1;
        }
    }

    public function checkForPluginType(string $merchantId, string $websiteUrl)
    {
        $app = App::getFacadeRoot();

        $mock = $app['config']['services.whatCMS.mock'];

        if($mock === true)
        {
            return array('DummyTestPluginType', true);
        }

        $whatCMSResponse = (new WhatCmsClient())->getWebsiteInfo($websiteUrl);

        $this->trace->info(TraceCode::WHATCMS_RESPONSE, [
            "mid" => $merchantId,
            "response" => $whatCMSResponse
        ]);

        $pluginType = null;
        $ecommercePlugin = null;

        if ($whatCMSResponse !== null)
        {
            $pluginType = $this->getPluginType($whatCMSResponse);
            $ecommercePlugin = $this->getIfEcommercePlatformApplicable($whatCMSResponse);
        }

        return array($pluginType, $ecommercePlugin);
    }

    public function getPluginType($response)
    {
        $results = $response['results'];

        $cms = null;

        foreach($results as $result) {
            $pluginType = $result['name'];

            if(in_array($pluginType, self::PLUGIN_TYPES)) {

                if($this->getPrecedenceOfPluginType($pluginType) > $this->getPrecedenceOfPluginType($cms)){
                    $cms = $pluginType;
                }
            }
        }

        return $cms;
    }

    public function getIfEcommercePlatformApplicable($response) : bool
    {
        $results = $response['results'];

        $ecommercePlatform = false;

        foreach($results as $result) {
            $pluginType = $result['name'];

            if(in_array($pluginType, self::PLUGIN_TYPES_FOR_LEAD_SCORE)) {
                $ecommercePlatform = true;
                break;
            }
        }

        return $ecommercePlatform;
    }
}
