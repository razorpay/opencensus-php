<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Models\CardMandate;
use RZP\Constants\Entity as E;

trait PaymentMandateHQTrait
{
    protected function runPaymentCallbackFlowMandateHq($response, &$callback = null)
    {
        list ($url, $method, $content) = $this->getDataForGatewayRequest($response, $callback);

        $this->assertEquals('https://mandate-manager.stage.razorpay.in/issuer/hdfc_GX3VC146gmBVNe/hostedpage', $url);
        $this->assertEquals('get', $method);
        $this->assertEmpty($content);

        $payment = $this->getDbLastEntity(E::PAYMENT);

        $paymentId = $payment->getPublicId();

        $url = (new CardMandate\MandateHubs\MandateHQ\MandateHQ)->getRedirectUrlForPayment($paymentId);

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
