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
    }

    public function testGetCardDowntimeForRupayGateways()
    {
        $this->markTestSkipped('no code, lol');

        $this->createCardNetworkDowntime('RUPAY');

        $this->startTest();
    }

    public function testGetNoCardDowntimeForSingleRupayGateway()
    {
        $this->markTestSkipped('no code, lol');

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

        $this->createMethodDowntimes();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetUpiDowntimeForIndividualGateways()
    {
        $this->createUpiAllGatewayDowntime();

        $this->createMethodDowntimes();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetNoUpiDowntimeForSingleGateway()
    {
        $this->fixtures->create('gateway_downtime:upi', [
            'begin'     => Carbon::now()->subMinutes(30)->timestamp,
            'end'       => null,
            'gateway'   => 'upi_mindgate',
            'scheduled' => false,
        ]);

        $this->createMethodDowntimes();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetSingleUpiDowntimeForMultipleDowntimeCreations()
    {
        $this->fixtures->create('gateway_downtime:upi', [
            'begin'     => Carbon::now()->subMinutes(30)->timestamp,
            'end'       => null,
            'gateway'   => 'ALL',
            'scheduled' => false,
        ]);

        $this->createMethodDowntimes();
        $this->createMethodDowntimes();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetWalletDowntime()
    {
        $this->markTestSkipped('no code, lol');

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
        $this->markTestSkipped('no code, lol');

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

    protected function createMethodDowntimes()
    {
        $request = [
            'method'  => 'POST',
            'url'     => '/methods/downtimes/create',
            'content' => [],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);
    }
}
