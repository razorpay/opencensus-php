<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use GuzzleHttp\Client as HttpClient;

/**
 * handles Farzi engineer coupons
 */
class Farzi
{
    const FARZI_COUPON_STAGE_URL = 'https://boat-api.farziengineer.co/discount';

    public function addFarziCoupon(string $code, string $cartId)
    {
        $body = [
            'code' => $code,
            'cartId' => $cartId
        ];

        $this->sendCouponRequest(
            json_encode($body),
            'POST'
        );
    }

    public function sendCouponRequest($body, string $method)
    {
        $headers = [
            'Content-type' => 'application/json',
        ];
        
        try
        {
            (new HttpClient)->request($method, self::FARZI_COUPON_STAGE_URL, [
                'headers' => $headers,
                'body' => $body
            ]);
        }
        catch (\Exception $e)
        {
            //Executing the next lines of code irrespective of the response
        }
    }
}