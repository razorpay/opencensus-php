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

            $response = new \WpOrg\Requests\Response;

            $response->body = json_encode(['foo' => 'bar']);

            return $response;
        }, 1);

        $this->ba->directAuth();

        $this->startTest();
    }

    public function testTerminalOnboardCallbackAuthorizationErrorWithoutHeaders()
    {
        $this->ba->directAuth();

        $this->changeEnvToNonTest();

        $this->startTest();
    }

    public function testTerminalOnboardCallbackAuthorizationErrorWithHeaders()
    {
        $this->ba->directAuth();

        $this->changeEnvToNonTest();

        $this->startTest();
    }

    public function testTerminalOnboardCallbackTerminalsServiceError()
    {
        $this->mockTerminalsServiceSendRequest(function () {
            $this->throwTerminalsServiceIntegrationException();
        },1);

        $this->ba->directAuth();

        $this->expectException(\WpOrg\Requests\Exception\Transport\Curl::class);

        $this->startTest();
    }

    public function testProxyAuthForGetOptimizerGateways()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $this->assertEquals(Requests::GET, $method);

            $this->assertEquals('v2/optimizer/supported_gateways', $path);

            $response = new \WpOrg\Requests\Response;

            $response->body = json_encode([
                'data' => [
                    'payu' => [
                        'Key' => [
                            'data_type'  => 'string',
                            'data_value' => 'payu key',
                            'min_length' => 6,
                        ],
                        'Salt' => [
                            'data_type'  => 'string',
                            'data_value' => 'payu salt',
                            'min_length' => 8,
                        ],
                    ],
                ],
            ]);

            return $response;
        }, 1);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthForAddingOptimizerProvider()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $this->assertEquals(Requests::POST, $method);

            $this->assertEquals('v2/optimizer/10000000000000/provider', $path);

            $response = new \WpOrg\Requests\Response;

            $response->body = json_encode([
                'data' => [
                    'terminal' => [
                        'id' => 'HWo8Z0G0c0az74',
                    ],
                ],
            ]);

            return $response;
        }, 1);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthForEditingOptimizerProvider()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $this->assertEquals(Requests::PUT, $method);

            $this->assertEquals('v2/optimizer/10000000000000/provider', $path);

            $response = new \WpOrg\Requests\Response;

            $response->body = json_encode([
                'data' => [
                    'terminal' => [
                        'id' => 'HWo8Z0G0c0az74',
                    ],
                ],
            ]);

            return $response;
        }, 1);

        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testProxyAuthForListOptimizerMerchantProviders()
    {
        $this->mockTerminalsServiceSendRequest(function ($path, $content, $method) {

            $this->assertEquals(Requests::GET, $method);

            $this->assertEquals('v2/optimizer/list/10000000000000/provider', $path);

            $response = new \WpOrg\Requests\Response;

            $response->body = json_encode([
                'data' => [
                    [
                        'Provider_name' => 'PayU',
                        'Description'   => 'Cards and UPI',
                        'Gateway'       => 'payu',
                    ],
                ],
            ]);

            return $response;
        }, 1);

        $this->ba->proxyAuth();

        $this->startTest();
    }
}
