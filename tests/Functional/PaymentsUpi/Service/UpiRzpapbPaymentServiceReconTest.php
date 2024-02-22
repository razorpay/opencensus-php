<?php


namespace Functional\PaymentsUpi\Service;


use RZP\Tests\Functional\PaymentsUpi\Service\UpiPaymentServiceReconBase;

class UpiRzpapbPaymentServiceReconTest extends UpiPaymentServiceReconBase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = 'upi_rzpapb';

        $this->terminal = $this->fixtures->create('terminal:upi_rzpapb_terminal');
    }
}
