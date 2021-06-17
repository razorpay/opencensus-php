<?php

namespace RZP\Services\UpiPayment;

use RZP\Exception;

/**
 * Service implements the UPI Payments service client
 */
class Service
{
    /**
     * Action handles all the action based payment requests
     *
     * @param string $gateway
     * @param string $action
     * @param array $input
     * @return array
     */   
    public function action(string $gateway, string $action, array $input) : array
    {
        throw new Exception\LogicException(
            'Action is not implemented for UPI payment service');

        return $input;
    }
}
