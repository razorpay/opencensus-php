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

    }

    //----- helpers -----

    protected function fillDefaultsForTests($functionName)
    {
        $now = time();

        $to = $now + 100;

        $this->testData[$functionName]['request']['content']['from'] = $now;

        $this->testData[$functionName]['request']['content']['to'] = $to;

    }
}