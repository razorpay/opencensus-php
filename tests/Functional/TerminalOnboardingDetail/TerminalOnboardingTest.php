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

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/TerminalOnboardingTestData.php';

        parent::setUp();

        $this->terminalsServiceMock = $this->getTerminalsServiceMock();
    }


    public function testTerminalOnboardCallback()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $this->assertEquals(Requests::POST, $method);

            $this->assertEquals("v2/terminal/onboard/wallet_paypal/callback", $path);

            $this->assertEquals(['foo' => 'bar'], json_decode($content, true));

            $response = new \Requests_Response;

            $response->body = json_encode(['foo' => 'bar']);

            return $response;
        }, 1);

        $this->ba->directAuth();

        $this->startTest();
    }

    public function testTerminalOnboardCallbackTerminalsServiceError()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            $this->throwTerminalsServiceIntegrationException();
        },1);

        $this->ba->directAuth();

        $this->expectException(\Requests_Exception_Transport_cURL::class);

        $this->startTest();
    }
}
