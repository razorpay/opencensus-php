<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\MocksDnsTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Downtime\DowntimeNotification as DowntimeNotification;

/**
 * @group dns-sensitive
 */
class PaymentDowntimeTest extends TestCase
{
    use PaymentTrait;
    use MocksDnsTrait;
    use TestsWebhookEvents;

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

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_netbanking' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_upi' => '1',
            ],
        ]);

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_wallet' => '1',
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
                'issuer'      => 'ABPB',
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

    public function testPaymentDowntimeForAllGatewayAndSingleGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'issuer'      => 'SVCB',
                'method'      => 'netbanking',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'billdesk';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testPaymentDowntimeForSingleGatewayAndAllGateway()
    {
        $request = [
            'content' => [
                'gateway'     => 'billdesk',
                'issuer'      => 'SVCB',
                'method'      => 'netbanking',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'ALL';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testPaymentDowntimeForFewSupportingGateways()
    {
        $request = [
            'content' => [
                'gateway'     => 'billdesk',
                'issuer'      => 'SBIN',
                'method'      => 'netbanking',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->timestamp),
                'end'       => strval(Carbon::now()->addMinutes(60)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'atom';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $this->ba->privateAuth();
        $this->startTest();
    }

    public function testPaymentDowntimeForAllSupportingGateways()
    {
        $request = [
            'content' => [
                'gateway'     => 'billdesk',
                'issuer'      => 'SBIN',
                'method'      => 'netbanking',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->timestamp),
                'end'       => strval(Carbon::now()->addMinutes(60)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'atom';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'netbanking_sbi';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(40)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        Carbon::setTestNow(Carbon::now()->addMinutes(45));

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
        $this->assertEquals($downtime['status'], 'started');

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
        $this->assertEquals($downtimes['items'][0]['status'], 'started');
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
        $this->assertEquals($downtime['status'], 'started');
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
        $this->assertEquals($downtime['status'], 'started');

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
        $this->assertEquals($downtimes['items'][0]['status'], 'started');
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
        $this->assertEquals($downtime['status'], 'started');

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
        $this->assertEquals($downtimes['items'][0]['status'], 'started');
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

        $this->expectWebhookEventWithContents('payment.downtime.started', 'testPaymentDowntimeStartedWebhook');

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'netbanking');
        $this->assertEquals($downtime['issuer'], 'SBIN');
        $this->assertEquals($downtime['status'], 'started');

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
        $this->assertEquals($downtime['status'], 'started');

        $this->activateDowntimes('started');

        $this->expectWebhookEventWithContents('payment.downtime.resolved', 'testPaymentDowntimeResolvedWebhook');

        // 90 minutes elapsed
        Carbon::setTestNow(Carbon::now()->addMinutes(90));

        $this->activateDowntimes('resolved');
    }

    public function testCreatePaymentDowntimeForVPAHandle()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'oksbi',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $id = $downtime1['id'];

        $this->assertEquals($downtime1['vpa_handle'], 'oksbi');
        $this->assertNull($downtime1['end']);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['vpa_handle'], 'oksbi');
        $this->assertNull($downtime['end']);

        Carbon::setTestNow(Carbon::now()->addMinute(10));

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $id
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $this->assertNotNull($downtime1['end']);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertNotNull($downtime['end']);
    }

    public function testCreatePaymentDowntimeWithoutVPAHandle()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $id = $downtime1['id'];

        $this->assertEquals($downtime1['method'], 'upi');

        $this->assertNull($downtime1['vpa_handle']);

        $this->assertNull($downtime1['end']);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['method'], 'upi');

        $this->assertNull($downtime['vpa_handle']);

        $this->assertNull($downtime['end']);

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $id
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $this->assertNotNull($downtime1['end']);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertNotNull($downtime['end']);
    }

    public function testCreateMultiplePaymentDowntimeWithVPAHandle(){
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'oksbi',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $addDowntimeRequest2 = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(10)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'ybl',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $gId1 = $gatewayDowntime['id'];

        $this->assertEquals($gatewayDowntime['vpa_handle'], 'oksbi');

        $downtime = $this->getLastEntity('payment.downtime', true);

        $pId1 = $downtime['id'];

        $this->makeRequestAndGetContent($addDowntimeRequest2);

        $gatewayDowntime2 = $this->getLastEntity('gateway_downtime', true);

        $gId2 = $gatewayDowntime2['id'];

        $downtime2 = $this->getLastEntity('payment.downtime', true);

        $pId2 = $downtime2['id'];

        $this->assertEquals($downtime['vpa_handle'], 'oksbi');

        $this->assertEquals($downtime2['vpa_handle'], 'ybl');

        Carbon::setTestNow(Carbon::now()->addMinute(10));

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $gId1
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $gatewayDowntime1 = $this->getEntityById('gateway_downtime', $gId1, true);

        $this->assertNotNull($gatewayDowntime1['end']);

        $paymentDowntime1 = $this->getEntityById('payment.downtime', $pId1, true);

        $this->assertNotNull($paymentDowntime1['end']);

        Carbon::setTestNow(Carbon::now()->addMinute(10));

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $gId2
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $gatewayDowntime2 = $this->getEntityById('gateway_downtime', $gId2, true);

        $this->assertNotNull($gatewayDowntime2['end']);

        $paymentDowntime2 = $this->getEntityById('payment.downtime', $pId2, true);

        $this->assertNotNull($paymentDowntime2['end']);
    }

    public function testCreateMultiplePaymentDowntimeWithAndWithoutVPAHandle(){

        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'oksbi',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $addDowntimeRequest2 = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(10)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'OTHER',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $gId1 = $gatewayDowntime['id'];

        $this->assertEquals($gatewayDowntime['vpa_handle'], 'oksbi');

        $downtime = $this->getLastEntity('payment.downtime', true);

        $pId1 = $downtime['id'];

        $this->makeRequestAndGetContent($addDowntimeRequest2);

        $gatewayDowntime2 = $this->getLastEntity('gateway_downtime', true);

        $gId2 = $gatewayDowntime2['id'];

        $downtime2 = $this->getLastEntity('payment.downtime', true);

        $pId2 = $downtime2['id'];

        $this->assertEquals($downtime['vpa_handle'], 'oksbi');

        $this->assertNull($downtime2['vpa_handle']);

        Carbon::setTestNow(Carbon::now()->addMinute(10));

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $gId2
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $gatewayDowntime2 = $this->getEntityById('gateway_downtime', $gId2, true);

        $this->assertNotNull($gatewayDowntime2['end']);

        $paymentDowntime2 = $this->getEntityById('payment.downtime', $pId2, true);

        $this->assertNotNull($paymentDowntime2['end']);

        Carbon::setTestNow(Carbon::now()->addMinute(10));

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'. $gId1
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $gatewayDowntime1 = $this->getEntityById('gateway_downtime', $gId1, true);

        $this->assertNotNull($gatewayDowntime1['end']);

        $paymentDowntime2 = $this->getEntityById('payment.downtime', $pId1, true);

        $this->assertNotNull($paymentDowntime2['end']);
    }

    public function testPaymentDowtimeEmailNotification()
    {
        Mail::fake();

        Mail::setFakeConfig();

        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'oksbi',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        Mail::assertSent(DowntimeNotification::class);

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->subMinutes(10)->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$gatewayDowntime['id'],
        ];

        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        Mail::assertSent(DowntimeNotification::class);
    }

    public function testGooglePayPspDowntime()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'upi',
                'source'      => 'BANK',
                'vpa_handle'  => 'oksbi',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $addDowntimeRequest['content']['begin'] = Carbon::now()->subMinutes(50)->timestamp;
        $addDowntimeRequest['content']['vpa_handle'] = 'okhdfcbank';
        $this->makeRequestAndGetContent($addDowntimeRequest);

        $addDowntimeRequest['content']['begin'] = Carbon::now()->subMinutes(40)->timestamp;
        $addDowntimeRequest['content']['vpa_handle'] = 'okaxis';
        $this->makeRequestAndGetContent($addDowntimeRequest);

        $addDowntimeRequest['content']['begin'] = Carbon::now()->subMinutes(30)->timestamp;
        $addDowntimeRequest['content']['vpa_handle'] = 'okicici';
        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($downtime['psp'], 'google_pay');

    }

    public function testGetCheckoutPreferencesWithPaymentDowntime()
    {
        $this->createNetbankingAllGatewayDowntime();

        $this->ba->publicAuth();

        $this->startTest();
    }

    public function testIssuerAndNetworkCardDowntimeSimultaneously()
    {
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'issuer'      => 'HDFC',
                'method'      => 'card',
                'source'      => 'VAJRA',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $issuerDowntimeId = $paymentDowntime['id'];

        $this->assertEquals($paymentDowntime['issuer'], 'HDFC');
        $this->assertNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(15));

        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'     => 'VISA',
                'method'      => 'card',
                'source'      => 'VAJRA',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime['network'], 'VISA');
        $this->assertNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(15));

        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'     => 'VISA',
                'method'      => 'card',
                'source'      => 'VAJRA',
                'reason_code' => 'HIGHER_ERRORS',
                'end'         => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime['network'], 'VISA');
        $this->assertNotNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(15));

        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'issuer'      => 'HDFC',
                'method'      => 'card',
                'source'      => 'VAJRA',
                'reason_code' => 'HIGHER_ERRORS',
                'end'         => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->appAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getEntityById('payment.downtime', $issuerDowntimeId, true);

        $this->assertEquals($paymentDowntime['issuer'], 'HDFC');
        $this->assertNotNull($paymentDowntime['end']);
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
        foreach (['hdfc', 'first_data', 'card_fss', 'paysecure', 'hitachi'] as $gateway)
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
