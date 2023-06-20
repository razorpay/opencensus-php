<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;

class ShippingRates extends Base\Core
{
    const forceEnableCODMids = [];

    /**
     * allow Shopify merchants to activate COD without
     * using a cod plugin on Shopify
     * @param array pointer
     * @return void
     */
    public function forceEnableCODIfApplicable(array &$shippingResponse)
    {
        $merchantId = $this->merchant->getId();

        if (in_array($merchantId, self::forceEnableCODMids) === true)
        {
            $shippingResponse['cod'] = true;
            $shippingResponse['cod_fee'] = null;

            $multipleCOD = [];

            if(empty($shippingResponse['shipping_methods']) === false)
            {
                $shipMethods = $shippingResponse['shipping_methods'];

                foreach ($shipMethods as $shipMethod)
                {
                    $shipMethod['cod'] = true;
                    $shipMethod['cod_fee'] = null;
                    array_push($multipleCOD, $shipMethod);
                }
            }

            $shippingResponse['shipping_methods'] = $multipleCOD;
        }
    }
}
