<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use GuzzleHttp\Client as HttpClient;

/**
 * handles Farzi engineer coupons
 */
class Farzi
{

    const BeMinimalisticShopName = 'minimalistfphapi';

    public function addFarziCoupon(string $code, string $cartId,string $shopName)
    {
        $body = [
            'code' => $code,
            'cartId' => $cartId
        ];
        if($shopName == self::BeMinimalisticShopName)
        {
            $body['app'] = 'custom_app';
        }

        $this->sendCouponRequest(
            json_encode($body),
            'POST',
            $shopName
        );

    }

    public function sendCouponRequest($body, string $method, string $shopName)
    {
        $headers = [
            'Content-type' => 'application/json',
        ];

        try
        {
            (new HttpClient)->request($method, $this->getFarziUrl($shopName), [
                'headers' => $headers,
                'body' => $body
            ]);
        }
        catch (\Exception $e)
        {
            //Executing the next lines of code irrespective of the response
        }
    }

    private function getFarziUrl($shopName){
        return 'https://'.$shopName.'.farziengineer.co/discount';
    }
}
