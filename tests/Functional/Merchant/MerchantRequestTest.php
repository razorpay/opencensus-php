<?php

namespace RZP\Tests\Functional\Merchant;

use DB;
use Illuminate\Http\UploadedFile;

use RZP\Models\Feature;
use RZP\Models\Merchant\Request;
use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Fixtures\Entity\MerchantRequest as MerchantRequestFixture;

class MerchantRequestTest extends TestCase
{
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantRequestTestData.php';

        parent::setUp();

        $this->fixtures->merchant_request->setUp();

        // Need this to enable SuperAdmin to make changes to features.
        $this->fixtures->edit(AdminEntity::ADMIN, Org::SUPER_ADMIN, [AdminEntity::ALLOW_ALL_MERCHANTS => 1]);
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

        // Assume the fixture's merchant_request is in needs_clarification status.
        // Then moving it to activated status is wrong.
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

        $request['content'][Request\Entity::SUBMISSIONS][Feature\Constants::VENDOR_AGREEMENT] = $uploadedFile;

        $response = $this->makeRequestAndGetContent($request);

        $fileStoreData = $this->getDbLastEntityPublic('file_store');

        $this->assertArraySelectiveEquals($this->testData[__FUNCTION__]['response']['content'], $response);

        $this->assertEquals($fileStoreData['id'],
                            'file_'.$response[Request\Entity::SUBMISSIONS][Feature\Constants::VENDOR_AGREEMENT]);
    }

    public function testBulkUpdateMerchantRequests()
    {
        $this->ba->adminAuth();

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

        $merchantRequest = $this->fixtures->create('merchant_request', [
            'merchant_id' => MerchantRequestFixture::DEFAULT_MERCHANT_ID,
            'name'        => 'marketplace',
            'status'      => Request\Status::UNDER_REVIEW,
            'type'        => Request\Type::PRODUCT,
        ]);

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

    /**
     * @param string $file
     *
     * @return UploadedFile
     */
    protected function createUploadedFile(string $file): UploadedFile
    {
        $this->assertFileExists($file);

        $mimeType = 'application/pdf';
        $uploadedFile = new UploadedFile(
            $file,
            $file,
            $mimeType,
            filesize($file),
            null,
            true
        );

        return $uploadedFile;
    }

}
