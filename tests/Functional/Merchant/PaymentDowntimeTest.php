<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Models\Payment\Gateway;

class PaymentDowntimeTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantTestData.php';

        parent::setUp();

        $this->ba->privateAuth();

        $this->markTestSkipped('no code, lol');
    }

    public function testGetCardDowntimeForRupayGateways()
    {
        $this->createCardNetworkDowntime('RUPAY');

        $this->startTest();
    }

    public function testGetNoCardDowntimeForSingleRupayGateway()
    {
        // Only one gateway down, network should still be up
        $this->fixtures->create('gateway_downtime:card', [
            'begin'     => Carbon::now()->subMinutes(30)->timestamp,
            'end'       => null,
            'gateway'   => 'hdfc',
            'network'   => 'RUPAY',
            'method'    => 'card',
            'card_type' => 'ALL',
        ]);

        $this->startTest();
    }

    public function testGetUpiDowntimeForAllGateways()
    {
        $this->fixtures->create('gateway_downtime:upi', [
            'begin'     => Carbon::now()->subMinutes(30)->timestamp,
            'end'       => null,
            'gateway'   => 'ALL',
            'scheduled' => false,
        ]);

        $this->startTest();
    }

    public function testGetUpiDowntimeForIndividualGateways()
    {
        $this->createUpiAllGatewayDowntime();

        $this->startTest();
    }

    public function testGetWalletDowntime()
    {
        $this->fixtures->create('gateway_downtime:wallet', [
            'begin'     => Carbon::now()->subMinutes(30)->timestamp,
            'end'       => null,
            'gateway'   => 'wallet_airtelmoney',
            'scheduled' => false,
        ]);

        $this->startTest();
    }

    protected function createCardNetworkDowntime(string $network)
    {
        foreach (Gateway::$cardNetworkMap as $gateway => $networks)
        {
            if (in_array($network, $networks, true) === true)
            {
                $this->fixtures->create('gateway_downtime:card', [
                    'begin'     => Carbon::now()->subMinutes(30)->timestamp,
                    'end'       => null,
                    'gateway'   => $gateway,
                    'network'   => $network,
                    'method'    => 'card',
                    'card_type' => 'ALL',
                ]);
            }
        }
    }

    protected function createUpiAllGatewayDowntime()
    {
        foreach (Gateway::$methodMap['upi'] as $gateway)
        {
            $this->fixtures->create('gateway_downtime:upi', [
                'begin'     => Carbon::now()->subMinutes(30)->timestamp,
                'end'       => null,
                'gateway'   => $gateway,
                'scheduled' => false,
            ]);
        }
    }
}
