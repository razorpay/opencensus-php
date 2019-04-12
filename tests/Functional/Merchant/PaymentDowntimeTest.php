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

        $this->enablePaymentDowntimes();

        $this->ba->privateAuth();
    }

    protected function enablePaymentDowntimes()
    {
        $this->ba->adminAuth();

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes' => '1',
            ],
        ]);
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

        $this->startTest();
    }

    public function testGetUpiDowntimeWithEndtime()
    {
        $this->testGetUpiDowntimeForAllGateways();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $request = [
            'content' => [
                'end' => Carbon::now()->subMinutes(30)->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$gatewayDowntime['id']
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $downtime = $this->getLastEntity('payment.downtime', true);
        $this->assertNotNull($downtime['end']);
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

    public function testGetNetbankingDowntimeForSingleBankBilldeskGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'billdesk',
                'issuer'      => 'SVCB',
                'method'      => 'netbanking',
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

    public function testGetNetbankingDowntimeWithEndTime()
    {
        $this->testGetNetbankingDowntimeForSingleBankBilldeskGateway();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $request = [
            'content' => [
                'end' => Carbon::now()->subMinutes(30)->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$gatewayDowntime['id']
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $downtime = $this->getLastEntity('payment.downtime', true);
        $this->assertNotNull($downtime['end']);
    }

    public function testGetNetbankingDowntimeForIndividualGateways()
    {
        $this->createNetbankingAllGatewayDowntime();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetNoNetbankingDowntimeForSingleGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'billdesk',
                'method'      => 'netbanking',
                'issuer'      => 'ANDB',
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

    public function testGetCardDowntimeForSingleNetworkHdfcGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'hdfc',
                'network'     => 'DICL',
                'method'      => 'card',
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

    public function testGetCardDowntimeWithEndTime()
    {
        $this->testGetCardDowntimeForSingleNetworkHdfcGateway();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $request = [
            'content' => [
                'end' => Carbon::now()->subMinutes(30)->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$gatewayDowntime['id']
        ];

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent($request);

        $downtime = $this->getLastEntity('payment.downtime', true);
        $this->assertNotNull($downtime['end']);
    }

    public function testGetCardDowntimeForIndividualGateways()
    {
        $this->createCardAllGatewayDowntime();

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetNoCardDowntimeForSingleGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'card_fss',
                'method'      => 'card',
                'network'     => 'RUPAY',
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

    protected function createNetbankingAllGatewayDowntime()
    {
        foreach (['billdesk', 'atom', 'ebs'] as $gateway)
        {
            $request = [
                'content' => [
                    'gateway'     => $gateway,
                    'method'      => 'netbanking',
                    'issuer'      => 'ANDB',
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

    protected function createCardAllGatewayDowntime()
    {
        foreach (['hdfc', 'first_data', 'card_fss', 'hitachi'] as $gateway)
        {
            $request = [
                'content' => [
                    'gateway'     => $gateway,
                    'method'      => 'card',
                    'network'     => 'RUPAY',
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
