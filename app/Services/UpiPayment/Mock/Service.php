<?php

namespace RZP\Services\UpiPayment\Mock;

use Requests_Response;
use RZP\Services\UpiPayment\Service as UpiPaymentService;

/**
 * Service implements all the UPS actions
 */
class Service extends UpiPaymentService
{
    /**
     * Mocks sending request to UPS
     *
     * @param array $request
     * @return void
     */
    protected function sendRawRequest(array $request)
    {
        $action  = camel_case(explode('/', $request['url'])[3]);

        $response = $this->$action();

        return $response;
    }

    /**
     * Authorize returns the authorize response
     *
     * @return void
     */
    protected function authorize()
    {
        $response = ['data' => ['vpa' => 'razorpay@airtel']];

        return $this->toJsonResponse($response);
    }

    /**
     * toJsonReponse returns a json response
     *
     * @param  array $content
     * @return void
     */
    protected function toJsonResponse(array $content)
    {
        $response = new Requests_Response();

        $response->headers = ['Content-Type' => 'application/json', 'Cache-Control' => 'no-cache'];

        $response->status_code = 200;

        $response->body = json_encode($content);

        return $response;
    }
}
