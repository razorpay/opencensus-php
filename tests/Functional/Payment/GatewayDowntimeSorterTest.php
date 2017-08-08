<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;
use RZP\Constants\Timezone;

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

        $this->createCardTerminals();
    }

    protected function createCardTerminals()
    {
        $this->fixtures->create('terminal:all_shared_terminals');
        $this->fixtures->create('terminal:disable_default_hdfc_terminal');
    }

    protected function makePayment(string $cardNumber = null)
    {
        $payment = $this->getDefaultPaymentArray();

        if ($cardNumber !== null)
        {
            $payment['card']['number'] = $cardNumber;
        }

        $this->doAuthAndCapturePayment($payment);

        return $payment;
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
        // without downtime
        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $axisMigsAllNetworkDowntimeData = $this->testData['axisMigsAllNetworkDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $axisMigsAllNetworkDowntimeData);

        $payment = $this->makePayment('555555555555558');

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
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcVisaDowntimeData = $this->testData['hdfcVisaDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcVisaDowntimeData);

        $payment = $this->makePayment();

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
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $migsAllIssuerDowntimeData = $this->testData['migsAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $migsAllIssuerDowntimeData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * downtime for hdfc for all issuer, all network
     * payment via hdfc visa card
     *
     * payment goes through axis_migs
     * hdfc is down, & axis_migs support visa payments
     */
    public function testDowntimeSortingCardHdfcAllNetworkAllIssuerDowntimeData()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcAllNetworkAllIssuerDowntimeData = $this->testData['hdfcAllNetworkAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcAllNetworkAllIssuerDowntimeData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for cybersource for all networks
     * payment via MC
     *
     * this payment should go through via hdfc
     * because cybersource is down for MC
     */
    public function testDowntimeSortingCardCybersourcePaymentMastercard()
    {
        // without downtime
        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $cybersourceDowntimeData = $this->testData['cybersourceDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $cybersourceDowntimeData);

        $payment = $this->makePayment('555555555555558');

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
        // without downtime
        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcAllNetworkAllIssuerDowntimeData = $this->testData['hdfcAllNetworkAllIssuerDowntimeData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcAllNetworkAllIssuerDowntimeData);

        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for MC networks,
     * payment via MC
     *
     * this payment should go through via axis_migs
     * because hdfc is down for MC
     */
    public function testDowntimeSortingCardHdfcMastercard()
    {
        // without downtime
        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcMastercardNetworkData = $this->testData['hdfcMastercardNetworkData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcMastercardNetworkData);

        $payment = $this->makePayment('555555555555558');

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for issued and network unknown,
     *
     * this payment should go through via axis_migs
     * because hdfc is down
     */
    public function testDowntimeSortingCardHdfcUnknownIssuerNetwork()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcUnkownIssuerNetworkData = $this->testData['hdfcUnkownIssuerNetworkData'];
        $this->fixtures->create('gateway_downtime:card', $hdfcUnkownIssuerNetworkData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc for issued and network unknown,
     * but it starts 24 hours later from now
     *
     * this payment should go through via hdfc
     */
    public function testDowntimeSortingCardHdfcDowntimeBeginLater()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcUnkownIssuerNetworkData = $this->testData['hdfcUnkownIssuerNetworkData'];
        $hdfcUnkownIssuerNetworkData['begin'] = Carbon::now(Timezone::IST)->addHours(24)->timestamp;

        $this->fixtures->create('gateway_downtime:card', $hdfcUnkownIssuerNetworkData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for all gateways for network VISA and issuer HDFC,
     *
     * this payment should go through via hdfc
     * the order remains the same because
     * all gateways are down for given network & Issuer
     */
    public function testDowntimeSortingCardAllGatewayIssuerHdfcNetworkVisa()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $allGatewayIssuerHdfcNetworkVisaData = $this->testData['allGatewayIssuerHdfcNetworkVisaData'];
        $this->fixtures->create('gateway_downtime:card', $allGatewayIssuerHdfcNetworkVisaData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for all gateways for network all and issuer HDFC,
     *
     * this payment should go through via hdfc
     * the order remains the same because
     * all gateways are down for all network & Issuer HDFC
     */
    public function testDowntimeSortingCardAllGatewayIssuerNetworkHdfc()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $allGatewayAllIssuerNetworkHdfcData = $this->testData['allGatewayAllIssuerNetworkHdfcData'];
        $this->fixtures->create('gateway_downtime:card', $allGatewayAllIssuerNetworkHdfcData);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);
    }

    /**
     * Card/EMI Downtime
     *
     * card downtime for hdfc gateways for network all and issuer HDFC,
     *
     * this payment should go through via axis_migs
     * hdfc gateways are down for all network & Issuer HDFC
     */
    public function testDowntimeSortingCardHdfcNetworkAllIssuerHdfc()
    {
        // without downtime
        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('hdfc', $payment['gateway']);

        // with downtime
        $hdfcNetworkAllIssuerHdfc = $this->testData['hdfcNetworkAllIssuerHdfc'];
        $this->fixtures->create('gateway_downtime:card', $hdfcNetworkAllIssuerHdfc);

        $payment = $this->makePayment();

        $payment = $this->getLastEntity('payment', true);
        $this->assertEquals('axis_migs', $payment['gateway']);
    }
}
