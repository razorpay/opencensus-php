<?php

namespace RZP\Tests\Functional\Merchant;

use Mail;
use Mockery;
use Carbon\Carbon;
use RZP\Tests\Functional\Helpers\WebhookTrait;
use \WpOrg\Requests\Response;

use RZP\Constants\Timezone;
use RZP\Models\Payment\Gateway;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Tests\Traits\TestsWebhookEvents;
use RZP\Tests\Functional\Helpers\DowntimeTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Mail\Downtime\DowntimeNotification as DowntimeNotification;
use Razorpay\IFSC\Bank;

class PaymentDowntimeTest extends TestCase
{
    use PaymentTrait;
    use DowntimeTrait;
    use TestsWebhookEvents;
    use WebhookTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/PaymentDowntimeTestData.php';

        parent::setUp();

        $this->enablePaymentDowntimes();

        $this->ba->privateAuth();
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

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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
                'issuer'      => 'BACB',
                'method'      => 'netbanking',
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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

        $this->ba->directAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'atom';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'payu';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'cashfree';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'ccavenue';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(30)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'netbanking_sbi';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(40)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'paytm';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(45)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'zaakpay';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(50)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'billdesk_optimizer';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(50)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $request['content']['gateway'] = 'ingenico';
        $request['content']['begin'] = strval(Carbon::now()->addMinutes(50)->timestamp);
        $request['content']['end'] = strval(Carbon::now()->addMinutes(90)->timestamp);

        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        Carbon::setTestNow(Carbon::now()->addMinutes(55));

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
                'issuer'      => Bank::ALLA,
                'source'      => 'dummy',
                'reason_code' => 'OTHER',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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

    public function testPaymentDowntimeGetByID()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(10)->timestamp,
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

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method' => 'GET',
            'url' => '/payments/downtimes/' . $downtime['id'],
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals($paymentDowntime['method'], 'upi');
        $this->assertEquals($paymentDowntime['status'], 'started');
    }


    public function testFetchOngoingPayments()
    {
        $this->markTestSkipped("Skipping for now");

        $this->ba->adminAuth();

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'oksbi');
        $paymentDowntime = $this->fetchOngoingDowntimes();

        $this->assertEquals(1, sizeof($paymentDowntime));

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okhdfc');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(2, sizeof($paymentDowntime));

        $this->createDowntime('card', 'BANK', 'issuer', 'HDFC');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(3, sizeof($paymentDowntime));

        $this->createDowntime('netbanking', 'BANK', 'issuer', 'HDFC');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(4, sizeof($paymentDowntime));

    }

    public function testFetchOngoingPayments_ResolveAndCheckCount()
    {
        $this->ba->adminAuth();

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'oksbi');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(1, sizeof($paymentDowntime));

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okhdfc');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(2, sizeof($paymentDowntime));

        $this->createDowntime('card', 'BANK', 'issuer', 'HDFC');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(3, sizeof($paymentDowntime));

        $this->createDowntime('netbanking', 'BANK', 'issuer', 'HDFC');
        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(4, sizeof($paymentDowntime));


        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$downtime1['id']
        ];
        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $paymentDowntime = $this->fetchOngoingDowntimes();
        $this->assertEquals(3, sizeof($paymentDowntime));
    }

    public function testFetchDowntimeWithPagination()
    {
        $this->markTestSkipped("Skipping for now");

        $currentDate = $this->getCurrentDate();
        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'oksbi');
        $this->resolveDowntime();
        sleep(1);

        $this->createDowntime('card', 'BANK', 'issuer', 'JAKA');
        $this->resolveDowntime();
        sleep(1);

        $this->createDowntime('netbanking', 'BANK', 'issuer', 'SBIN');
        $this->resolveDowntime();
        sleep(1);

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate,
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(3, sizeof($paymentDowntime));

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&skip=0&count=0',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(0, sizeof($paymentDowntime));

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okhdfc');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okicici');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('card', 'BANK', 'issuer', 'SBIN');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okaxis');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'ybl');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'paytm');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('card', 'BANK', 'issuer', 'HDFC');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('card', 'BANK', 'issuer', 'KKBK');
        $this->resolveDowntime();
        sleep(1);
        $this->createDowntime('card', 'BANK', 'issuer', 'IDIB');
        $this->resolveDowntime();

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate,
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(12, sizeof($paymentDowntime));


        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&skip=0&count=3',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(3, sizeof($paymentDowntime));

        $dt1 = $paymentDowntime[0];
        $this->assertEquals('card', $dt1['method']);
        $this->assertEquals('IDIB', $dt1['instrument']['issuer']);

        $dt1 = $paymentDowntime[1];
        $this->assertEquals('card', $dt1['method']);
        $this->assertEquals('KKBK', $dt1['instrument']['issuer']);

        $dt1 = $paymentDowntime[2];
        $this->assertEquals('card', $dt1['method']);
        $this->assertEquals('HDFC', $dt1['instrument']['issuer']);


        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&skip=3&count=4',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(4, sizeof($paymentDowntime));


        $dt1 = $paymentDowntime[0];
        $this->assertEquals('card', $dt1['method']);
        $this->assertEquals('SBIN', $dt1['instrument']['issuer']);

        $dt1 = $paymentDowntime[1];
        $this->assertEquals('card', $dt1['method']);
        $this->assertEquals('JAKA', $dt1['instrument']['issuer']);

        $dt1 = $paymentDowntime[2];
        $this->assertEquals('netbanking', $dt1['method']);
        $this->assertEquals('SBIN', $dt1['instrument']['bank']);

        $dt1 = $paymentDowntime[3];
        $this->assertEquals('upi', $dt1['method']);
        $this->assertEquals('paytm', $dt1['instrument']['psp']);

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&skip=3&count=15',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->assertEquals(9, sizeof($paymentDowntime));

        $dt1 = $paymentDowntime[8];
        $this->assertEquals('upi', $dt1['method']);
        $this->assertEquals('oksbi', $dt1['instrument']['vpa_handle']);


    }

    public function testFetchResolvedPayments()
    {
        $this->markTestSkipped("Skipping for now");

        $currentDate = $this->getCurrentDate();

        $this->ba->adminAuth();

        $downtime = $this->createDowntime('upi', 'BANK', 'vpa_handle', 'oksbi');

        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate,
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(0, sizeof($paymentDowntime));

        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $resolveDowntimeRequest = [
            'content' => [
                'end'         => Carbon::now()->timestamp,
            ],
            'method' => 'PUT',
            'url' => '/gateway/downtimes/'.$downtime1['id']
        ];
        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($resolveDowntimeRequest);

        $this->ba->adminAuth();
        $downtime = $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okhdfc');

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate,
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(1, sizeof($paymentDowntime));

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&method=card',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(0, sizeof($paymentDowntime));

    }

    public function testFetchResolvedPaymentsInValidArguments()
    {
        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved',
        ];

        try {
             $this->makeRequestAndGetContent($fetchDowntimeRequest);
            $this->assertTrue(false);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("startDate and endDate should be provided", $e->getMessage());
        }

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate=2021-08-21',
        ];

        try {
            $this->makeRequestAndGetContent($fetchDowntimeRequest);
            $this->assertTrue(false);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("startDate and endDate should be provided", $e->getMessage());
        }

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate=2021-08-21&endDate=2021-08-21',
        ];

        $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate=2021-08-22&endDate=2021-08-21',
        ];

        try {
            $this->makeRequestAndGetContent($fetchDowntimeRequest);
            $this->assertTrue(false);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("startDate should never be greater than endDate", $e->getMessage());
        }

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate=2021-08-01&endDate=2021-09-01',
        ];

        try {
            $this->makeRequestAndGetContent($fetchDowntimeRequest);
            $this->assertTrue(false);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("Date range should be within 30 days", $e->getMessage());
        }

    }

    public function testRefreshHistoricalDowntimeCache()
    {
        $this->markTestSkipped("Skipping for now");

        $currentDate = $this->getCurrentDate();
        $this->ba->adminAuth();

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'oksbi');
        $this->resolveLatestDowntime();

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okhdfc');
        $this->resolveLatestDowntime();

        $this->createDowntime('upi', 'BANK', 'vpa_handle', 'okicic');
        $this->resolveLatestDowntime();

        $this->createDowntime('card', 'BANK', 'issuer', 'SBIN');
        $this->resolveLatestDowntime();

        $this->ba->adminAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved/refresh_cache',
        ];

        $this->ba->adminAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved/refresh_cache?lookbackPeriod=7',
        ];

        $this->makeRequestAndGetContent($fetchDowntimeRequest);
        $this->ba->adminAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved/refresh_cache?lookbackPeriod=9',
        ];

        $this->makeRequestAndGetContent($fetchDowntimeRequest);


        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate
        ];

        $downtimes = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(4, sizeof($downtimes));

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&method=netbanking',
        ];

        $downtimes = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(0, sizeof($downtimes));

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&method=upi',
        ];

        $downtimes = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(3, sizeof($downtimes));

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/resolved?startDate='.$currentDate.'&endDate='.$currentDate.'&method=card',
        ];

        $downtimes = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(1, sizeof($downtimes));
    }


    public function testFetchScheduledDowntimes()
    {
        $this->ba->adminAuth();

        $addDowntimeRequest = [
            'content' => [
                'begin'       => Carbon::now()->addMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(240)->timestamp,
                'gateway'     => 'ALL',
                'reason_code' => 'HIGHER_DECLINES',
                'method'      => 'netbanking',
                'source'      => 'BANK',
                'issuer'      => 'SBIN',
                'scheduled'   => true
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        $downtime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('netbanking',$downtime['method']);
        $this->assertEquals('SBIN',$downtime['issuer']);
        $this->assertEquals('scheduled',$downtime['status']);

        $this->ba->adminAuth();
        $refreshCache = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/scheduled/refresh_cache',
        ];

       $r =  $this->makeRequestAndGetContent($refreshCache);

        $this->ba->privateAuth();
        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/scheduled/',
        ];

        $downtimes = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        $this->assertEquals(1,sizeof($downtimes));

    }

    public function testPaymentDowntimeGetByInvalidId()
    {
        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method' => 'GET',
            'url' => '/payments/downtimes/DummyID'
        ];

        try {
            $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);
        }
        catch (\Exception $e)
        {
            $this->assertExceptionClass($e, BadRequestException::class);
            $this->assertEquals("The id provided does not exist", $e->getMessage());
        }
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

        $this->ba->directAuth();
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

        $this->ba->directAuth();
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

    public function
    testWebhookForPaymentDowntimeStartedEvent()
    {
        Carbon::setTestNow(Carbon::create(2019, 14, 01, null, null, null));

        $this->ba->adminAuth();

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_downtime_webhooks' => '1',
            ],
        ]);

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

        $this->setExpectationForGetMerchantsSubscribingToWebhookEvent('payment.downtime.started');
        // Expects webhook events for all merchants.
        $this->expectWebhookEventWithContents('payment.downtime.started', 'testPaymentDowntimeStartedWebhook');
        $this->testData['testPaymentDowntimeStartedWebhook']['account_id'] = 'acc_10000000000011';
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
        Carbon::setTestNow(Carbon::create(2019, 14, 01, null, null, null));

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

        $this->setExpectationForGetMerchantsSubscribingToWebhookEvent('payment.downtime.resolved');
        // Expects webhook events for all merchants.
        $this->expectWebhookEventWithContents('payment.downtime.resolved', 'testPaymentDowntimeResolvedWebhook');
        $this->testData['testPaymentDowntimeResolvedWebhook']['account_id'] = 'acc_10000000000011';
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

        $this->assertEquals($downtime['vpa_handle'], 'ALL');

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

        $this->assertEquals($downtime2['vpa_handle'], 'ALL');

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
                'source'      => 'DOWNTIME_SERVICE',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
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
                'source'      => 'DOWNTIME_SERVICE',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
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
                'source'      => 'DOWNTIME_SERVICE',
                'reason_code' => 'HIGHER_ERRORS',
                'end'         => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
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
                'source'      => 'DOWNTIME_SERVICE',
                'reason_code' => 'HIGHER_ERRORS',
                'end'         => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();
        $this->updateSignature($request);
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getEntityById('payment.downtime', $issuerDowntimeId, true);

        $this->assertEquals($paymentDowntime['issuer'], 'HDFC');
        $this->assertNotNull($paymentDowntime['end']);
    }

    public function testCardDowntimeResolveAfterFlagDisabled(){
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'      => 'RUPAY',
                'method'      => 'card',
                'source'      => 'DOWNTIME_V2',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
                'issuer'      => 'UNKNOWN'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime['network'], 'RUPAY');
        $this->assertNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'      => 'UNKNOWN',
                'method'      => 'card',
                'source'      => 'DOWNTIME_V2',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
                'issuer'      => 'SBIN'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $paymentDowntime2 = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime2['issuer'], 'SBIN');
        $this->assertNull($paymentDowntime2['end']);

        // setting network flag to false
        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card' => '0',
            ],
        ]);

        $request = [
            'content' => [
                'gateway'     => 'upi_mindgate',
                'method'      => 'upi',
                'source'      => 'DOPPLER',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $downtime1 = $this->getEntityById('payment.downtime', $paymentDowntime['id'], true);

        $this->assertEquals($downtime1['network'], 'RUPAY');
        $this->assertNotNull($downtime1['end']);

        $downtime2 = $this->getEntityById('payment.downtime', $paymentDowntime2['id'], true);

        $this->assertEquals($downtime2['issuer'], 'SBIN');
        $this->assertNotNull($downtime2['end']);
    }

    public function testNetworkDowntimeResolveAfterFlagDisabled(){
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'      => 'RUPAY',
                'method'      => 'card',
                'source'      => 'DOWNTIME_V2',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
                'issuer'      => 'UNKNOWN'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime['network'], 'RUPAY');
        $this->assertNull($paymentDowntime['end']);

        // setting network flag to false
        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_payment_downtimes_card_network' => '0',
            ],
        ]);

        $request = [
            'content' => [
                'gateway'     => 'upi_mindgate',
                'method'      => 'upi',
                'source'      => 'DOPPLER',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);
        $this->assertEquals($paymentDowntime['network'], 'RUPAY');
        $this->assertNotNull($paymentDowntime['end']);
    }

    public function testAllUPIPaymentDowntime()
    {
        $this->createUpiAllGatewayDowntime();

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals($paymentDowntime['method'], 'upi');
        $this->assertEquals($paymentDowntime['vpa_handle'], 'ALL');
    }

    public function testCreatePaymentDowntimeByDowntimeService()
    {

        $this->markTestSkipped("Skipping for now");

        $this->enableGatewayDowntimeService();

        $downtimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLFT',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(3)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(2)->timestamp),
                'ruleId'      => 'rule1',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertNull($response['merchant_id']);

        $this->assertEquals('card', $paymentDowntime['method']);
        $this->assertEquals('VISA', $paymentDowntime['network']);
        $this->assertNull($paymentDowntime['merchant_id']);

        $downtimeCreateRequest2 = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(3)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(2)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();
        $response = $this->makeRequestAndGetContent($downtimeCreateRequest2);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('card', $paymentDowntime['method']);
        $this->assertEquals('VISA', $paymentDowntime['network']);
        $this->assertNull($paymentDowntime['merchant_id']);
    }

    public function testCreatePaymentDowntimeByDowntimeServiceReverse()
    {
        $this->enableGatewayDowntimeService();

        $downtimeCreateRequest2 = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(3)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(2)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest2);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('card', $paymentDowntime['method']);
        $this->assertEquals('VISA', $paymentDowntime['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $downtimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLFT',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(3)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(2)->timestamp),
                'ruleId'      => 'rule1',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();
        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertNull($response['merchant_id']);

        $this->assertEquals('card', $paymentDowntime['method']);
        $this->assertEquals('VISA', $paymentDowntime['network']);
        $this->assertNull($paymentDowntime['merchant_id']);
    }

    public function testCreatePaymentDowntimeMerchantByDowntimeService()
    {
        $this->enableGatewayDowntimeService();

        $downtimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(3)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(2)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('card', $paymentDowntime['method']);
        $this->assertEquals('VISA', $paymentDowntime['network']);
        $this->assertEquals('HGYAjhc', $paymentDowntime['merchant_id']);
    }

    public function testPhonePeAPIVPADowntime()
    {
        $this->enablePhonePeDowntime();

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/phonepe/cron',
            'content' => [
                'a' => 1,
            ],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('upi', $gatewayDowntime['method']);
        $this->assertEquals('PHONEPE', $gatewayDowntime['source']);
        $this->assertEquals('ybl', $gatewayDowntime['vpa_handle']);
        $this->assertNull($gatewayDowntime['end']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('upi', $paymentDowntime['method']);
        $this->assertEquals('ybl', $paymentDowntime['vpa_handle']);
        $this->assertNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/phonepe/cron',
            'content' => [
                'a' => 2,
            ],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('upi', $gatewayDowntime['method']);
        $this->assertEquals('PHONEPE', $gatewayDowntime['source']);
        $this->assertEquals('ybl', $gatewayDowntime['vpa_handle']);
        $this->assertNotNull($gatewayDowntime['end']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('upi', $paymentDowntime['method']);
        $this->assertEquals('ybl', $paymentDowntime['vpa_handle']);
        $this->assertNotNull($paymentDowntime['end']);
    }

    public function testFPXDowntime()
    {
        $this->terminal = $this->fixtures->create('terminal:shared_FPX_banking_terminal');

        $this->ba->adminAuth();

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/fpx/cron',
            'content' => [
                'terminal_id' => $this->terminal->getPublicId()
            ]
        ];

        $this->makeRequestAndGetContent([
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'config:enable_downtime_webhooks' => '1',
            ],
        ]);

        $this->ba->cronAuth();

        $this->setExpectationForGetMerchantsSubscribingToWebhookEvent('payment.downtime.started', 0);

        $this->dontExpectStorkServiceRequest('/twirp/rzp.stork.webhook.v1.WebhookAPI/List');

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('fpx', $gatewayDowntime['method']);
        $this->assertEquals('PAYNET', $gatewayDowntime['source']);
        $this->assertNull($gatewayDowntime['end']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('fpx', $paymentDowntime['method']);
        $this->assertNull($paymentDowntime['end']);
    }

    public function testPhonePeAPIIssuerDowntime()
    {
        $this->enablePhonePeDowntime();

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/phonepe/cron',
            'content' => [
                'a' => 3,
            ],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('upi', $gatewayDowntime['method']);
        $this->assertEquals('PHONEPE', $gatewayDowntime['source']);
        $this->assertEquals('PMCB', $gatewayDowntime['issuer']);
        $this->assertNull($gatewayDowntime['end']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('upi', $paymentDowntime['method']);
        $this->assertEquals('PMCB', $paymentDowntime['issuer']);
        $this->assertNull($paymentDowntime['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $request = [
            'method'  => 'POST',
            'url'     => '/gateway/downtimes/phonepe/cron',
            'content' => [
                'a' => 2,
            ],
        ];

        $this->ba->cronAuth();

        $this->makeRequestAndGetContent($request);

        $gatewayDowntime = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('upi', $gatewayDowntime['method']);
        $this->assertEquals('PHONEPE', $gatewayDowntime['source']);
        $this->assertEquals('PMCB', $gatewayDowntime['issuer']);
        $this->assertNotNull($gatewayDowntime['end']);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('upi', $paymentDowntime['method']);
        $this->assertEquals('PMCB', $paymentDowntime['issuer']);
        $this->assertNotNull($paymentDowntime['end']);
    }

    public function testPaymentDowntimeSeverityChange()
    {
        $request = [
            'content' => [
                'gateway'     => 'ALL',
                'network'     => 'RUPAY',
                'method'      => 'card',
                'source'      => 'DOWNTIME_SERVICE',
                'reason_code' => 'HIGHER_ERRORS',
                'begin'       => strval(Carbon::now()->timestamp),
                'issuer'      => 'UNKNOWN'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('high', $paymentDowntime['severity']);

        $request['content']['source'] = 'VAJRA';
        $request['content']['reason_code'] = 'LOW_SUCCESS_RATE';
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('high', $paymentDowntime['severity']);

        $request['content']['source'] = 'DOPPLER';
        $request['content']['reason_code'] = 'OTHER';
        $this->makeRequestAndGetContent($request);

        $paymentDowntime = $this->getLastEntity('payment.downtime', true);

        $this->assertEquals('high', $paymentDowntime['severity']);
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

            $this->ba->directAuth();
            $this->updateSignature($request);
            $this->makeRequestAndGetContent($request);
        }
    }

    protected function createNetbankingAllGatewayDowntime()
    {
        foreach (['billdesk', 'atom', 'ebs', 'payu', 'paytm', 'cashfree','ccavenue', 'zaakpay', 'ingenico', 'billdesk_optimizer'] as $gateway)
        {
            $request = [
                'content' => [
                    'gateway'     => $gateway,
                    'method'      => 'netbanking',
                    'issuer'      => 'PSIB',
                    'source'      => 'dummy',
                    'reason_code' => 'OTHER',
                    'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
                ],
                'method' => 'POST',
                'url' => '/gateway/downtimes/dummy/webhook'
            ];

            $this->ba->directAuth();
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

            $this->ba->directAuth();
            $this->updateSignature($request);
            $this->makeRequestAndGetContent($request);
        }
    }

    protected function activateDowntimes(string $status)
    {
        $this->ba->cronAuth();

        $this->makeRequestAndGetContent([
            'url'     => '/payments/downtimes/trigger/' . $status,
            'method'  => 'POST',
            'content' => [],
        ]);
    }

    protected function updateSignature(array & $request)
    {
        unset($request['content']['signature']);

        $secret = \Config::get('applications.merchant_dashboard.secret'); // todo verify this

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

    /**
     * Mocks request args expectation for getMerchantsSubscribingToWebhookEvent method.
     * Also returns mocked response with total 3 merchant ids, containing 2 uniques.
     *
     * @param string $event
     * @param integer $occurence
     */

    protected function setExpectationForGetMerchantsSubscribingToWebhookEvent(string $event, $occurence = 1)
    {
        $this->createStorkMock();

        $expectedReqArgs = ['service' => 'api-test', 'owner_type' => 'merchant', 'limit' => 5000, 'active' => true, 'event' => $event];

        $mockedRes = new \WpOrg\Requests\Response;
        $mockedRes->success = true;
        $mockedRes->body = json_encode(["webhooks" => [['owner_id' => '10000000000000'], ['owner_id' => '10000000000000'], ['owner_id' => '10000000000011']]]);

        $this->storkMock
            ->shouldReceive('request')
            ->times($occurence)
            ->with('/twirp/rzp.stork.webhook.v1.WebhookAPI/List', Mockery::subset($expectedReqArgs), 15000)
            ->andReturn($mockedRes);
    }

    /**
     * @param string $method
     * @param string $source
     * @param string $instrument_name
     * @param string $instrument_value
     */
    private function createDowntime(string $method, string $source, string $instrument_name, string $instrument_value)
    {
        $this->ba->adminAuth();
        $addDowntimeRequest = [
            'content' => [
                'begin'          => Carbon::now()->subMinutes(10)->timestamp,
                'gateway'        => 'ALL',
                'reason_code'    => 'HIGHER_DECLINES',
                'method'         => $method,
                'source'         => $source,
                $instrument_name => $instrument_value,
            ],
            'method'  => 'POST',
            'url'     => '/gateway/downtimes'
        ];

        $this->makeRequestAndGetContent($addDowntimeRequest);

        return $this->getLastEntity('payment.downtime', true);
    }

    /**
     * @return mixed
     */
    private function fetchOngoingDowntimes()
    {
        $this->ba->privateAuth();

        $fetchDowntimeRequest = [
            'content' => [],
            'method'  => 'GET',
            'url'     => '/payments/downtimes/ongoing',
        ];

        $paymentDowntime = $this->makeRequestAndGetContent($fetchDowntimeRequest);

        return $paymentDowntime;
    }

    private function resolveLatestDowntime(): void
    {
        $downtime1              = $this->getLastEntity('gateway_downtime', true);
        $resolveDowntimeRequest = [
            'content' => [
                'end' => Carbon::now()->timestamp,
            ],
            'method'  => 'PUT',
            'url'     => '/gateway/downtimes/' . $downtime1['id']
        ];
        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($resolveDowntimeRequest);
    }

    /**
     * @return mixed
     */
    private function getCurrentDate()
    {
        return Carbon::now(Timezone::IST)->format('Y-m-d');
    }

    private function resolveDowntime(): void
    {
        $downtime1 = $this->getLastEntity('gateway_downtime', true);

        $resolveDowntimeRequest = [
            'content' => [
                'end' => Carbon::now()->timestamp,
            ],
            'method'  => 'PUT',
            'url'     => '/gateway/downtimes/' . $downtime1['id']
        ];
        $this->ba->adminAuth();
        $this->makeRequestAndGetContent($resolveDowntimeRequest);
    }
}
