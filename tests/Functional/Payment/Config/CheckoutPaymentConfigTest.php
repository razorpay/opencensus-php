<?php

namespace Functional\Payment\Config;

use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class CheckoutPaymentConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CheckoutPaymentConfigTestData.php';

        parent::setUp();
    }

    public function testGetCheckoutConfigWithIdInternal(): void
    {
        $config = $this->fixtures->create('config', [
            'type' => 'checkout',
            'name' => 'Default Checkout',
            'is_default' => false,
            'config' => '{"restrictions": {"allow": [{"iins": ["400016"],"method": "card"}]}}'
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['url'] = '/internal/payment/config/checkout?config_id=config_' . $config['id'];

        $this->runRequestResponseFlow($testData);
    }

    public function testGetDefaultCheckoutConfigInternal(): void
    {
        $this->fixtures->create('config', [
            'type' => 'checkout',
            'name' => 'Default Checkout',
            'is_default' => true,
            'config' => '{"restrictions": {"allow": [{"iins": ["400016"],"method": "card"}]}}'
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $this->startTest();
    }

    public function testGetDefaultCheckoutConfigInternalWithNoDefaultConfig(): void
    {
        $this->fixtures->create('config', [
            'type' => 'checkout',
            'name' => 'Default Checkout',
            'is_default' => false,
            'config' => '{"restrictions": {"allow": [{"iins": ["400016"],"method": "card"}]}}'
        ]);

        $this->ba->checkoutServiceProxyAuth();

        $testData = $this->testData[__FUNCTION__];

        $response = $this->runRequestResponseFlow($testData);

        $this->assertArrayNotHasKey('restrictions', $response);
    }
}
