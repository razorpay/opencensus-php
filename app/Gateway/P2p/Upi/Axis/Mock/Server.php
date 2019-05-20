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

    public function transactionRaiseConcern($request)
    {
        $response = [
            Fields::STATUS           => 'SUCCESS',
            Fields::RESPONSE_CODE    => 'SUCCESS',
            Fields::RESPONSE_MESSAGE => 'SUCCESS',
            Fields::PAYLOAD      => [
                Fields::MERCHANT_ID                 => 'MERCHANT',
                Fields::MERCHANT_CHANNEL_ID         => 'MERCHANTAPP',
                Fields::MERCHANT_CUSTOMER_ID        => $request[Fields::MERCHANT_CUSTOMER_ID],
                Fields::QUERY_REFERENCE_ID          => 'QUERY' . str_random(10),
                Fields::QUERY_COMMENT               => $request[Fields::QUERY_COMMENT],
                Fields::GATEWAY_TRANSACTION_ID      => $request[Fields::UPI_REQUEST_ID],
                Fields::GATEWAY_REFERENCE_ID        => $request[Fields::UPI_RESPONSE_ID],
                Fields::GATEWAY_RESPONSE_CODE       => '00',
                Fields::GATEWAY_RESPONSE_MESSAGE    => 'Query raised successfully',
            ],
            Fields::UDF_PARAMETERS          => $request[Fields::UDF_PARAMETERS],
        ];

        $this->content($response, 'raise_concern');

        $response = $this->makeResponse($response);

        return $response;
    }

    public function transactionConcernStatus($request)
    {
        $response = [
            Fields::STATUS           => 'SUCCESS',
            Fields::RESPONSE_CODE    => 'SUCCESS',
            Fields::RESPONSE_MESSAGE => 'SUCCESS',
            Fields::PAYLOAD      => [
                Fields::MERCHANT_ID                 => 'MERCHANT',
                Fields::MERCHANT_CHANNEL_ID         => 'MERCHANTAPP',
                Fields::MERCHANT_CUSTOMER_ID        => $request[Fields::MERCHANT_CUSTOMER_ID],
                Fields::QUERY_REFERENCE_ID          => 'QUERY' . str_random(10),
                Fields::QUERY_COMMENT               => 'Query transaction status',
                Fields::GATEWAY_TRANSACTION_ID      => $request[Fields::UPI_REQUEST_ID],
                Fields::GATEWAY_REFERENCE_ID        => $request[Fields::UPI_RESPONSE_ID],
                Fields::GATEWAY_RESPONSE_CODE       => '105',
                Fields::GATEWAY_RESPONSE_MESSAGE    => 'Beneficiary account has already been credited.',
                Fields::QUERY_CLOSING_TIMESTAMP     => '2022-02-22',
            ],
            Fields::UDF_PARAMETERS          => $request[Fields::UDF_PARAMETERS],
        ];

        $this->content($response, 'concern_status');

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
