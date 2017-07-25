<?php

namespace RZP\Tests\Functional\Risk;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;

class RiskTest extends TestCase
{
    use PaymentTrait;
    use HeimdallTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/RiskTestData.php';

        parent::setUp();

        $this->ba->publicAuth();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_sharp_terminal');
    }

    public function testCreateRiskEntry()
    {

    }
}

