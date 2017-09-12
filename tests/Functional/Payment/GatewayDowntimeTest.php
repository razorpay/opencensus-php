<?php

namespace RZP\Tests\Functional\Payment;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use Carbon\Carbon;

class GatewayDowntimeTest extends TestCase
{
    use PaymentTrait;

    protected $gatewayBankMap = [
        'netbanking_hdfc'  => 'HDFC',
        'netbanking_kotak' => 'KKBK',
    ];

    protected $gatewayMethodName = [
        'netbanking_hdfc'  => 'netbanking',
        'netbanking_kotak' => 'netbanking',
        'axis_migs'        => 'card'
    ];

    protected $statusCakeToken;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayDowntimeTestData.php';

        parent::setUp();

        $statusCakeUserName = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $statusCakeApiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

        $this->statusCakeToken = md5($statusCakeUserName . $statusCakeApiKey);

        $this->ba->appAuth();
    }

    //----- Create Tests -----

    // general tests

    public function testGatewayCreateDowntimeNetbanking()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayCreateDowntimeDuplicate()
    {
        $request = [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'netbanking',
                'issuer'      => 'HDFC',
                'comment'     => 'Test Reason',
                'source'      => 'statuscake',
                'begin'        => Carbon::now()->subMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], $response2['id']);

        $this->assertEquals('ISSUER_DOWN', $response2['reason_code']);
    }

    public function testGatewayDowntimeDuplicateWithUpdatedScheduled()
    {
        $request = [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'netbanking',
                'issuer'      => 'HDFC',
                'comment'     => 'Test Reason',
                'source'      => 'statuscake',
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['scheduled'] = true;

        $downtimeTo = Carbon::now()->addMinutes(60)->timestamp;

        $request['content']['end'] = $downtimeTo;

        unset($request['content']['source']);

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], $response2['id']);

        $this->assertEquals($downtimeTo, $response2['end']);

        $this->assertEquals(true, $response2['scheduled']);

        $downtimeEntity = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('statuscake', $downtimeEntity['source']);

    }

    public function testGatewayDowntimeDuplicateWithoutScheduled()
    {
        $request = [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'netbanking',
                'issuer'      => 'HDFC',
                'comment'     => 'Test Reason',
                'source'      => 'statuscake',
                'scheduled'   => true,
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'end'         => Carbon::now()->addMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['source'] = 'other';

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertNotEquals($response['id'], $response2['id']);

        $this->assertEquals('ISSUER_DOWN', $response2['reason_code']);

        $this->assertEquals(true, $response2['scheduled']);

        $downtimeEntity = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals('other', $downtimeEntity['source']);

    }

    // tests with 2 inputs, one having minimal input while
    // the other having max input. We need to create 2 entries
    // for this
    public function testGatewayDowntimeDuplicateWithCreate()
    {
        $request = [
            'content' => [
                'begin'       => Carbon::now()->subMinutes(60)->timestamp,
                'gateway'     => 'axis_migs',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'source'      => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['network'] = 'VISA';

        $request['content']['card_type'] = 'debit';

        $request['content']['reason_code'] = 'OTHER';

        $request['content']['end']  = Carbon::now()->addMinutes(60)->timestamp;

        unset($request['content']['source']);

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals('OTHER', $response2['reason_code']);

        $this->assertEquals('VISA', $response2['network']);

    }

    public function testCreateDowntimeInvalidGateway()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayInvalidTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $from = $this->testData[__FUNCTION__]['request']['content']['begin'];

        $this->testData[__FUNCTION__]['request']['content']['end'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateDowntimeInvalidSource()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $from = $this->testData[__FUNCTION__]['request']['content']['begin'];

        $this->testData[__FUNCTION__]['request']['content']['end'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateDowntimeInvalidFrom()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        // more than end of time
        $from = 2147483648;

        $this->testData[__FUNCTION__]['request']['content']['begin'] = $from;

        $this->testData[__FUNCTION__]['request']['content']['end'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateDowntimeInvalidTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        // more than end of time
        $to = 2147483648;

        $this->testData[__FUNCTION__]['request']['content']['end'] = $to;

        $this->startTest();
    }

    public function testGatewayCreateNullTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        unset($this->testData[__FUNCTION__]['request']['content']['end']);

        $this->startTest();
    }

    public function testGatewayInvalidReasonCode()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeWithTerminal()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal', ['used_count' => 2]);

        $tid = $terminal['id'];

        $request = [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'begin'       => time(),
                'method'      => 'netbanking',
                'terminal_id' => $tid,
                'issuer'      => 'HDFC',
                'source'      => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['terminal_id'], $tid);
    }

    public function testGatewayDowntimeWithInvalidTerminal()
    {
        $tid = '6fNfsofiUqP1rs';

        $request = [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'begin'       => time(),
                'method'      => 'netbanking',
                'terminal_id' => $tid,
                'issuer'      => 'HDFC',
                'source'      => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        try
        {
            $this->makeRequestAndGetContent($request);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, 'Illuminate\Database\QueryException');
        }
    }

    // netbanking
    public function testGatewayCreateDowntimeNetbankingPartial()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateDowntimeNBInvalidIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateDowntimeNBNonSupportedIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateNBGeneral()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateNBAllIssuers()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    // card
    public function testCreateDowntimeCardInvalidIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeForCard()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeForCardWithoutIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeForCardUnsupportedNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeForCardInvalidNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeForCardInvalidCardType()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeCardWithTypeIssuerNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeCardSpecificIssuerCardType()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeCardSpecificIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeCardSpecificIssuerNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeCardCompleteGateway()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    // wallets

    public function testGatewayDowntimeWithWallet()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeWithInvalidWallet()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayDowntimeWithInvalidWalletIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();

    }


    //----- Update Tests -----

    public function testGatewayDowntimeUpdate()
    {
        $content = $this->createGatewayDowntime();

        $url = '/gateway/downtimes/'. $content['id'];

        $now = Carbon::now()->getTimestamp();

        $to = Carbon::now()->addMinutes(100)->timestamp;

        $lastEntity = $this->getLastEntity('gateway_downtime', true);
        $request = [
            'content' => [
                'begin' => $now,
                'end' => $to,
                'comment' => 'SOME_COMMENT'
            ],
            'method' => 'PUT',
            'url' => $url
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['begin'], $now);

        $this->assertEquals($content['end'], $to);

        $this->assertEquals($content['comment'], 'SOME_COMMENT');
    }

    // statuscake tests
    public function testStatusCakeWebHookNB()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeWebHookCard()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeWebHookWallet()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeWebHookUPI()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeInvalidNB()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeInvalidCard()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeInvalidWallet()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeInvalidUPI()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeInvalidFormat()
    {
        $this->ba->directAuth();

        $this->testData[__FUNCTION__]['request']['content']['Token'] = $this->statusCakeToken;

        $this->startTest();
    }

    public function testStatusCakeWebHookUpdate()
    {
        $this->ba->directAuth();

        $content = [
            'URL' => 'http://www.example.com',
            'Token' => $this->statusCakeToken,
            'Method' => 'Website',
            'Name' => 'Test',
            'StatusCode' => 400,
            'Status' => 'Down',
            'Tags' => '{"method": "netbanking", "issuer": "hdfc"}'
        ];

        $request = [
            'content' => $content,
            'url' => '/gateway/downtimes/status_cake/webhook',
            'method' => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNull($response['end']);

        $content['Status'] = 'Up';

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['end']);
    }

    public function testStatusCakeWebHookMissingToken()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

    public function testStatusCakeWebHookInvalidToken()
    {
        $this->ba->directAuth();

        $this->startTest();
    }

    //----- helpers -----

    protected function fillDefaultsForTests($functionName)
    {
        $now = time();

        $to = $now + 100;

        $this->testData[$functionName]['request']['content']['begin'] = $now;

        $this->testData[$functionName]['request']['content']['end'] = $to;
    }

    protected function createGatewayDowntime($gatewayName = 'netbanking_hdfc', $terminalId = null)
    {
        $from = Carbon::now()->subMinutes(60)->timestamp;

        $to = Carbon::now()->addMinutes(60)->timestamp;

        return $this->__createGatewayDowntime($gatewayName, $from, $to, $terminalId);
    }

    protected function createGatewayDowntimeNullTo($gatewayName = 'netbanking_hdfc')
    {
        $from = Carbon::now()->subMinutes(60)->timestamp;

        $to = null;

        return $this->__createGatewayDowntime($gatewayName, $from, $to, null);
    }

    protected function createGatewayDowntimeForOneHour($gatewayName = 'netbanking_hdfc', $from)
    {
        $to = $from + 60*60;

        return $this->__createGatewayDowntime($gatewayName, $from, $to, null);
    }

    protected function __createGatewayDowntime($gatewayName, $from, $to, $terminalId)
    {
        $bank = $this->gatewayBankMap[$gatewayName];

        $method = $this->gatewayMethodName[$gatewayName];

        $content = [
            'gateway' => $gatewayName,
            'reason_code'  => 'LOW_SUCCESS_RATE',
            'issuer'  => $bank,
            'begin'  => $from,
            'end' => $to,
            'method' => $method,
            'source' => 'other'
        ];

        if (empty($terminalId) === false)
        {
            $content['terminal_id'] = $terminalId;
        }

        $request = [
            'content' => $content,
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        if ($to === null)
        {
            unset($request['content']['end']);
        }

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }

    protected function createGatewayDowntimeWithEmptyTo($gatewayName, $from)
    {
        $bank = $this->gatewayBankMap[$gatewayName];

        $method = $this->gatewayMethodName[$gatewayName];

        $request = [
            'content' => [
                'gateway' => $gatewayName,
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer'  => $bank,
                'begin'  => $from,
                'method' => $method,
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }

    protected function createGatewayDowntimeWithCard($gatewayName, $from, $cardType, $network)
    {
        $method = $this->gatewayMethodName[$gatewayName];

        $request = [
            'content' => [
                'gateway' => $gatewayName,
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'begin'  => $from,
                'method' => $method,
                'card_type' => $cardType,
                'network' => $network,
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }
}
