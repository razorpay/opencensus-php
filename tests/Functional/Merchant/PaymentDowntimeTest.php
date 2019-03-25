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
        $this->testDataFilePath = __DIR__.'/helpers/PaymentDowntimeTestData.php';

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
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'method'      => 'upi',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => Carbon::now()->subMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();

        $response = $this->startTest();
    }

    public function testGetUpiDowntimeForIndividualGateways()
    {
        $this->createUpiAllGatewayDowntime();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetNoUpiDowntimeForSingleGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'upi_mindgate',
                'method'      => 'upi',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => Carbon::now()->subMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();

        $this->makeRequestAndGetContent($request);

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
            $request = [
                'content' => [
                    'gateway'     => $gateway,
                    'method'      => 'upi',
                    'source'      => 'dummy',
                    'reason_code' => 'OTHER',
                    'begin'       => Carbon::now()->subMinutes(60)->timestamp
                ],
                'method' => 'POST',
                'url' => '/gateway/downtimes/dummy/webhook'
            ];

            $this->ba->appAuth();

            $this->makeRequestAndGetContent($request);
        }
    }
}
