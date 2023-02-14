<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

class UpiKotakPaymentServiceTest extends UpiPaymentServiceBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_kotak';

        $this->terminal = $this->fixtures->create('terminal:shared_upi_kotak_terminal');
    }
}
