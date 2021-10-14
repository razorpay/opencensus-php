<?php

namespace RZP\Tests\Functional\Payment;

use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Constants\Timezone;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DowntimeTrait;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\MockHttpResponseTrait;

class GatewayDowntimeTest extends TestCase
{
    use PaymentTrait;
    use DowntimeTrait;
    use DbEntityFetchTrait;

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

    protected $dopplerToken;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayDowntimeTestData.php';

        parent::setUp();

        $statusCakeUserName = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $statusCakeApiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

        $this->statusCakeToken = md5($statusCakeUserName . $statusCakeApiKey);

        $dopplerkey = $this->app['config']->get('application.doppler.key');

        $dopplerSecret = $this->app['config']->get('application.doppler.secret');

        $this->dopplerToken = "basic ".base64_encode($dopplerkey.":".$dopplerSecret);

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

        // unsetEditDuplicateInput contains scheduled, hence schedule cannot be edited and will be false
        $this->assertEquals(false, $response2['scheduled']);

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
        $this->ba->vajraAuth();

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
        $this->markTestSkipped("Disabled creation of downtime at terminal level");
        $this->createUpiTerminals();

        $this->commonAlertUPIWebHookTestHandler(__FUNCTION__);
    }

    public function testVajraAlertUPIWebhookMerchantIds()
    {
        $this->markTestSkipped("Disabled creation of downtime at terminal level");
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
        $this->ba->vajraAuth();

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

        $this->ba->vajraAuth();

        $this->startTest();

        $downtimeWithoutTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithoutTerminalId, true);

        $downtimeWithTerminalEntity = $this->getEntityById('gateway_downtime', $downtimeWithTerminalId, true);

        // Check end times

        $this->assertNotNull($downtimeWithoutTerminalEntity['end']);

        $this->assertNotNull($downtimeWithTerminalEntity['end']);
    }

    public function testVajraErrorViaSourceWebhook()
    {
        $this->createUpiTerminals();

        $this->ba->vajraAuth();

        $testData = $this->testData[__FUNCTION__];

        try {
            $this->startTest($testData);
        }
        catch(\Throwable $e) {
            $this->assertEquals('vajra downtime is not created through this webhook.', $e->getMessage());
        }
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
        $this->fixtures->create('terminal:shared_upi_mindgate_terminal');

        $headers = [
            'HTTP_Authorization'    => $this->dopplerToken,
        ];

        $request = [
            'content' => [
                'gateway' => 'upi_mindgate',
                'method' => 'upi',
                'source' => 'doppler',
                'reason_code' => 'ISSUER_DOWN',
                'begin' => strval(Carbon::now()->subMinutes(60)->timestamp)
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/dummy/webhook',
            'server'    => $headers
        ];

        $this->ba->directAuth();

        $this->updateSignature($request);

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['method'], 'upi');
    }

    public function testDopplerDowntimeCreation()
    {
        $this->ba->directAuth();

        $headers = [
            'HTTP_Authorization'    => $this->dopplerToken,
        ];

        $request = [
            'content' => [
                'method'    => 'upi',
                'reason_code'   => 'ISSUER_DOWN',
                'gateway'   => 'ALL',
                'status'    => 'DOWN'
            ],
            'url'   => '/gateway/downtimes/doppler/webhook',
            'method'    => 'POST',
            'server'    => $headers
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNull($response['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $request = [
            'content' => [
                'method'    => 'upi',
                'reason_code'   => 'ISSUER_DOWN',
                'gateway'   => 'ALL',
                'status'    => 'UP'
            ],
            'url'   => '/gateway/downtimes/doppler/webhook',
            'method'    => 'POST',
            'server'    => $headers
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNotNull($response['end']);
    }

    public function testDopplerDowntimeUpdate()
    {
        $this->ba->directAuth();

        $headers = [
            'HTTP_Authorization'    => $this->dopplerToken,
        ];

        $request = [
            'content' => [
                'method'    => 'upi',
                'reason_code'   => 'LOW_SUCCESS_RATE',
                'gateway'   => 'ALL',
                'status'    => 'DOWN'
            ],
            'url'   => '/gateway/downtimes/doppler/webhook',
            'method'    => 'POST',
            'server'    => $headers
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNull($response['end']);

        Carbon::setTestNow(Carbon::now()->addMinutes(10));

        $request = [
            'content' => [
                'method'    => 'upi',
                'reason_code'   => 'ISSUER_DOWN',
                'gateway'   => 'ALL',
                'status'    => 'DOWN'
            ],
            'url'   => '/gateway/downtimes/doppler/webhook',
            'method'    => 'POST',
            'server'    => $headers
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertNull($response['end']);

        $this->assertEquals($response['reason_code'], "ISSUER_DOWN");
    }

    public function testGatewayDowntimeArchival()
    {
        $begin = Carbon::now()->subMinutes(120)->timestamp;
        $end   = Carbon::now()->subMinutes(60)->timestamp;

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

        $downtimeEntity = $this->getDbLastEntity('gateway_downtime');
        $this->assertNotNull($downtimeEntity);

        $request = [
            'content' => [],
            'method' => 'POST',
            'url' => '/gateway/downtimes/archive'
        ];

        $this->ba->cronAuth();
        $this->makeRequestAndGetContent($request);

        $downtimeEntityAfterTest = $this->getDbLastEntity('gateway_downtime');
        $this->assertNull($downtimeEntityAfterTest);

        $downtimeEntityArchived = $this->getDbLastEntity('gateway_downtime_archive');
        $this->assertEquals($downtimeEntity->getId(), $downtimeEntityArchived->getId());
        $this->assertEquals($downtimeEntity->getCreatedAt(), $downtimeEntityArchived->getCreatedAt());
    }

    public function testGetGatewayDowntimeForPaymentUPI()
    {
        $downtimeCreateRequest = [
            'content' => [
                'gateway'     => 'upi_mindgate',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'upi',
                'comment'     => 'Test Reason',
                'source'      => 'VAJRA',
                'begin'       => strval(Carbon::now()->subMinutes(10)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $this->assertEquals( "upi", $response['method']);

        $downtimeGetRequest = [
            'content' => [
                'terminals' => [
                    [
                        'gateway' => 'upi_mindgate',
                    ],
                    [
                        'gateway' => 'upi_sbi',
                    ]
                ],
                'payment' => [
                    'method' => 'upi',
                ]
            ],
            'method' => 'POST',
            'url' => '/router/gateway/downtimes'
        ];

        $config = \Config::get('applications.smart_routing');
        $pwd = $config['secret'];

        $this->ba->appAuth('rzp_test', $pwd);

        $response = $this->makeRequestAndGetContent($downtimeGetRequest);
        $this->assertEquals( "upi", $response['gateway_downtimes'][0]['method']);
        $this->assertEquals( "upi_mindgate", $response['gateway_downtimes'][0]['gateway']);
        $this->assertEquals(1, sizeof($response['gateway_downtimes']));

        $downtimeCreateRequest2 = [
            'content' => [
                'gateway'     => 'upi_sbi',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'upi',
                'comment'     => 'Test Reason',
                'source'      => 'VAJRA',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();

        $response2 = $this->makeRequestAndGetContent($downtimeCreateRequest2);

        $this->assertEquals( "upi", $response2['method']);

        $this->ba->appAuth('rzp_test', $pwd);
        $response2 = $this->makeRequestAndGetContent($downtimeGetRequest);
        $this->assertEquals(2, sizeof($response2['gateway_downtimes']));
    }

    public function testGetGatewayDowntimeForPaymentCard()
    {
        $downtimeCreateRequest = [
            'content' => [
                'gateway'     => 'hitachi',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'comment'     => 'Test Reason',
                'source'      => 'VAJRA',
                'begin'       => strval(Carbon::now()->subMinutes(10)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $this->assertEquals( "card", $response['method']);

        $downtimeGetRequest = [
            'content' => [
                'terminals' => [
                    [
                        'gateway' => 'hitachi',
                    ],
                    [
                        'gateway' => 'card_fss',
                    ]
                ],
                'payment' => [
                    'method' => 'card',
                    'card'  => [
                        'network_code'  => 'RUPAY',
                        'type'          => 'debit',
                    ]
                ]
            ],
            'method' => 'POST',
            'url' => '/router/gateway/downtimes'
        ];

        $config = \Config::get('applications.smart_routing');
        $pwd = $config['secret'];

        $this->ba->appAuth('rzp_test', $pwd);

        $response = $this->makeRequestAndGetContent($downtimeGetRequest);
        $this->assertEquals( "card", $response['gateway_downtimes'][0]['method']);
        $this->assertEquals( "hitachi", $response['gateway_downtimes'][0]['gateway']);
        $this->assertEquals(1, sizeof($response['gateway_downtimes']));

        $downtimeCreateRequest = [
            'content' => [
                'gateway'     => 'card_fss',
                'reason_code' => 'LOW_SUCCESS_RATE',
                'method'      => 'card',
                'comment'     => 'Test Reason',
                'source'      => 'VAJRA',
                'begin'       => strval(Carbon::now()->subMinutes(5)->timestamp),
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $this->ba->adminAuth();
        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $this->assertEquals( "card", $response['method']);

        $this->ba->appAuth('rzp_test', $pwd);
        $response2 = $this->makeRequestAndGetContent($downtimeGetRequest);
        $this->assertEquals(2, sizeof($response2['gateway_downtimes']));
    }

    public function testMerchantDowntimeByDowntimeService()
    {
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);
        $this->assertNotNull($response['end']);
    }

    public function testMerchantAndPaymentDowntimeInOrderByDowntimeService()
    {
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'RuPay',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);
        $this->assertNotNull($response['end']);

        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'RuPay',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    public function testMerchantAndPaymentDowntimeOutOfOrder1ByDowntimeService()
    {
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'RuPay',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'RuPay',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    public function testMerchantAndPaymentDowntimeOutOfOrder2ByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'RuPay';
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);
        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('RUPAY', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * where merchant downtime gets created first followed by payment downtime creation and resolution.
     *
     * It should succeed,  as platform creation doesn't have any check on merchant downtime creation.
     *
     */
    public function testMerchantAndPaymentDowntimeOnSameNetworkInOrderByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'Visa';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * where payment downtime gets created first followed by merchant downtime creation and resolution.
     *
     * exception has to be thrown for merchant create, empty response for resolve.
     * for merchant create --> Ongoing platform downtime.
     *
     */
    public function testPaymentAndMerchantDowntimeOnSameNetworkInOrderByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'Visa';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);


        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertNull( $mDowntimeResolveResponse);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * Merchant downtime gets created
     * Payment downtime gets created
     * Merchant downtime gets resolved
     * Payment downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameNetworkByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'Visa';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * Merchant downtime gets created
     * Payment downtime gets created
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameNetworkScenario2ByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'Visa';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);
        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);


        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * Payment downtime gets created
     * Merchant downtime gets created
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     * Merchant downtime gets created
     * Merchant downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameNetworkScenario3ByDowntimeService()
    {
        $mNetwork  = 'Visa';
        $pNetwork = 'Visa';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $mNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => $pNetwork,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse0 = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $this->assertNull($mDowntimeResolveResponse0);

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('VISA', $mDowntimeresponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResponse['network']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $mDowntimeResolveResponse['network']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('VISA', $pDowntimeResolveResponse['network']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     * Payment downtime gets created
     * Merchant downtime gets created
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     * Merchant downtime gets created
     * Merchant downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameIssuerByDowntimeService()
    {
        $mIssuer  = 'SBIN';
        $pIssuer = 'SBIN';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();
        $this->ba->downtimeServiceAuth();
        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse0 = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $this->assertNull($mDowntimeResolveResponse0);

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals('card', $mDowntimeresponse['method']);
        $this->assertEquals('SBIN', $mDowntimeresponse['issuer']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals('card', $pDowntimeResponse['method']);
        $this->assertEquals('SBIN', $pDowntimeResponse['issuer']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals('card', $mDowntimeResolveResponse['method']);
        $this->assertEquals('SBIN', $mDowntimeResolveResponse['issuer']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals('card', $pDowntimeResolveResponse['method']);
        $this->assertEquals('SBIN', $pDowntimeResolveResponse['issuer']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     *
     * Downtime for netbanking.
     *
     * Payment downtime gets created
     * Merchant downtime gets created
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     * Merchant downtime gets created
     * Merchant downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameNetbankingIssuerByDowntimeService()
    {
        $mIssuer  = 'SBIN';
        $method = 'netbanking';
        $this->enableGatewayDowntimeService();
        $this->mockRazorx();
        $this->enableNetbankingDowntimeCreation();

        $this->ba->downtimeServiceAuth();

        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse0 = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $this->assertNull($mDowntimeResolveResponse0);

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals($method, $mDowntimeresponse['method']);
        $this->assertEquals('SBIN', $mDowntimeresponse['issuer']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals($method, $pDowntimeResponse['method']);
        $this->assertEquals('SBIN', $pDowntimeResponse['issuer']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals($method, $mDowntimeResolveResponse['method']);
        $this->assertEquals('SBIN', $mDowntimeResolveResponse['issuer']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals($method, $pDowntimeResolveResponse['method']);
        $this->assertEquals('SBIN', $pDowntimeResolveResponse['issuer']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }


    /**
     *
     * Downtime for upi.
     *
     * Payment downtime gets created
     * Merchant downtime gets created
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     * Merchant downtime gets created
     * Merchant downtime gets resolved
     */
    public function testPaymentAndMerchantDowntimeOnSameUPIIssuerByDowntimeService()
    {
        $mIssuer  = 'oksbi';
        $method = 'upi';
        $this->mockRazorx();
        $this->enableGatewayDowntimeService();

        $this->enableNetbankingDowntimeCreation();

        $this->ba->downtimeServiceAuth();

        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse0 = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $this->assertNull($mDowntimeResolveResponse0);

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals($method, $mDowntimeresponse['method']);
        $this->assertEquals('oksbi', $mDowntimeresponse['vpa_handle']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals($method, $pDowntimeResponse['method']);
        $this->assertEquals('oksbi', $pDowntimeResponse['vpa_handle']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals($method, $mDowntimeResolveResponse['method']);
        $this->assertEquals('oksbi', $mDowntimeResolveResponse['vpa_handle']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals($method, $pDowntimeResolveResponse['method']);
        $this->assertEquals('oksbi', $pDowntimeResolveResponse['vpa_handle']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }

    /**
     *
     * Downtime for upi.
     *
     * Payment downtime gets created
     * Merchant downtime gets created
     * Merchant downtime severity update
     * Payment downtime gets resolved
     * Merchant downtime gets resolved
     * Merchant downtime gets created
     * Merchant downtime gets resolved
     */
    public function testMerchantDowntimeSeverityChangeDuringOngoingPlatformDowntime()
    {
        $mIssuer  = 'oksbi';
        $method = 'upi';

        $this->mockRazorX();

        $this->enableGatewayDowntimeService();

        $this->enableNetbankingDowntimeCreation();

        $this->ba->downtimeServiceAuth();

        $merchantDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $paymentDowntimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $merchantDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];
        $platformDowntimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => $method,
                'issuer'      => $mIssuer,
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLTF',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $pDowntimeResponse = $this->makeRequestAndGetContent($paymentDowntimeCreateRequest);

        try
        {
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        try
        {
            $merchantDowntimeCreateRequest['content']['severity'] = 'MEDIUM';
            $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        }
        catch(\Exception $e)
        {
            $this->assertExceptionClass($e, Exception\LogicException::class);
        }

        $pDowntimeResolveResponse = $this->makeRequestAndGetContent($platformDowntimeResolveRequest);
        $mDowntimeResolveResponse0 = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);
        $this->assertNull($mDowntimeResolveResponse0);

        $mDowntimeresponse = $this->makeRequestAndGetContent($merchantDowntimeCreateRequest);
        $mDowntimeResolveResponse = $this->makeRequestAndGetContent($merchantDowntimeResolveRequest);

        $this->assertEquals($method, $mDowntimeresponse['method']);
        $this->assertEquals('oksbi', $mDowntimeresponse['vpa_handle']);
        $this->assertEquals('HGYAjhc', $mDowntimeresponse['merchant_id']);

        $this->assertEquals($method, $pDowntimeResponse['method']);
        $this->assertEquals('oksbi', $pDowntimeResponse['vpa_handle']);
        $this->assertNull($pDowntimeResponse['merchant_id']);

        $this->assertEquals($method, $mDowntimeResolveResponse['method']);
        $this->assertEquals('oksbi', $mDowntimeResolveResponse['vpa_handle']);
        $this->assertEquals('HGYAjhc', $mDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($mDowntimeResolveResponse['end']);

        $this->assertEquals($method, $pDowntimeResolveResponse['method']);
        $this->assertEquals('oksbi', $pDowntimeResolveResponse['vpa_handle']);
        $this->assertNull( $pDowntimeResolveResponse['merchant_id']);
        $this->assertNotNull($pDowntimeResolveResponse['end']);
    }
    public function testCreateGatewayDowntimeByDowntimeService()
    {
        $this->markTestSkipped('Failing on latest changes');
        $this->enableGatewayDowntimeService();

        $downtimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'PLFT',
                'begin'       => strval(Carbon::now()->subMinutes(15)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(13)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(12)->timestamp),
                'ruleId'      => 'rule1',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertNull($response['merchant_id']);

        $downtimeCreateRequest2 = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'CREATE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest2);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);

        $downtimeResolveRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'PLFT',
                'begin'       => strval(Carbon::now()->subMinutes(8)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(7)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(6)->timestamp),
                'ruleId'      => 'rule1',
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $this->ba->downtimeServiceAuth();

        $response = $this->makeRequestAndGetContent($downtimeResolveRequest);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertNull($response['merchant_id']);
        $this->assertNotNull($response['end']);

        $downtimeResolveRequest2 = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'card',
                'network'     => 'Visa',
                'strategy'    => 'SUCCESS_RATE',
                'action'      => 'RESOLVE',
                'type'        => 'MERCHANT',
                'begin'       => strval(Carbon::now()->subMinutes(14)->timestamp),
                'end'         => strval(Carbon::now()->subMinutes(12)->timestamp),
                'eventTime'   => strval(Carbon::now()->subMinutes(10)->timestamp),
                'ruleId'      => 'rule1',
                'merchantId' => 'HGYAjhc'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes/webhook/downtime_service'
        ];

        $response = $this->makeRequestAndGetContent($downtimeResolveRequest2);

        $this->assertEquals('card', $response['method']);
        $this->assertEquals('VISA', $response['network']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);
        $this->assertNotNull($response['end']);
    }

    public function testCreateGatewayDowntimeForUpiByDowntimeService()
    {
        $this->enableGatewayDowntimeService();
        $this->markTestSkipped('Failing on latest changes');
        $downtimeCreateRequest = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'upi',
                'issuer'      => 'oksbi',
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

        $this->assertEquals('upi', $response['method']);
        $this->assertEquals('oksbi', $response['vpa_handle']);
        $this->assertNull($response['merchant_id']);

        $downtimeCreateRequest2 = [
            'content' => [
                'severity'    => 'HIGH',
                'method'      => 'upi',
                'issuer'     => 'oksbi',
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

        $response = $this->makeRequestAndGetContent($downtimeCreateRequest2);

        $this->assertEquals('upi', $response['method']);
        $this->assertEquals('oksbi', $response['vpa_handle']);
        $this->assertEquals('HGYAjhc', $response['merchant_id']);
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

        $secret = \Config::get('applications.merchant_dashboard.secret');

        $signature = hash_hmac('sha256', json_encode($request['content']), $secret);

        $request['content']['signature'] = $signature;
    }

    private function enableNetbankingDowntimeCreation(): void
    {
        $this->makeRequestAndGetContent([
                                            'method'  => 'PUT',
                                            'url'     => '/config/keys',
                                            'content' => [
                                                'config:enable_downtime_service_netbanking' => '1'
                                            ],
                                        ]);
    }

    protected function mockRazorx()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx
            ->method('getTreatment')
            ->willReturn('enabled');
    }
}
