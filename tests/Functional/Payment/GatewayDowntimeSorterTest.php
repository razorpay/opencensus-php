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

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * downtime for axis migs for all issuer
     * payment via hdfc visa card
     *
     * payment goes through hdfc,
     * axis migs is down
     */
    public function testDowntimeSortingCardGatewayMigsIssuerAll()
    {
        $this->createCardTerminals();

        $migsAllIssuerDowntimeData = $this->testData['migsAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $migsAllIssuerDowntimeData);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * downtime for cybersource for all issuer, all network
     * payment via hdfc visa card
     *
     * payment goes through hdfc
     * cybersource is down.
     */
    public function testDowntimeSortingCardCybersourceAllNetworkAllIssuer()
    {
        $this->createCardTerminals();

        $cybersourceDowntimeData = $this->testData['cybersourceDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $cybersourceDowntimeData);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * downtime for cybersource for all issuer, all network
     * payment via hdfc visa card
     *
     * payment goes through axis_migs
     * hdfc is down, & axis_migs support visa payments
     */
    public function testDowntimeSortingCardHdfcAllNetworkAllIssuerDowntimeData()
    {
        $this->createCardTerminals();

        $hdfcAllNetworkAllIssuerDowntimeData = $this->testData['hdfcAllNetworkAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcAllNetworkAllIssuerDowntimeData);

        $payment = $this->getDefaultPaymentArray();

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for axis_migs for all networks
     * payment via MC
     *
     * this payment should go through via hdfc
     * because cybersource is down for MC
     */
    public function testDowntimeSortingCardCybersourcePaymentMastercard()
    {
        $this->createCardTerminals();

        $cybersourceDowntimeData = $this->testData['cybersourceDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $cybersourceDowntimeData);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for all networks, all cards
     * payment via MC
     *
     * this payment should go through via axis_migs
     * because hdfc is down for all network & issuer
     */
    public function testDowntimeSortingCardHdfcPaymentMastercard()
    {
        $this->createCardTerminals();

        $hdfcAllNetworkAllIssuerDowntimeData = $this->testData['hdfcAllNetworkAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcAllNetworkAllIssuerDowntimeData);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for all networks, all cards
     * payment via MC
     *
     * this payment should go through via axis_migs
     * because hdfc is down for MC
     */
    public function testDowntimeSortingCardHdfcMastercard()
    {
        $this->createCardTerminals();

        $hdfcMastercardNetworkData = $this->testData['hdfcMastercardNetworkData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcMastercardNetworkData);

        $payment = $this->getDefaultPaymentArray();
        $payment['card']['number'] = '555555555555558';

        $this->doAuthAndCapturePayment($payment);

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }
}
