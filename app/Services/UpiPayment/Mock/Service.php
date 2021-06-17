<?php

namespace RZP\Services\UpiPayment\Mock;

use RZP\Services\UpiPayment\Service as UpiPaymentService;

/**
 * Service implements all the UPS actions
 */
class Service extends UpiPaymentService
{

    /**
     * Action returns the mock response to a payment request.
     *
     * @param string $gateway
     * @param string $action
     * @param array $input
     * @return array
     */
    public function action(string $gateway, string $action, array $input): array
    {
        $data = ['vpa' => 'razorpay@airtel'];

        return ['data' => $data];
    }
}
