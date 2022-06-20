<?php

namespace RZP\Service;

use RZP\Models\Base;
use RZP\Services\WhatCmsClient;
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
        switch($pluginType){
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
        $whatcmsResponse = (new WhatCmsClient())->getWebsiteInfo($websiteUrl);

        $this->trace->info(TraceCode::WHATCMS_RESPONSE, [
            "mid" => $merchantId,
            "response" => $whatcmsResponse
        ]);

        $pluginType = null;

        if($whatcmsResponse !== null){
            $pluginType = $this->getPluginType($whatcmsResponse);
        }

        return $pluginType;
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
}
