<?php

namespace RZP\Services\UpiPayment\Mock;

use RZP\Exception;
use RZP\Services\UpiPayment\Action;
use RZP\Services\UpiPayment\Request;
use RZP\Services\UpiPayment\Service as UpiPaymentService;

/**
 * Service implements all the UPS actions
 */
class Service extends UpiPaymentService
{
    /**
     * Action handles mocks all the action based payment requests
     *
     * @param array $request
     */
    public function action(string $gateway, string $action, array $input) : array
    {
        return ['data' => ['vpa' => 'razorpay@airtel']];
    }
}
