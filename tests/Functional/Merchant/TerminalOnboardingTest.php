<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Exception;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;

class TerminalOnboardingTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/helpers/TerminalOnboardingTestData.php';

        parent::setUp();
    }

    public function testInitiateOnboarding()
    {
        $this->ba->proxyAuth();

        (new BaseFixture)->createEntity('merchant_detail', [
            'merchant_id' => '10000000000000',
            'submitted'   => true,
            'business_registered_state' => 'KA',
        ]);
        
        $response = $this->startTest();

        $this->assertArrayHasKey('links', $response);
    }

    public function testInitiateOnboardingWithNoGatewayInInput()
    {
        $this->ba->proxyAuth();
        
        $this->expectException(Exception\BadRequestValidationFailureException::class);

        $this->expectExceptionMessage(
            'The gateway field is required.');

        $this->startTest();
    }
}
