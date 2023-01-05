<?php


namespace Functional\Payment\Config;


use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;

class ConfigTest extends TestCase
{
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/ConfigTestData.php';

        parent::setUp();

        $this->ba->proxyAuth();
    }

    public function testFetchDccConfig()
    {
        $this->testCreateConfigFieldForDccConfig();

        return $this->startTest();
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

    public function testCreateConfigFieldForDccConfig()
    {
        return $this->startTest();
    }

    public function testErrorCreateConfigFieldForDccConfig()
    {
        $this->testCreateConfigFieldForDccConfig();

        $this->startTest();
    }

    public function testCreateWrongConfigForDccConfig()
    {
        $this->startTest();
    }

    public function testCreateDecimalValueForDccConfig()
    {
        $this->startTest();
    }

    public function testCreateDecimalPrecisionMoreThan2DccConfig()
    {
        $this->startTest();
    }

    public function testCreateBlankValueForDccConfig()
    {
        $this->startTest();
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

    public function testCreateLateAuthConfig()
    {
        return $this->startTest();
    }

    public function testUpdateConfigFieldForLateAuthConfig()
    {
        $config = $this->fixtures->create('config', ['type' => 'late_auth']);

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->startTest();
   }

    public function testUpdateConfigFieldForMultipleLateAuthConfig()
    {
        $config1 = $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => false]);
        $config2 = $this->fixtures->create('config', ['type' => 'late_auth', 'is_default' => true]);

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

    public function testUpdateConfigFieldForLateAuthConfigBulk()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $config = $this->fixtures->create('config', ['type' => 'late_auth']);

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->testData[__FUNCTION__]['request']['url'] = '/admin/lateauth/config/bulk';
        $this->startTest();
    }

    public function testUpdateForDccConfig()
    {
        $config = $this->testCreateConfigFieldForDccConfig();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config';

        $this->testData[__FUNCTION__]['request']['content']['id'] = $config['id'];

        $this->startTest();
    }

    public function testCreateCheckoutConfigBulk()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $config = $this->fixtures->create('config', ['type' => 'late_auth']);

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config/bulk';

        $this->startTest();
    }

    public function testCreateRiskConfigBulk()
    {
        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->testData[__FUNCTION__]['request']['url'] = '/payment/config/bulk';

        $this->startTest();
    }

    public function testDeleteLocaleConfig()
    {
        $config = $this->fixtures->create('config');

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();
    }

    public function testDeleteDccConfig()
    {
        $this->testCreateConfigFieldForDccConfig();

        $this->ba->adminAuth();

        $admin = $this->ba->getAdmin();

        $this->fixtures->admin->edit($admin["id"], ['allow_all_merchants' => true]);

        $this->startTest();
    }

    public function testConfigInternalById()
    {
        $this->ba->pgRouterAuth();

        $config = $this->fixtures->create('config');

        $this->testData[__FUNCTION__]['request']['url'] .= $config->getId();
        $this->testData[__FUNCTION__]['response']['content']['id'] = $config->getPublicId();

        $this->startTest();
        $this->assertEquals($config->getPublicId(), $this->testData[__FUNCTION__]['response']['content']['id']);
    }

    public function testConfigInternalList()
    {
        $this->ba->pgRouterAuth();

        $this->fixtures->create('config', ['type' => 'late_auth']);

        $this->fixtures->create('config', ['type' => 'locale']);

        $this->startTest();
    }

    public function testConfigInternalListByIsDefault()
    {
        $this->ba->pgRouterAuth();

        $this->fixtures->create('config', ['type' => 'checkout', 'name' => 'Default Checkout', 'is_default' => true]);
        $this->fixtures->create('config', ['type' => 'checkout', 'name' => 'Checkout', 'is_default' => false]);

        $this->startTest();
    }

    public function testCreateConvenienceFeeConfigWithEmptyRules()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithNullRules()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForUPIWithFlatValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForNetbanking()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForUPIWithPercentageValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForCardWithFlatValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForCardWithPercentageValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForNonRepeatingCardTypes()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithExtraFieldProvided()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForRepeatingCardTypes()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigforPercentageFeeInFloat()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForPercentageFeeInvalidValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForInvalidMethodName()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForFlatValueLessThanZero()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithExtraFieldProvidedInRules()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithRequiredFieldNotProvidedInRules()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithRepeatingWalletConfig()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithRepeatingCardConfig()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForPercentageFeeLessThanZero()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigForPercentageGreaterThanMaxValue()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithInvalidFeePayee()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithRepeatingCardTypeConfig()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithInvalidCardType()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigWithMethodNotSent()
    {

        $this->startTest();

    }

    public function testCreateConvenienceFeeConfigInvalidLabelLength()
    {

        $this->startTest();

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
