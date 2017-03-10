<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use Carbon\Carbon;

class GatewayAbsenceTest extends TestCase
{
    use PaymentTrait;

    protected $gatewayBankMap = [
        'netbanking_hdfc' => 'HDFC',
        'netbanking_kotak' => 'KKBK',
    ];

    protected $gatewayMethodName = [
        'netbanking_hdfc' => 'netbanking',
        'netbanking_kotak' => 'netbanking',
        'axis_migs' => 'card'
    ];

    protected $statusCakeToken;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayAbsenceTestData.php';

        parent::setUp();

        $statusCakeUserName = $this->app['config']->get('applications.gateway_downtime.statuscake.username');

        $statusCakeApiKey = $this->app['config']->get('applications.gateway_downtime.statuscake.api_key');

        $this->statusCakeToken = md5($statusCakeUserName . $statusCakeApiKey);

        $this->ba->appAuth();
    }

    //----- Create Tests -----

    public function testGatewayCreateAbsenceNetbanking()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayCreateAbsenceDuplicate()
    {
        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake',
                'downtime_from' => Carbon::now()->subMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);
        
        $request['content']['reason_code'] = 'ISSUER_DOWN';
        
        $response2 = $this->makeRequestAndGetContent($request);
        
        $this->assertEquals($response['id'], $response2['id']);
        
        $this->assertEquals($response['reason_code'], $response2['reason_code']);
    }

    public function testGatewayAbsenceDuplicateWithUpdatedScheduled()
    {
        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake',
                'downtime_from' => Carbon::now()->subMinutes(60)->timestamp,
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['scheduled'] = true;

        $downtimeTo = Carbon::now()->addMinutes(60)->timestamp;

        $request['content']['downtime_to'] = $downtimeTo;

        $request['content']['source'] = 'other';

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], $response2['id']);

        $this->assertEquals($response2['downtime_to'], $downtimeTo);

        $this->assertEquals($response2['scheduled'], true);

        $absenceEntity = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals($absenceEntity['source'], 'other');

    }

    public function testGatewayAbsenceDuplicateWithoutScheduled()
    {
        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'netbanking',
                'issuer' => 'HDFC',
                'comment' => 'Test Reason',
                'source' => 'statuscake',
                'scheduled' => true,
                'downtime_from' => Carbon::now()->subMinutes(60)->timestamp,
                'downtime_to'  => Carbon::now()->addMinutes(60)->timestamp
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['reason_code'] = 'ISSUER_DOWN';

        $request['content']['source'] = 'other';

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response['id'], $response2['id']);

        $this->assertEquals($response2['reason_code'], 'LOW_SUCCESS_RATE');

        $this->assertEquals($response2['scheduled'], true);

        $absenceEntity = $this->getLastEntity('gateway_downtime', true);

        $this->assertEquals($absenceEntity['source'], 'statuscake');

    }

    // tests with 2 inputs, one having minimal input while
    // the other having max input. We need to create 2 entries
    // for this
    public function testGatewayAbsenceDuplicateWithCreate()
    {
        $request = [
            'content' => [
                'downtime_from' => Carbon::now()->subMinutes(60)->timestamp,
                'gateway' => 'axis_migs',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'method' => 'card',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $request['content']['network'] = 'visa';

        $request['content']['card_type'] = 'debit';

        $request['content']['reason_code'] = 'OTHER';

        $request['content']['downtime_to']  = Carbon::now()->addMinutes(60)->timestamp;

        $response2 = $this->makeRequestAndGetContent($request);

        $this->assertEquals($response2['reason_code'], 'OTHER');

        $this->assertEquals($response2['network'], 'visa');

    }


    public function testGatewayCreateAbsenceNetbankingPartial()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateAbsenceNBEmptyIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateAbsenceInvalidGateway()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateAbsenceNBInvalidIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateAbsenceCardInvalidIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testCreateAbsenceNBNonSupportedIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayInvalidTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $from = $this->testData[__FUNCTION__]['request']['content']['downtime_from'];

        $this->testData[__FUNCTION__]['request']['content']['downtime_to'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateAbsenceInvalidSource()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $from = $this->testData[__FUNCTION__]['request']['content']['downtime_from'];

        $this->testData[__FUNCTION__]['request']['content']['downtime_to'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateAbsenceInvalidFrom()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        // more than end of time
        $from = 2147483648;

        $this->testData[__FUNCTION__]['request']['content']['downtime_from'] = $from;

        $this->testData[__FUNCTION__]['request']['content']['downtime_to'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateAbsenceInvalidTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        // more than end of time
        $to = 2147483648;

        $this->testData[__FUNCTION__]['request']['content']['downtime_to'] = $to;

        $this->startTest();
    }

    public function testGatewayCreateNullTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        unset($this->testData[__FUNCTION__]['request']['content']['downtime_to']);

        $this->startTest();
    }

    public function testGatewayInvalidReasonCode()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }


    public function testGatewayAbsenceForCard()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceForCardWithoutIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceForCardUnsupportedNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceForCardInvalidNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceForCardInvalidCardType()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceCardWithTypeIssuerNetwork()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceWithWallet()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceWithInvalidWallet()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceWithInvalidWalletIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();

    }


    public function testGatewayAbsenceWithTerminal()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal', ['used_count' => 2]);
        
        $tid = $terminal['id'];

        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'downtime_from'  => time(),
                'method' => 'netbanking',
                'terminal_id' => $tid,
                'issuer' => 'HDFC',
                'source' => 'other'
            ],
            'method' => 'POST',
            'url' => '/gateway/downtimes'
        ];

        $content = $this->makeRequestAndGetContent($request);
        
        $this->assertEquals($content['terminal_id'], $tid);
    }

    public function testGatewayAbsenceWithInvalidTerminal()
    {
        $tid = '6fNfsofiUqP1rs';

        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'downtime_from'  => time(),
                'method' => 'netbanking',
                'terminal_id' => $tid,
                'issuer' => 'HDFC',
                'source' => 'other'
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

    //----- Update Tests -----

    public function testGatewayAbsenceUpdate()
    {
        $content = $this->createGatewayAbsence();

        $url = '/gateway/downtimes/'. $content['id'];

        $now = Carbon::now()->timestamp;

        $to = Carbon::now()->addMinutes(100)->timestamp;

        $lastEntity = $this->getLastEntity('gateway_downtime', true);
        $request = [
            'content' => [
                'downtime_from' => $now,
                'downtime_to' => $to,
                'source' => $lastEntity['source'],
                'comment' => 'SOME_COMMENT'
            ],
            'method' => 'PUT',
            'url' => $url
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['downtime_from'], $now);

        $this->assertEquals($content['downtime_to'], $to);

        $this->assertEquals($content['comment'], 'SOME_COMMENT');
    }

    //----- Delete Tests -----

    public function testGatewayAbsenceDelete()
    {
        $content = $this->createGatewayAbsence();

        $url = '/gateway/downtimes/'. $content['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }


    //----- Fetch Tests -----

    public function testGatewayAbsenceFetchForNullTo()
    {
        $content1 = $this->createGatewayAbsence();

        $this->createGatewayAbsenceNullTo('netbanking_kotak');

        $from = $content1['downtime_from'];

        $request = [
            'content' => ['downtime_from' => $from],
            'url' => '/gateway/downtimes',
            'method' => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 2);

        $request['content']['gateway'] = 'netbanking_kotak';

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);

        $this->assertEquals($content['items'][0]['downtime_to'], null);
    }

    public function testGatewayAbsenceFetch()
    {
        $content1 = $this->createGatewayAbsence();

        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal', ['used_count' => 2]);

        $tid = $terminal['id'];

        $content2 = $this->createGatewayAbsence('netbanking_kotak', $tid);

        $from = $content1['downtime_from'];

        $to = $content2['downtime_to'];

        $request = [
            'content' => ['downtime_from' => $from, 'downtime_to' => $to],
            'url' => '/gateway/downtimes',
            'method' => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 2);

        $request['content']['gateway'] = 'netbanking_hdfc';

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);
    }

    public function testGatewayAbsenceFetchWithEmptyTo()
    {

        $content1 = $this->createGatewayAbsenceWithEmptyTo('netbanking_hdfc',
            Carbon::now()->subMinutes(60)->timestamp);

        $content2 = $this->createGatewayAbsenceWithEmptyTo('netbanking_kotak',
            Carbon::now()->addMinutes(60)->timestamp);

        $from = Carbon::now()->timestamp;

        $request = [
            'content' => ['downtime_from' => $from],
            'url' => '/gateway/downtimes',
            'method' => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);
    }

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
            'Tags' => 'NETBANKING_HDFC'
        ];

        $request = [
            'content' => $content,
            'url' => '/statuscake/callback',
            'method' => 'POST'
        ];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(isset($response['downtime_to']), false);

        $content['Status'] = 'Up';

        $request['content'] = $content;

        $response = $this->makeRequestAndGetContent($request);

        $this->assertEquals(isset($response['downtime_to']), true);
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

        $this->testData[$functionName]['request']['content']['downtime_from'] = $now;

        $this->testData[$functionName]['request']['content']['downtime_to'] = $to;
    }

    protected function createGatewayAbsence($gatewayName = 'netbanking_hdfc', $terminalId = null)
    {
        $from = Carbon::now()->subMinutes(60)->timestamp;

        $to = Carbon::now()->addMinutes(60)->timestamp;

        return $this->__createGatewayAbsence($gatewayName, $from, $to, $terminalId);
    }

    protected function createGatewayAbsenceNullTo($gatewayName = 'netbanking_hdfc')
    {
        $from = Carbon::now()->subMinutes(60)->timestamp;

        $to = null;

        return $this->__createGatewayAbsence($gatewayName, $from, $to, null);
    }

    protected function __createGatewayAbsence($gatewayName, $from, $to, $terminalId)
    {
        $bank = $this->gatewayBankMap[$gatewayName];

        $method = $this->gatewayMethodName[$gatewayName];

        $content = [
            'gateway' => $gatewayName,
            'reason_code'  => 'LOW_SUCCESS_RATE',
            'issuer'  => $bank,
            'downtime_from'  => $from,
            'downtime_to' => $to,
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
            unset($request['content']['downtime_to']);
        }

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }

    protected function createGatewayAbsenceWithEmptyTo($gatewayName, $from)
    {
        $bank = $this->gatewayBankMap[$gatewayName];

        $method = $this->gatewayMethodName[$gatewayName];

        $request = [
            'content' => [
                'gateway' => $gatewayName,
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'issuer'  => $bank,
                'downtime_from'  => $from,
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

    protected function createGatewayAbsenceWithCard($gatewayName, $from, $cardType, $network)
    {
        $method = $this->gatewayMethodName[$gatewayName];

        $request = [
            'content' => [
                'gateway' => $gatewayName,
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'downtime_from'  => $from,
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