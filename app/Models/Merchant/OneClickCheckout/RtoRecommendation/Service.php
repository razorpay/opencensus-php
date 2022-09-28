<?php

namespace RZP\Models\Merchant\OneClickCheckout\RtoRecommendation;

use RZP\Models\Merchant\OneClickCheckout\Constants;
use RZP\Models\Merchant\OneClickCheckout\Native;
use RZP\Models\Merchant\OneClickCheckout\Woocommerce;
use RZP\Models\Merchant\OneClickCheckout\Shopify;
use RZP\Models\Base;


class Service extends Base\Service
{
    public function handleAction($data)
    {

        (new Validator())->validateInput('actionHandler', $data);

        switch ($data[Constants::PLATFORM])
        {
            case Constants::NATIVE:
                (new Native\Service())->updateOrderStatus($data);
                break;
            case Constants::WOOCOMMERCE;
                (new Woocommerce\Service())->updateOrderStatus($data);
                break;
            case Constants::SHOPIFY;
                if($data[Constants::ACTION] ===  Constants::APPROVE)
                {
                    (new Shopify\Service())->removeTag($data);
                }
                elseif($data[Constants::ACTION] ===  Constants::CANCEL)
                {
                    (new Shopify\Service())->cancelShopifyOrder($data);
                }
                elseif($data[Constants::ACTION] ===  Constants::HOLD)
                {
                    (new Shopify\Service())->addTag($data);
                }
                break;
        }
    }
}
