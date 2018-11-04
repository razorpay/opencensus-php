<?php

namespace RZP\Tests\P2p\Service\Customer;

use RZP\Tests\P2p\Service\TestCase;

class CustomerTest extends TestCase
{
    protected $entity = 'customer';

    public function testCustomerVerificationStart()
    {
        $helper = $this->getCustomerHelper();

        $helper->withSchemaValidated();

        $helper->sendVerificationStart();
    }

    public function testCustomerVerificationStatus()
    {
        $token = str_random(16);

        $helper = $this->getCustomerHelper();

        $helper->withSchemaValidated();

        $helper->fetchVerificationStatus($token);
    }

    public function testCustomerCreation()
    {
        $token = str_random(16);

        $helper = $this->getCustomerHelper();

        $helper->withSchemaValidated();

        $helper->postCreateCustomer(['token' => $token]);
    }
}
