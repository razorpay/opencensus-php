<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Models\Batch\Header;
use Config;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Feature\Constants as Feature;
use RZP\Models\Admin\Permission\Name as PName;
use RZP\Tests\P2p\Service\Base\Traits\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail;
use RZP\Tests\Functional\Helpers\Heimdall\HeimdallTrait;
use RZP\Models\Admin\Org;

class MerchantUpdateMiqBatchTest extends TestCase
{
    use BatchTestTrait;
    use DbEntityFetchTrait;
    use HeimdallTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/MerchantUpdateMiqBatchTestData.php';

        parent::setUp();

        $this->ba->adminAuth();
    }

    public function testCreateMerchantUploadMIQForUpdate()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => "100000razorpay",
            'entity_type'   => 'org',
        ]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];

        $response = $this->startTest();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

        return ($response[Header::MIQ_OUT_MERCHANT_ID]);
    }

    public function testUpdateBatchPricingMIQNASuccess()
    {
        $this->markTestSkipped('Skipping this until pricing issue is fixed');
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }

    public function testUpdateBatchPricingMIQUPISuccess()
    {
        $this->markTestSkipped('Skipping this until pricing issue is fixed');
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }

    public function testUpdateBatchPricingMIQWalletSuccess()
    {
        $this->markTestSkipped('Skipping this until pricing issue is fixed');
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }

    public function testUpdateBatchPricingMIQNBSuccess()
    {
        $this->markTestSkipped('Skipping this until pricing issue is fixed');
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }

    public function testUpdateBatchPricingMIQCardsSuccess()
    {
        $this->markTestSkipped('Skipping this until pricing issue is fixed');

        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }


    public function testUpdateBatchPricingMIQOnlyFeeBearerSuccess()
    {
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }


    public function testUpdateBatchPricingMIQSuccess()
    {
        Config::set('pgos.proxy.request.mock', true);

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->batchAppAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdatePricingEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_ID]);
    }


    public function testCreateBatchMerchantUpdateMIQSuccess()
    {

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_NAME_BUSINESS_NAME]);
    }

    public function testCreateBatchMerchantUpdateMIQSuccessNAValues()
    {

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_NAME_BUSINESS_NAME]);
    }

    public function testCreateBatchMerchantUpdateMIQSuccessNAWebsiteDetails()
    {

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_NAME_BUSINESS_NAME]);
    }

    public function testCreateBatchMerchantUpdateMIQSuccessAllNAWebsite()
    {

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_NAME_BUSINESS_NAME]);
    }

    public function testCreateBatchMerchantUpdateMIQNAWebsite()
    {

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($response[Header::MIQ_MERCHANT_NAME_BUSINESS_NAME]);
    }


    public function testCreateBatchMerchantUpdateMIQFailed()
    {
        $this->expectException('RZP\Exception\BadRequestValidationFailureException');

        $this->expectExceptionMessage('Invalid Business Category');

        $resp = $this->testCreateMerchantUploadMIQForUpdate();

        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];

        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

    }

    public function testCreateBatchMerchantUploadMIQforAxis()
    {
        $this->ba->appAuth();

        $this->fixtures->create('feature', [
            'name'          => Feature::SKIP_KYC_VERIFICATION,
            'entity_id'     => Org\Entity::AXIS_ORG_ID,
            'entity_type'   => 'org',
        ]);

        $org = $this->fixtures->create('org', [
            'id' => Org\Entity::AXIS_ORG_ID
        ]);

        $this->addAssignablePermissionsToOrg($org);

        $this->fixtures->pricing->createPricingPlanForDifferentOrg($org->getId());
        $org1 = (new Org\Service())->edit('org_' . Org\Entity::AXIS_ORG_ID, ['default_pricing_plan_id' => '1hDYlICxbxOCYx', 'merchant_session_timeout_in_seconds' => 600,]);

        $this->testData[__FUNCTION__] = $this->testData['defaultSuccess'];
        $this->testData[__FUNCTION__]['request']['content'][Header::ORG_ID] = 'org_' . Org\Entity::AXIS_ORG_ID;

        $response = $this->startTest();

        $this->assertNotEmpty($response[Header::MIQ_OUT_MERCHANT_ID]);

        return ($response[Header::MIQ_OUT_MERCHANT_ID]);
    }

    public function testCreateBatchMerchantUpdateMiqAdditionalFieldsSuccess()
    {
        $resp = $this->testCreateBatchMerchantUploadMIQforAxis();
        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $merchantDetails = (new Detail\Repository())->getByMerchantId($response[Header::MIQ_MERCHANT_ID]);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertEquals('success', $response[Header::STATUS]);

        $this->assertEmpty($response[Header::ERROR_CODE]);

        $this->assertEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertNotEmpty($businessDetailMetadata['org_defined_merchant_fields']);
    }

    public function testCreateBatchMerchantUpdateMiqAdditionalFieldsFailure()
    {
        $resp = $this->testCreateBatchMerchantUploadMIQforAxis();
        $this->ba->appAuth();

        $testData = $this->testData[__FUNCTION__];
        $testData['request']['content'][Header::MIQ_MERCHANT_ID] = $resp;
        $testData['response']['content'][Header::MIQ_MERCHANT_ID] = $resp;

        $input = $testData['request']['content'];
        $response = (new Detail\Upload\Core)->processUpdateMerchantEntry($input);

        $merchantDetails = (new Detail\Repository())->getByMerchantId($response[Header::MIQ_MERCHANT_ID]);
        $businessDetailMetadata = $merchantDetails->businessDetail->getMetadata();

        $this->assertNotEmpty($response[Header::ERROR_CODE]);

        $this->assertNotEmpty($response[Header::ERROR_DESCRIPTION]);

        $this->assertEmpty($businessDetailMetadata['org_defined_merchant_fields'][0]['value']);
    }
}
