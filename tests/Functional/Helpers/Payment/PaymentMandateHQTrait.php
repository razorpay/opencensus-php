<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Models\CardMandate;

trait PaymentMandateHQTrait
{
    protected function runPaymentCallbackFlowMandateHq($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->assertEquals('https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage', $url);
        $this->assertEquals('get', $method);
        $this->assertEmpty($content);

        $content = $this->getJsonContentFromResponse($response, $callback);

        $paymentId = $content['payment_id'];

        $url = (new CardMandate\Core)->getRedirectUrlForPayment($paymentId);

        $approved = $this->mandateConfirm ?? 'true';

        $request = [
            'method'  => 'GET',
            'url'     => $url,
            'content' => [
                'approved' => $approved,
            ],
        ];

        return $this->makeRequestAndGetRawContent($request);
    }
}
