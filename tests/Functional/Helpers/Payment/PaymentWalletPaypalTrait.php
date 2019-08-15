<?php


namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentWalletPaypalTrait
{

    public function runPaymentCallbackFlowWalletPaypal($response, & $callback , $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);
        s($url, $method, $values);
        s($gateway);
        $dt = $this->getFormRequestFromResponse($response->getContent(), $url);

        $resp = $this->sendRequest($dt);

        // array conversion is required because we are getting std class object after json_decode
        $request = [
            'url' => $dt['content']['callback_url'],
            'content' => (array)json_decode(($resp->getContent())),
            'method' =>  'POST',
        ];

        $resp = $this->sendRequest($request);

        $data = $this->getPaymentJsonFromCallback($resp->getContent());

        $resp->setContent($data);

        return $resp;
    }
}