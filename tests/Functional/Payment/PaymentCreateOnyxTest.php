<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use Mockery;

class PaymentCreateOnyxTest extends TestCase
{
    use PaymentTrait;

    public function testOnyxProtocolPaymentAutomaticCallbackRoute()
    {
        $postContent = [
            'razorpay_payment_id' => 'abcde',
            'razorpay_order_id' => 'abcde',
            'razorpay_signature' => 'abcde'];

        //
        // @shk: https://api.razorpay.com/v1/payments/create/onyx?url=123&params={"a":1}&options={"key": "rzp_test_1DP5mmOlF5G5ag", "amount": 100}&back=http://qq.com
        // * url
        // * params
        // * options
        //

        $getUrlContent = [
            'request' => [
                'url' => 'http://random.com',
                'content' => [
                    'a' => 1,
                    'b' => 2,
                ],
                'method' => 'POST',
                'target' => '',
            ],
            'options' => [
                'key' => 'rzp_test_1DP5mmOlF5G5ag',
                'amount' => 100,
            ],
            'back' => 'http://abc.com',
        ];

        $params = http_build_query([
            'data' => base64_encode(json_encode($getUrlContent))
        ]);

        $url = 'v1/payments/create/onyx?' . $params;

        $request = ['url' => $url, 'content' => $postContent, 'method' => 'POST'];

        $response = $this->sendRequest($request);

        $body = $response->content();

        // @todo: Crawl the body and get the form. Then assert that the fields
        // exist as passed in post and get content.
    }
}
