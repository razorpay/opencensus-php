<?php

namespace Tests\Functional\Gateway\Cybersource;

use EE\Exception;
use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use Tests\Functional\Helpers\Payment\PaymentTrait;
use Gateway\Cybersource;
use Tests\Functional\TestCase;

class CybersourceGatewayTest extends TestCase
{
    use PaymentTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/CybersourceGatewayTestData.php';

        parent::setUp();

        $this->sharedTerminal = $this->fixtures->create('terminal:shared_cybersource_terminal');

        $this->fixtures->create('terminal:disable_default_hdfc_terminal');

        $this->gateway = 'cybersource';

        $this->setMockGatewayTrue();

        $this->mockTokenex();
    }

}
