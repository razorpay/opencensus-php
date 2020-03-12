<?php

namespace RZP\Tests\Functional\TerminalOnboarding;

use RZP\Http\Request\Requests;
use RZP\Tests\Functional\Helpers\Payment\PaymentTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\TerminalTrait;

class TerminalOnboardingTest extends TestCase
{
    use TerminalTrait;
    use PaymentTrait;

    protected $terminalsServiceMock;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalOnboardingTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }


    public function testTerminalOnboardCallback()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            $response = new \Requests_Response;

            $response->body = json_encode(['foo' => 'bar']);

            return $response;
        }, 1);

        $this->ba->directAuth();

        $this->startTest();
    }
}
