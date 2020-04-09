<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\Base as BaseFixture;

class TerminalOnboardingTest extends TestCase
{
    use DbEntityFetchTrait;
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

    // Terminals Service call api to enable PayPal on activation of PayPal onboarding terminal
    public function testEnablePaypalMethodInternal()
    {
        $this->ba->appAuth();

        $this->startTest();

        $merchant = $this->getDbEntityById('merchant', '10000000000000');
        $this->assertTrue($merchant->methods->isPaypalEnabled());
    }

    public function testEnablePaypalMethodInternalWithWrongMerchantIdInInput()
    {
        $this->ba->appAuth();

        $this->testData[__FUNCTION__] = $this->testData['testEnablePaypalMethodInternal'];

        $this->testData[__FUNCTION__]['request']['url'] = '/merchants/1001230000000000/methods';
        
        $this->expectException(Exception\BadRequestException::class);

        $this->expectExceptionCode(
            ErrorCode::BAD_REQUEST_INVALID_ID);

        $this->expectExceptionMessage(
            'The id provided does not exist');

        $this->startTest();
    }
}
