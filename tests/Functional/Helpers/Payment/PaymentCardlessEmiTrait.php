<?php

namespace RZP\Tests\Functional\Helpers\Payment;

use RZP\Models\Payment\Gateway;

trait PaymentCardlessEmiTrait
{
    public function runPaymentCallbackFlowCardlessEmi($response, & $callback = null, $gateway)
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

            $responseInput['request']['content']['ott'] = $responseContent['ott'];
            $responseInput['request']['content']['emi_duration'] = head($responseContent['emi_plans'])['duration'];

            $payment = $responseInput['request']['content'];

            $request = [
                'method'  => 'POST',
                'url'     => $responseInput['payment_create_url'],
                'content' => $payment
            ];

            if (Gateway::isCardlessEmiProviderAndRedirectFlowProvider($responseInput['request']['content']['provider']) === true)
            {
                $newRequest = $this->getFormRequestFromResponse($this->makeRequestParent($request)->getContent(), $url);

                $resp = $this->sendRequest($newRequest);

                $request = [
                    'url' => $newRequest['content']['callback_url'],
                    'content' => json_decode(($resp->getContent()), true),
                    'method' =>  'POST',
                ];

                $parsed_url = parse_url($newRequest['url']);

                if ($parsed_url['path'] === '/v1/gateway/mocksharp/payment')
                {
                    $request['content'] = ['status' => 'authorized'];
                }

                $resp = $this->sendRequest($request);

                $data = $this->getPaymentJsonFromCallback($resp->getContent());

                $resp->setContent($data);
            }
            else
            {
                $resp = $this->makeRequestParent($request);
            }

            return $resp;
        }
        else
        {


            if($response->getStatusCode()===302)
            {
                $parts = parse_url($response->getTargetUrl());

                parse_str($parts["query"],$outputContent);

                $dt=[
                    'url' => $response->getTargetUrl(),
                    'content' => $outputContent,
                    'method' => "POST",
                ];

                $resp = $this->sendRequest($dt);

                $request = [
                    'url' => $outputContent["callback_url"],
                    'content' => (array)json_decode(($resp->getContent())),
                    'method' =>  'POST',
                ];

                $resp = $this->sendRequest($request);

                $data = $this->getPaymentJsonFromCallback($resp->getContent());

                $resp->setContent($data);

                return $resp;
            }

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
}
