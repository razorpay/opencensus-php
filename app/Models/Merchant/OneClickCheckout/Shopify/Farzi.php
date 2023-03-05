<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use GuzzleHttp\Client as HttpClient;

/**
 * handles Farzi engineer coupons
 */
class Farzi
{

    const BeMinimalisticShopName = 'minimalistfphapi';
    const WowSkinShopName = 'wow-api';

    public function addFarziCoupon(string $code, string $cartId,string $shopName)
    {
        $body = $this->getBodyForFarziUrl($code, $cartId, $shopName);

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

    private function getBodyForFarziUrl($code, $cartId, $shopName){
        $body = [
            'code' => $code,
            'cartId' => $cartId
        ];
        switch($shopName)
        {
            case self::BeMinimalisticShopName : $body['app'] = 'custom_app';
            break;

            case self::WowSkinShopName : $body['storeId'] = '2';
            break;
        }
        return $body;
    }

    private function getFarziUrl($shopName){
        switch($shopName)
        {
            case self::WowSkinShopName : return 'https://'.$shopName.'.farziengineer.co/multistore/discount';

            default: return 'https://'.$shopName.'.farziengineer.co/discount';
        }
    }
}
