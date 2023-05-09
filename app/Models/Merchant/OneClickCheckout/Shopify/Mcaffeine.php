<?php

namespace RZP\Models\Merchant\OneClickCheckout\Shopify;

use GuzzleHttp\Client as HttpClient;

/**
 * handles Mcaffeine bulk  coupons
 */
class Mcaffeine
{

    public function addMcaffeineCoupon(string $code)
    {
        $body = [
            'coupon' => $code
        ];

        return $this->sendCreateCouponRequest(
            json_encode($body)
        );
    }

    protected function sendCreateCouponRequest($body)
    {
        $headers = [
            'Content-type' => 'application/json',
        ];

        try
        {
            $response = (new HttpClient)->request('POST', $this->getMcaffeineCreateShopifyCouponUrl(), [
                'headers' => $headers,
                'body' => $body
            ]);

            return json_decode($response->getBody(), true);
        }
        catch (\Exception $e)
        {
            return [
                'message' => $e,
            ];
        }
    }

    protected function getMcaffeineCreateShopifyCouponUrl(){
        return 'https://bulkcoupon.mcaffeine.com/api/createcoupononshopify';
    }
}
