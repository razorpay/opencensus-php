<?php

namespace RZP\Tests\Functional\Merchant;

use Carbon\Carbon;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use RZP\Models\Payment\Gateway;

class PaymentDowntimeTest extends TestCase
{
    use PaymentTrait;
    use WebhookTrait;

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

        $this->fixtures->merchant->addFeatures('expose_downtimes');
    }

    public function testGetUpiDowntimeForAllGateways()
    {
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'method'      => 'upi',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testGetUpiDowntimeWithEndtime()
    {
        $this->testGetUpiDowntimeForAllGateways();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        // Trigger downtime started cron
        $this->activateDowntimes('started');

        $request = [
            'content' => [
                'end' => strval(Carbon::now()->subMinutes(30)->timestamp),
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$gatewayDowntime['id']
        ];
        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $downtime = $this->getLastEntity('payment.downtime', true);
        $this->assertNotNull($downtime['end']);

        // Trigger downtime resolved cron
        $this->activateDowntimes('resolved');

        $downtime = $this->getLastEntity('payment.downtime', true);
        $this->assertEquals('resolved', $downtime['status']);
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testActivateDowntimes()
    {
        $this->testGetUpiDowntimeForAllGateways();

        $this->activateDowntimes('started');

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);
        $this->assertEquals('started', $paymentDowntime['status']);
    }

    public function testGetNetbankingDowntimeWithEndTime()
    {
        $this->testGetNetbankingDowntimeForSingleBankBilldeskGateway();

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $request = [
            'content' => [
                'end' => strval(Carbon::now()->subMinutes(30)->timestamp),
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testGatewayDowntimeIndividualBankAllGateway()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'netbanking',
                'source'      => 'BANK',
                'issuer'      => 'SBIN',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'netbanking');
        $this->assertEquals($downtime['issuer'], 'SBIN');
        $this->assertEquals($downtime['status'], 'scheduled');

        // 90 minutes elapsed
        Carbon::setTestNow(Carbon::now()->addMinutes(90));

        // Create new downtime
        $addDowntimeRequest['content']['issuer'] = 'ALLA';
        $addDowntimeRequest['content']['begin']  = Carbon::now()->timestamp;
        $addDowntimeRequest['content']['end']    = Carbon::now()->addMinutes(60)->timestamp;

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtimes = $this->fetchOngoingDowntime();

        // Previous downtime should get resolved
        $this->assertCount(1, $downtimes['items']);

        $this->assertEquals($downtimes['items'][0]['method'], 'netbanking');
        $this->assertEquals($downtimes['items'][0]['instrument']['bank'], 'ALLA');
        $this->assertEquals($downtimes['items'][0]['status'], 'scheduled');
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
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
                'end' => strval(Carbon::now()->subMinutes(30)->timestamp),
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

    public function testGatewayDowntimeCardNetworkAllGateway()
    {
        $this->ba->adminAuth();

        $begin = Carbon::now()->subMinutes(60)->timestamp;
        $end   = Carbon::now()->addMinutes(60)->timestamp;

        $addDowntimeRequest = [
            'content' => [
                'begin'       => $begin,
                'end'         => $end,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'card',
                'source'      => 'BANK',
                'acquirer'    => 'axis',
                'network'     => 'MC',
                'card_type'   => 'ALL',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method' => 'GET',
            'url' => '/payments/downtimes'
        ];

        $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'card');
        $this->assertEquals($downtime['status'], 'scheduled');
    }

    public function testGatewayDowntimeIndividualCardAllGateway()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'network'     => 'MC',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'card',
                'source'      => 'BANK'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'card');
        $this->assertEquals($downtime['network'], 'MC');
        $this->assertEquals($downtime['status'], 'scheduled');

        // 90 minutes elapsed
        Carbon::setTestNow(Carbon::now()->addMinutes(90));

        // Create new downtime
        $addDowntimeRequest['content']['network'] = 'VISA';
        $addDowntimeRequest['content']['begin']  = Carbon::now()->timestamp;
        $addDowntimeRequest['content']['end']    = Carbon::now()->addMinutes(60)->timestamp;

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtimes = $this->fetchOngoingDowntime();

        // Previous downtime should get resolved
        $this->assertCount(1, $downtimes['items']);

        $this->assertEquals($downtimes['items'][0]['method'], 'card');
        $this->assertEquals($downtimes['items'][0]['instrument']['network'], 'VISA');
        $this->assertEquals($downtimes['items'][0]['status'], 'scheduled');
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGetWalletDowntimeForSingleGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'wallet_olamoney',
                'method'      => 'wallet',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();

        $this->startTest();
    }

    public function testGatewayDowntimeIndividualWalletAllGateway()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp,
                'gateway'     => 'wallet_olamoney',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'wallet',
                'source'      => 'BANK'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'wallet');
        $this->assertEquals($downtime['issuer'], 'olamoney');
        $this->assertEquals($downtime['status'], 'scheduled');

        // 90 minutes elapsed
        Carbon::setTestNow(Carbon::now()->addMinutes(90));

        // Create new downtime
        $addDowntimeRequest['content']['gateway'] = 'wallet_payumoney';
        $addDowntimeRequest['content']['begin']  = Carbon::now()->timestamp;
        $addDowntimeRequest['content']['end']    = Carbon::now()->addMinutes(60)->timestamp;

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtimes = $this->fetchOngoingDowntime();

        // Previous downtime should get resolved
        $this->assertCount(1, $downtimes['items']);

        $this->assertEquals($downtimes['items'][0]['method'], 'wallet');
        $this->assertEquals($downtimes['items'][0]['instrument']['wallet'], 'payumoney');
        $this->assertEquals($downtimes['items'][0]['status'], 'scheduled');
    }

    public function testGetWalletDowntimeWithEndTime()
    {
        $this->testGetWalletDowntimeForSingleGateway();

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

    public function testWebhookForPaymentDowntimeStartedEvent()
    {
        Carbon::setTestNow(Carbon::create(2019, 14, 01));

        $this->createWebhook(['events' => ['payment.downtime.started' => '1',
                                           'payment.downtime.resolved' => '1']]);

        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'netbanking',
                'source'      => 'BANK',
                'issuer'      => 'SBIN',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'netbanking');
        $this->assertEquals($downtime['issuer'], 'SBIN');
        $this->assertEquals($downtime['status'], 'scheduled');

        $this->setInfernoExpectations(['testPaymentDowntimeStartedWebhook']);

        $this->activateDowntimes('started');
    }

    public function testWebhookForPaymentDowntimeResolvedEvent()
    {
        Carbon::setTestNow(Carbon::create(2019, 14, 01));

        $this->createWebhook(['events' => ['payment.downtime.started' => '1',
                                           'payment.downtime.resolved' => '1']]);

        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'netbanking',
                'source'      => 'BANK',
                'issuer'      => 'SBIN',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'netbanking');
        $this->assertEquals($downtime['issuer'], 'SBIN');
        $this->assertEquals($downtime['status'], 'scheduled');

        $this->activateDowntimes('started');

        $this->setInfernoExpectations(['testPaymentDowntimeResolvedWebhook']);

        // 90 minutes elapsed
        Carbon::setTestNow(Carbon::now()->addMinutes(90));

        $this->activateDowntimes('resolved');
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
                    'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
                ],
                'method' => 'POST',
                'url' => '/gateway/downtimes/dummy/webhook'
            ];

            $this->ba->appAuth();
            $this->updateSignature($request);
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
                    'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
                ],
                'method' => 'POST',
                'url' => '/gateway/downtimes/dummy/webhook'
            ];

            $this->ba->appAuth();
            $this->updateSignature($request);
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
                    'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
                ],
                'method' => 'POST',
                'url' => '/gateway/downtimes/dummy/webhook'
            ];

            $this->ba->appAuth();
            $this->updateSignature($request);
            $this->makeRequestAndGetContent($request);
        }
    }

    protected function activateDowntimes(string $status)
    {
        $this->ba->appAuth();

        $this->makeRequestAndGetContent([
            'url'     => '/payments/downtimes/trigger/' . $status,
            'method'  => 'POST',
            'content' => [],
        ]);
    }

    protected function updateSignature(array & $request)
    {
        unset($request['content']['signature']);

        $secret = \Config::get('applications.dashboard.secret');

        $signature = hash_hmac('sha256', json_encode($request['content']), $secret);

        $request['content']['signature'] = $signature;
    }

    protected function fetchOngoingDowntime()
    {
        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method' => 'GET',
            'url' => '/payments/downtimes'
        ];

        return $this->makeRequestAndGetContent($fetchDowntimeRequest);
    }
}
