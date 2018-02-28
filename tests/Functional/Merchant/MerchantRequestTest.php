<?php

namespace RZP\Tests\Functional\Merchant;

use DB;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Admin\Admin\Entity as AdminEntity;
use RZP\Models\Merchant\Request\Entity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Fixtures\Entity\MerchantRequest;

class MerchantRequestTest extends TestCase
{
    use RequestResponseFlowTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__.'/helpers/MerchantRequestTestData.php';

        parent::setUp();

        $this->fixtures->merchant_request->setUp();

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
            MerchantRequest::DEFAULT_MERCHANT_REQUEST_ID,
            [Entity::STATUS => 'needs_clarification']);

        $this->setDefaultMerchantRequestIdInUrl();

        $this->startTest();
    }

    private function setDefaultMerchantRequestIdInUrl()
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

        $functionName = $trace[1]['function'];

        $defaultId = 'm_req_' . MerchantRequest::DEFAULT_MERCHANT_REQUEST_ID;

        $url = $this->testData[$functionName]['request']['url'];

        $url = sprintf($url, $defaultId);

        // Assign url
        $this->testData[$functionName]['request']['url'] = $url;
    }
}
