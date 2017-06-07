<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Exception\RuntimeException;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class GatewayDowntimeSorterTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayDowntimeSorterTestData.php';

        parent::setUp();
    }

    protected function createCardTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for axis_migs for all networks
     *
     * this payment should go through via hdfc
     * because axis_migs is down
     */
    public function testDowntimeSortingCardAxisMigs()
    {
        $this->createCardTerminals();

        $axisMigsAllNetworkDowntimeData = $this->testData['axisMigsAllNetworkDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $axisMigsAllNetworkDowntimeData);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for visa networks
     *
     * this payment should go through via axis_migs
     * because hdfc visa network is down
     */
    public function testDowntimeSortingCardHdfcVisa()
    {
        $this->createCardTerminals();

        $hdfcVisaDowntimeData = $this->testData['hdfcVisaDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcVisaDowntimeData);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '4012001036275556';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }
}
