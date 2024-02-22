<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

class UpiRzpapbPaymentsServiceTest extends UpiPaymentServiceBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_rzpapb';

        $this->terminal = $this->fixtures->create('terminal:upi_rzpapb_terminal');
    }
}
