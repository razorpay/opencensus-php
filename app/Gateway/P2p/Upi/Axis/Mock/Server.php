<?php

namespace RZP\Gateway\P2p\Upi\Axis\Mock;

use RZP\Gateway\P2p\Base\Mock;
use RZP\Gateway\P2p\Upi\Axis\Fields;

class Server extends Mock\Server
{
    public function setMockRequest($request)
    {
        parent::setMockRequest($request);

        $content = json_decode($request['content'], true);

        return $content;
    }

    public function deviceDeregister($request)
    {
        $response = [
            Fields::STATUS           => 'SUCCESS',
            Fields::RESPONSE_CODE    => 'SUCCESS',
            Fields::RESPONSE_MESSAGE => 'SUCCESS',
            Fields::PAYLOAD      => [
                Fields::MERCHANT_ID => 'MERCHANT',
                Fields::MERCHANT_CHANNEL_ID     => 'MERCHANTAPP',
                Fields::MERCHANT_CUSTOMER_ID    => $request[Fields::MERCHANT_CUSTOMER_ID],
                Fields::CUSTOMER_MOBILE_NUMBER  => $request[Fields::CUSTOMER_MOBILE_NUMBER],
            ],
            Fields::UDF_PARAMETERS          => $request[Fields::UDF_PARAMETERS],
        ];

        $this->content($response, 'deregister');

        $response = $this->makeResponse($response);

        return $response;
    }

    public function vpaValidate($request)
    {
        $response = [
            Fields::STATUS           => 'SUCCESS',
            Fields::RESPONSE_CODE    => 'SUCCESS',
            Fields::RESPONSE_MESSAGE => 'SUCCESS',
            Fields::PAYLOAD          => [
                Fields::MERCHANT_ID             => 'MERCHANT',
                Fields::MERCHANT_CHANNEL_ID     => 'MERCHANTAPP',
                Fields::IS_CUSTOMER_VPA_VALID   => true,
                Fields::CUSTOMER_VPA            => $request[Fields::CUSTOMER_VPA],
                Fields::CUSTOMER_NAME           => 'Razorpay Customer',
            ],
            Fields::UDF_PARAMETERS   => $request[Fields::UDF_PARAMETERS],
        ];

        $this->content($response, 'validate');

        $response = $this->makeResponse($response);

        return $response;
    }

    protected function makeResponse($input)
    {
        $response = new \Requests_Response();

        $response->headers = ['content-type' => 'application/json'];

        $response->body = json_encode($input);

        return $response;
    }
}
