<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\MockHttpResponseTrait;

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

        $this->ba->adminAuth();
    }

    //----- Create Tests -----

    // general tests

    public function testGatewayCreateDowntimeNetbanking()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayFetchDowntimes()
    {
        $this->testGatewayCreateDowntimeNetbanking();

        $this->startTest();
    }

    public function testExternalApiHealth()
    {
        $this->startTest();
    }

    public function testExternalApiInvalidUrl()
    {
        $this->startTest();
    }

    public function testExternalApiWithGatewayResponse500()
    {
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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $this->updateSignature($request);

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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['scheduled'] = '1';

        $downtimeTo = Carbon::now()->addMinutes(60)->timestamp;

        $request['content']['end'] = strval($downtimeTo);

        unset($request['content']['source']);

        $this->updateSignature($request);

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
                'scheduled'   => '1',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp),
                'end'         => strval(Carbon::now()->addMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['source'] = 'other';

        $this->updateSignature($request);

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
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp),
                'gateway'     => 'axis_migs',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'source'      => 'other',
                'acquirer'    => 'axis',
                'network'     => 'VISA',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['network'] = 'VISA';

        $request['content']['card_type'] = 'debit';

        $request['content']['reason_code'] = 'OTHER';

        $request['content']['end']  = strval(Carbon::now()->addMinutes(60)->timestamp);

        unset($request['content']['source']);

        $this->updateSignature($request);

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals('OTHER', $response2['reason_code']);

        $this->assertEquals('VISA', $response2['network']);
    }

    public function testCreateDowntimeInvalidGateway()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateDowntimePayLater()
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
            $this->assertExceptionClass($e, Exception\BadRequestException::class);
        }
    }

    public function testGatewayDowntimeWithoutBegin()
    {
        $this->startTest();
    }

    public function testGatewayDowntimeWithDifferentCardNetworks()
    {
        $begin = Carbon::now()->subMinutes(60)->timestamp;
        $end   = Carbon::now()->addMinutes(60)->timestamp;

        $request = [
            'content' => [
                'begin'       => $begin,
                'end'         => $end,
                'gateway'     => 'axis_migs',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'source'      => 'other',
                'acquirer'    => 'axis',
                'network'     => 'VISA',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['network'], 'VISA');
        $this->assertEquals($response['begin'], $begin);
        $this->assertEquals($response['end'], $end);

        $request['content']['network'] = 'MC';
        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['network'], 'MC');
        $this->assertEquals($response['begin'], $begin);
        $this->assertEquals($response['end'], $end);
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

    public function testGatewayDowntimeDelete()
    {
        $content = $this->createGatewayDowntime();

        $url = '/gateway/downtimes/'. $content['id'];

        $lastEntity = $this->getLastEntity('gateway_downtime', true);

        $response = $this->makeRequestAndGetContent([
            'content' => [],
            'method'  => 'DELETE',
            'url'     => $url
        ]);

        $this->assertEquals($lastEntity['id'], $response['id']);

        $newLastEntity = $this->getLastEntity('gateway_downtime', true);

        $this->assertNotEquals($newLastEntity['id'], $lastEntity['id']);
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
        $to = $from + 60 * 60;

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

    protected function commonAlertUPIWebHookTestHandler($testName)
    {
        $this->ba->appAuth();

        // create downtime

        $testData = $this->testData[$testName];

        $responseDataArray = $this->startTest($testData);

        $expectedDowntimeCreatedResponse = $this->testData[$testName]['downtimeCreatedResponse'];

        $this->assertArraySelectiveEquals($expectedDowntimeCreatedResponse, $responseDataArray);

        foreach ($responseDataArray as $responseData)
        {
            $this->assertNotNull($responseData['begin']);

            $this->assertNull($responseData['end']);
        }

        $gatewayDowntimeEntityIds = array_pluck($responseDataArray, 'id');

        $gatewayDowntimeBeginTimes = array_pluck($responseDataArray, 'begin');

        // duplicate create downtime

        $responseDataArray = $this->startTest($testData);

        $this->assertEmpty($responseDataArray);

        // resolve downtime

        $testData['request']['content']['state'] = 'ok';

        $responseDataArray = $this->startTest($testData);

        $this->assertArraySelectiveEquals($expectedDowntimeCreatedResponse, $responseDataArray);

        foreach ($responseDataArray as $responseData)
        {
            $this->assertNotNull($responseData['begin']);

            $this->assertNotNull($responseData['end']);
        }

        $this->assertEquals($gatewayDowntimeEntityIds, array_pluck($responseDataArray, 'id'));

        $this->assertEquals($gatewayDowntimeBeginTimes, array_pluck($responseDataArray, 'begin'));

        // duplicate resolve downtime

        $responseDataArray = $this->startTest($testData);

        $this->assertEmpty($responseDataArray);
    }

    protected function createUpiTerminals()
    {
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        // Create another upi mindgate terminal for merchant 100000Razorpay
        $upiMindgateTerm2Attributes = [
            'id'                        => '100UPIMindtml2',
            'merchant_id'               => '100000Razorpay',
            'gateway'                   => 'upi_mindgate',
            'gateway_merchant_id'       => 'razorpay upi mindgate',
            'gateway_terminal_id'       => 'nodal account upi hdfc term 2',
            'gateway_merchant_id2'      => 'razorpay@hdfcbank',
            'gateway_terminal_password' => '3723476ytfew7823623gdgf87236',
            'upi'                       => 1,
            'gateway_acquirer'          => 'hdfc',
        ];

        $this->fixtures->create('terminal', $upiMindgateTerm2Attributes);
    }

    public function testVajraAlertUPIWebhookMerchantId()
    {
        $this->createUpiTerminals();

        $this->commonAlertUPIWebHookTestHandler(__FUNCTION__);
    }

    public function testVajraAlertUPIWebhookMerchantIds()
    {
        $this->createUpiTerminals();

        $this->commonAlertUPIWebHookTestHandler(__FUNCTION__);
    }

    public function testVajraAlertUPIWebhookTerminalId()
    {
        $this->createUpiTerminals();

        $this->commonAlertUPIWebHookTestHandler(__FUNCTION__);
    }

    public function testVajraAlertUPIWebhookTerminalIds()
    {
        $this->createUpiTerminals();

        $this->commonAlertUPIWebHookTestHandler(__FUNCTION__);
    }

    public function testVajraAlertUPIWebhookWithoutTerminalDowntime()
    {
        $this->ba->appAuth();

        $this->fixtures->create("terminal:shared_upi_mindgate_terminal");

        // Create alert for gateway + terminal

        array_set(
            $this->testData[__FUNCTION__],
            'request.content.message',
            $this->testData[__FUNCTION__]['messageFor']['withTerminal']
        );

        array_set(
            $this->testData[__FUNCTION__],
            'response',
            $this->testData[__FUNCTION__]['downtimeResponseWithTerminal']
        );

        $responseDataArray = $this->startTest();

        $this->assertNotNull($responseDataArray[0]['terminal_id']);

        $downtimeWithTerminalId = $responseDataArray[0]['id'];

        // Create alert for gateway

        array_set(
            $this->testData[__FUNCTION__],
            'request.content.message',
            $this->testData[__FUNCTION__]['messageFor']['withoutTerminal']
        );

        array_set(
            $this->testData[__FUNCTION__],
            'response',
            $this->testData[__FUNCTION__]['downtimeResponseWithoutTerminal']
        );

        $responseDataArray = $this->startTest();

        $this->assertNull($responseDataArray[0]['terminal_id']);

        $downtimeWithoutTerminalId = $responseDataArray[0]['id'];

        // Check if seperate downtimes created

        $this->assertNotEquals($downtimeWithTerminalId, $downtimeWithoutTerminalId);

        // Resolve alert for gateway

        $this->testData[__FUNCTION__]['request']['content']['state'] = 'ok';

        $this->startTest();

        $downtimeWithoutTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithoutTerminalId, true);

        $downtimeWithTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithTerminalId, true);

        // Check end times

        $this->assertNotNull($downtimeWithoutTerminalEntity['end']);

        $this->assertNull($downtimeWithTerminalEntity['end']);

        // Resolve alert for gateway + terminal

        array_set(
            $this->testData[__FUNCTION__],
            'request.content.message',
            $this->testData[__FUNCTION__]['messageFor']['withTerminal']
        );

        array_set(
            $this->testData[__FUNCTION__],
            'response',
            $this->testData[__FUNCTION__]['downtimeResponseWithTerminal']
        );

        $this->ba->appAuth();

        $this->startTest();

        $downtimeWithoutTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithoutTerminalId, true);

        $downtimeWithTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithTerminalId, true);

        // Check end times

        $this->assertNotNull($downtimeWithoutTerminalEntity['end']);

        $this->assertNotNull($downtimeWithTerminalEntity['end']);
    }

    public function testGatewayCreateOverlappingDowntimeViaDashboard1()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                ██████████████████████████████████                  |
        // |                                ██████████████████████████████████  |
        $request['content']['begin'] = Carbon::createFromTime(0, 30, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 50, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingDowntimeViaDashboard2()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                ██████████████████████████████████                  |
        // |██████████████████████████████████                                  |
        $request['content']['begin'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 30, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingDowntimeViaDashboard3()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                ██████████████████████████████████                  |
        // |                        ███████████████                             |
        $request['content']['begin'] = Carbon::createFromTime(0, 25, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 35, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingDowntimeViaDashboard4()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                ██████████████████████████████████                  |
        // |██████████████████████████████████████████████████████████████████  |
        $request['content']['begin'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 50, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithExistingNullEndDowntimeViaDashboard1()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);

        $response = $this->makeRequestAndGetContent($request);

        // |                      ████████████████████████████████████████████████∞
        // |     ███████████████████████                                        |
        $request['content']['begin'] = Carbon::createFromTime(0, 0, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 30, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithExistingNullEndDowntimeViaDashboard2()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);

        $response = $this->makeRequestAndGetContent($request);

        // |                      ████████████████████████████████████████████████∞
        // |                            ███████████                             |
        $request['content']['begin'] = Carbon::createFromTime(0, 30, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithNewNullEndDowntimeViaDashboard1()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;;

        $response = $this->makeRequestAndGetContent($request);

        // |     ███████████████████████                                        |
        // | █████████████████████████████████████████████████████████████████████∞
        $request['content']['begin'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithNewNullEndDowntimeViaDashboard2()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;;

        $response = $this->makeRequestAndGetContent($request);

        // |     ███████████████████████                                        |
        // |                ██████████████████████████████████████████████████████∞
        $request['content']['begin'] = Carbon::createFromTime(0, 30, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithBothNullEndDowntimeViaDashboard1()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);

        $response = $this->makeRequestAndGetContent($request);

        // |                ██████████████████████████████████████████████████████∞
        // |     █████████████████████████████████████████████████████████████████∞
        $request['content']['begin'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateOverlappingWithBothNullEndDowntimeViaDashboard2()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);

        $response = $this->makeRequestAndGetContent($request);

        // |     █████████████████████████████████████████████████████████████████∞
        // |                ██████████████████████████████████████████████████████∞
        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);
        $this->expectException(Exception\BadRequestException::class);
        $this->expectExceptionMessage('A conflicting gateway downtime already exists.');
        $this->makeRequestAndGetContent($request);
    }

    public function testGatewayCreateNonOverlappingDowntimeViaDashboard1()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                      ███████████████                               |
        // |     ███████████████                                                |
        $request['content']['begin'] = Carbon::createFromTime(0, 0, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response['id']);
    }

    public function testGatewayCreateNonOverlappingDowntimeViaDashboard2()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;

        $response = $this->makeRequestAndGetContent($request);

        // |                      ███████████████                               |
        // |                                        ███████████████             |
        $request['content']['begin'] = Carbon::createFromTime(0, 50, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(1, 0, 0, Timezone::IST)->timestamp;
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response['id']);
    }

    public function testGatewayCreateNonOverlappingWithExistingNullEndDowntimeViaDashboard()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);

        $response = $this->makeRequestAndGetContent($request);

        // |                      ████████████████████████████████████████████████∞
        // |     ███████████████                                                |
        $request['content']['begin'] = Carbon::createFromTime(0, 0, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 10, 0, Timezone::IST)->timestamp;
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response['id']);
    }

    public function testGatewayCreateNonOverlappingWithNewNullEndDowntimeViaDashboard()
    {
        $request = $this->getDowntimeCreationRequest();

        $this->ba->adminAuth();

        $request['content']['begin'] = Carbon::createFromTime(0, 20, 0, Timezone::IST)->timestamp;
        $request['content']['end'] = Carbon::createFromTime(0, 40, 0, Timezone::IST)->timestamp;;

        $response = $this->makeRequestAndGetContent($request);

        // |     ███████████████                                                |
        // |                      ████████████████████████████████████████████████∞
        $request['content']['begin'] = Carbon::createFromTime(0, 50, 0, Timezone::IST)->timestamp;
        unset($request['content']['end']);
        $response = $this->makeRequestAndGetContent($request);
        $this->assertNotNull($response['id']);
    }

    public function testUPIDowntimeCreation()
    {
        $this->fixtures->create("terminal:shared_upi_mindgate_terminal");

        $request = [
            'content' => [
                'gateway'     => 'upi_mindgate',
                'method'      => 'upi',
                'source'      => 'doppler',
                'psp'         => 'bhim',
                'reason_code' => 'ISSUER_DOWN',
                'begin'       => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook'
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['psp'], 'bhim');
    }

    protected function getDowntimeCreationRequest(): array
    {
        return [
            'content' => [
                'gateway'     => 'netbanking_hdfc',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'netbanking',
                'issuer'      => 'HDFC',
                'comment'     => 'Test Reason',
                'source'      => 'statuscake',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];
    }

    protected function updateSignature(array & $request)
    {
        unset($request['content']['signature']);

        $secret = \Config::get('applications.dashboard.secret');

        $signature = hash_hmac('sha256', json_encode($request['content']), $secret);

        $request['content']['signature'] = $signature;
    }
}
