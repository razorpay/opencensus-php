<?php


namespace Functional\Payment\Config;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/ConfigTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testCreateCheckoutConfig()
    {
        return $this->startTest();
    }

    public function testCreateCheckoutConfigWithDefaultFalse()
    {
        $this->startTest();
    }

    public function testCreateCheckoutConfigWithoutConfig()
    {
        $this->startTest();
    }

    public function testCreateCheckoutConfigWithoutName()
    {
        $this->startTest();
    }

    public function testCreateCheckoutConfigWithConfigNotInJsonFormat()
    {
        $this->startTest();
    }

    public function testCreateCheckoutConfigWithExistingDefaultConfig()
    {
        $firstConfig = $this->fixtures->create('config');

        $secondConfig = $this->testCreateCheckoutConfig();

        $firstConfig->reload();

        $this->assertEquals(false, $firstConfig->is_default);

        $this->assertEquals(true, $secondConfig['is_default']);
    }

    public function testUpdateDefaultFieldForCheckoutConfig()
    {
        $config = $this->fixtures->create('config');

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $config->getPublicId();

        $this->startTest();

        $this->assertEquals($config->getPublicId(), $this->testData[__FUNCTION__]['response']['content']['id']);
    }

    public function testUpdateDefaultFieldForCheckoutConfigWithExistingDefaultConfig()
    {
        $firstConfig = $this->fixtures->create('config');

        $secondConfig= $this->fixtures->create('config', ['is_default' => '0']);

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->testData[__FUNCTION__]['request']['content']['id'] = $secondConfig->getPublicId();

        $this->startTest();

        $firstConfig->reload();

        $secondConfig->reload();

        $this->assertEquals(false, $firstConfig->is_default);

        $this->assertEquals(true, $secondConfig->is_default);
    }

    public function testUpdateConfigFieldForCheckoutConfig()
    {
        $config = $this->fixtures->create('config');

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();

        $this->startTest();
    }

    public function testCreateCheckoutConfigFromAdminAuth()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        return $this->startTest();
    }

    public function testCreateLocaleConfig()
    {
        return $this->startTest();
    }

    public function testCreateLocaleConfigWithExistingDefaultConfig()
    {
        $firstConfig = $this->fixtures->create('config', ['type' => 'locale']);

        $firstConfig->reload();

        $this->startTest();
    }

    public function testUpdateConfigFieldForLocaleConfig()
    {
        $config = $this->fixtures->create('config', ['type' => 'locale']);

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config->getPublicId();

        $this->testData[__FUNCTION__]['response']['content']['id'] = $config->getPublicId();

        $this->startTest();

        $this->assertEquals($config->getPublicId(), $this->testData[__FUNCTION__]['response']['content']['id']);
    }
}
