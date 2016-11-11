<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

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

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayAbsenceTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    //----- Create Tests -----

    public function testGatewayCreateAbsenceNetbanking()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
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

    public function testCreateAbsenceNBNonSupportedIssuer()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayInvalidTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $from = $this->testData[__FUNCTION__]['request']['content']['from'];

        $this->testData[__FUNCTION__]['request']['content']['to'] = $from - 10;

        $this->startTest();
    }

    public function testGatewayCreateNullTo()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        unset($this->testData[__FUNCTION__]['request']['content']['to']);

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


    public function testGatewayAbsenceWithTerminal()
    {
        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal', ['used_count' => 2]);
        
        $tid = $terminal['id'];

        $request = [
            'content' => [
                'gateway' => 'netbanking_hdfc',
                'reason_code'  => 'LOW_SUCCESS_RATE',
                'from'  => time(),
                'method' => 'netbanking',
                'terminal_id' => $tid,
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
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
                'from'  => time(),
                'method' => 'netbanking',
                'terminal_id' => $tid,
                'issuer' => 'HDFC'
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
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

        $url = '/gateway/absence/'. $content['id'];

        $now = time();

        $to = $now + 100;

        $request = [
            'content' => [
                'from' => $now,
                'to' => $to
            ],
            'method' => 'PUT',
            'url' => $url
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['from'], $now);

        $this->assertEquals($content['to'], $to);
    }

    //----- Delete Tests -----

    public function testGatewayAbsenceDelete()
    {
        $content = $this->createGatewayAbsence();

        $url = '/gateway/absence/'. $content['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }


    //----- Fetch Tests -----

    public function testGatewayAbsenceFetchForNullTo()
    {
        $content1 = $this->createGatewayAbsence();

        $this->createGatewayAbsenceNullTo('netbanking_kotak');

        $from = $content1['from'];

        $request = [
            'content' => ['from' => $from],
            'url' => '/gateway/absence',
            'method' => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 2);

        $request['content']['gateway'] = 'netbanking_kotak';

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);

        $this->assertEquals($content['items'][0]['to'], null);
    }

    public function testGatewayAbsenceFetch()
    {
        $content1 = $this->createGatewayAbsence();

        $terminal = $this->fixtures->create('terminal:netbanking_hdfc_terminal', ['used_count' => 2]);

        $tid = $terminal['id'];

        $content2 = $this->createGatewayAbsence('netbanking_kotak', $tid);

        $from = $content1['from'];

        $to = $content2['to'];

        $request = [
            'content' => ['from' => $from, 'to' => $to],
            'url' => '/gateway/absence',
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
        $content1 = $this->createGatewayAbsenceWithEmptyTo('netbanking_hdfc', strtotime('-1 hour'));

        $content2 = $this->createGatewayAbsenceWithEmptyTo('netbanking_kotak', strtotime('+1 hour'));

        $from = time();

        $request = [
            'content' => ['from' => $from],
            'url' => '/gateway/absence',
            'method' => 'GET'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 1);
    }



    //----- helpers -----

    protected function fillDefaultsForTests($functionName)
    {
        $now = time();

        $to = $now + 100;

        $this->testData[$functionName]['request']['content']['from'] = $now;

        $this->testData[$functionName]['request']['content']['to'] = $to;
    }

    protected function createGatewayAbsence($gatewayName = 'netbanking_hdfc', $terminalId = null)
    {
        $from = strtotime('-1 hour');

        $to = strtotime('+1 hour');

        return $this->__createGatewayAbsence($gatewayName, $from, $to, $terminalId);
    }

    protected function createGatewayAbsenceNullTo($gatewayName = 'netbanking_hdfc')
    {
        $from = strtotime('-1 hour');

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
            'from'  => $from,
            'to' => $to,
            'method' => $method
        ];

        if (empty($terminalId) === false)
        {
            $content['terminal_id'] = $terminalId;
        }

        $request = [
            'content' => $content,
            'method' => 'POST',
            'url' => '/gateway/absence'
        ];

        if ($to === null)
        {
            unset($request['content']['to']);
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
                'from'  => $from,
                'method' => $method,
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
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
                'from'  => $from,
                'method' => $method,
                'card_type' => $cardType,
                'network' => $network
            ],
            'method' => 'POST',
            'url' => '/gateway/absence'
        ];

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }
}