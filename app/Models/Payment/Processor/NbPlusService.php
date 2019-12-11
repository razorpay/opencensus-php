<?php

namespace RZP\Models\Payment\Processor;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;

trait NbPlusService
{
    public function callNbPlusServiceAction($payment, $gateway, $action, $gatewayData)
    {
        $response = $this->app['nbplus.payments']->action($gateway, $action, $gatewayData);

        $this->handleNbPlusResponse($payment, $response);

        // If action is verify we get verify trace data
        if ($action === Action::VERIFY)
        {
            return $response;
        }

        return $response['data'];
    }

    protected function handleNbPlusResponse($payment, $response)
    {
        if (empty($response) === true)
        {
            throw new Exception\GatewayErrorException(
                ErrorCode::BAD_REQUEST_PAYMENT_FAILED
            );
        }

        $this->app['nbplus.payments']->checkForErrors($response);
    }
}
