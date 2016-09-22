<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;

class GatewayAbsenceTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/GatewayAbsenceTestData.php';

        parent::setUp();

        $this->ba->appAuth();
    }

    public function testGatewayCreateAbsence()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();

    }

    public function testCreateAbsenceWithBank()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();

    }

    public function testCreateAbsenceInvalidGateway()
    {
        $this->fillDefaultsForTests(__FUNCTION__);

        $this->startTest();
    }

    public function testGatewayAbsenceUpdate()
    {
        $content = $this->createGatewayAbsence();

        $url = '/gateway/absence/'. $content['id'];

        $now = time();

        $to = $now + 100;

        $request = array(
            'content' => array(
                'from' => $now,
                'to' => $to
            ),
            'method' => 'PUT',
            'url' => $url
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['from'], $now);

        $this->assertEquals($content['to'], $to);
    }

    public function testGatewayAbsenceDelete()
    {
        $content = $this->createGatewayAbsence();

        $url = '/gateway/absence/'. $content['id'];

        $this->testData[__FUNCTION__]['request']['url'] = $url;

        $this->startTest();
    }

    public function testGatewayAbsenceFetch()
    {
        $content1 = $this->createGatewayAbsence();

        $content2 = $this->createGatewayAbsence('netbanking_kotak');

        $from = $content1['from'];

        $to = $content2['to'];

        $request = array(
            'content' => array('from' => $from, 'to' => $to),
            'url' => '/gateway/absence',
            'method' => 'GET'
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['count'], 2);

        $request['content']['gateway'] = 'netbanking_hdfc';

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

    protected function createGatewayAbsence($gatewayName = 'netbanking_hdfc')
    {
        $now = time();

        $request = array(
            'content' => array(
                'gateway' => $gatewayName,
                'reason'  => 'Test Reason',
                'bank'  => 'hdfc',
                'from'  => $now,
                'to' => $now + 10
            ),
            'method' => 'POST',
            'url' => '/gateway/absence'
        );

        $content = $this->makeRequestAndGetContent($request);

        $this->assertEquals($content['gateway'], $gatewayName);

        return $content;
    }
}