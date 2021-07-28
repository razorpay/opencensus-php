<?php

namespace RZP\Services\UpiPayment\Mock;

use Psr\Http\Message\RequestInterface;
use RZP\Services\UpiPayment\Service as UpiPaymentService;

/**
 * Service implements all the UPS actions
 */
class Service extends UpiPaymentService
{
    /**
     * Mocks the request to the UPS server
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function sendRequest(RequestInterface $request): array
    {
        $content = json_decode($request->getBody()->getContents(),true);

        $action = $this->action;

        list($response, $code) = $this->$action($content);

        return [$response, $code];
    }

    /**
     * Authorize returns the authorize response
     *
     * @param array $content
     * @return array
     */
    protected function authorize(array $content): array
    {
        $description = $content['payment']['description'];

        $response = [];

        $code = 500;

        switch ($description)
        {
            case 'create_collect_success':
                $response = ['data' => ['vpa' => 'razorpay@airtel']];
                $code = 200;

                break;

            case 'validation_failure_collect_vpa':
                $response = [
                    'details' => [[
                        'internal' => [
                            'code'          => 'BAD_REQUEST_INPUT_VALIDATION_FAILURE',
                            'description'   => 'Vpa is required for UPI collect request'
                        ]
                    ]]
                ];
                $code = 400;

                break;

            case 'service_failure':
                $response = [
                    'error' => 'internal server error',
                ];
                $code = 500;

                break;
        }

        return [$response, $code];
    }
}
