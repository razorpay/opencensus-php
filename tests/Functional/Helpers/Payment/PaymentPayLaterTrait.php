<?php

namespace RZP\Tests\Functional\Helpers\Payment;

trait PaymentPayLaterTrait
{
    public function runPaymentCallbackFlowPayLater($response, & $callback = null, $gateway)
    {
        list ($url, $method, $values) = $this->getDataForGatewayRequest($response, $callback);

        if ($this->isOtpVerifyUrl($url) === true)
        {
            $responseData = $this->processCardlessPaymentForm($response);

            $responseInput = json_decode($responseData->getContent(), true);

            $contact = $responseInput['request']['content']['contact'];
            $email   = $responseInput['request']['content']['email'];

            $this->callbackUrl = $url;

            $this->otpFlow = true;

            $response = $this->makeOtpVerifyCallback($url, $email, $contact);
            $responseContent =  json_decode($response->getContent(), true);

            switch ($email)
            {
                case 'invalidott@gmail.com':
                    $responseInput['request']['content']['ott'] = 'invalidott';
                    break;

                default:
                    $responseInput['request']['content']['ott'] = $responseContent['ott'];
            }

            $payment = $responseInput['request']['content'];

            $request = [
                'method'  => 'POST',
                'url'     => $responseInput['payment_create_url'],
                'content' => $payment
            ];

            return $this->makeRequestParent($request);
        }
        elseif ($this->isOtpCallbackUrl($url))
        {
            $this->callbackUrl = $url;

            return $this->makeOtpCallback($url);
        }
        else
        {
            $dt = $this->getFormRequestFromResponse($response->getContent(), $url);

            $resp = $this->sendRequest($dt);

            // array conversion is required because we are getting std class object after json_decode
            $request = [
                'url' => $dt['content']['callback_url'],
                'content' => (array) json_decode(($resp->getContent())),
                'method' => 'POST',
            ];

            $resp = $this->sendRequest($request);

            $data = $this->getPaymentJsonFromCallback($resp->getContent());

            $resp->setContent($data);

            return $resp;
        }
    }
}
