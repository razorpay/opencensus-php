<?php

namespace RZP\Tests\Functional\PaymentsUpi\Service;

class UpiRzprblPaymentServiceTest extends UpiPaymentServiceBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_rzprbl';

        $this->terminal = $this->fixtures->create('terminal:upi_rzprbl_terminal');
    }
}
