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
        }

        return [$response, $code];
    }
}
