<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Models\Feature;
use RZP\Models\Merchant\Request;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\FileUploadTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Fixtures\Entity\MerchantRequest as MerchantRequestFixture;

class MerchantRequestTest extends TestCase
{
    use FileUploadTrait;
    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantRequestTestData.php';

        parent::setUp();

        $this->fixtures->merchant_request->setUp();
    }

    public function testGetMerchantRequestDetails()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testGetMerchantRequestStatusLog()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testChangeMerchantRequestStatusToNeedsClarification()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testChangeMerchantRequestStatusToRejectedWithRejectionReasons()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testChangeMerchantRequestStatusWithException()
    {
        $this->ba->adminAuth();

        //
        // Assume the fixture's merchant_request is in needs_clarification status.
        // Then moving it to activated status is wrong.
        //
        $this->fixtures->edit(
            'merchant_request',
            MerchantRequestFixture::DEFAULT_MERCHANT_REQUEST_ID,
            [Request\Entity::STATUS => 'needs_clarification']);

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testCreateMerchantRequest()
    {
        $this->ba->proxyAuth();

        $url = storage_path(
            "files/" . Feature\Constants::ONBOARDING .  "/" . Feature\Constants::VENDOR_AGREEMENT . ".pdf");

        $uploadedFile = $this->createUploadedFile($url);

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $request['content'][Request\Constants::SUBMISSIONS][Feature\Constants::VENDOR_AGREEMENT] = $uploadedFile;

        $response = $this->makeRequestAndGetContent($request);

        $fileStoreData = $this->getDbLastEntityPublic('file_store');

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['response']['content'], $response);

        $this->assertEquals($fileStoreData['location'],
                            $response[Request\Constants::SUBMISSIONS][Feature\Constants::VENDOR_AGREEMENT]);
    }

    public function testCreateMerchantRequestForQrCodeActivation()
    {
        $this->fixtures->merchant->activate('10000000000000');

        $user = $this->fixtures->user->createUserForMerchant('10000000000000', [], 'owner', 'live');

        $this->ba->proxyAuth('rzp_live_10000000000000', $user->getId());

        $testData = $this->testData[__FUNCTION__];

        $request = $testData['request'];

        $response = $this->makeRequestAndGetContent($request);

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['response']['content'], $response);
    }

    public function testCreateMerchantRequestWithErrors()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testUpdateMerchantRequest()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testUpdateMerchantRequestWithSubmissions()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testUpdateMerchantRequestWithErrors()
    {
        $this->ba->adminAuth();

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    public function testBulkUpdateMerchantRequests()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    protected function bulkUpdateMerchantRequestsTimestampsOnce()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testBulkUpdateMerchantRequestsTimestamps()
    {
        $this->ba->adminAuth();

        $this->bulkUpdateMerchantRequestsTimestampsOnce();

        // Update timestamp again
        $this->startTest();
    }

    public function testBulkUpdateMerchantRequestsWithErrors()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFetchMerchantRequests()
    {
        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testGetForFeatureTypeAndName()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    public function testGetForFeatureTypeAndNameWhichDoesNotExist()
    {
        $this->ba->proxyAuth();

        $this->startTest();
    }

    private function setDefaultMerchantRequestIdInUrl()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $functionName = $trace[1]['function'];

        $defaultId = 'm_req_' . MerchantRequestFixture::DEFAULT_MERCHANT_REQUEST_ID;

        $url = $this->testData[$functionName]['request']['url'];

        $url = sprintf($url, $defaultId);

        // Assign url
        $this->testData[$functionName]['request']['url'] = $url;
    }
}
