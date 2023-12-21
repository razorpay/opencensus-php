<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use Config;
use Functional\Helpers\BvsTrait;
use RZP\Models\Merchant\Detail\Constants;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\TestCase;

class BvsCredenceCheckTest extends TestCase
{
    use BvsTrait;
    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BvsCredenceCheckTestData.php';

        parent::setUp();
    }

    /*
    public function testCreateVKYCAdminForMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail',[
            'promoter_pan_name' => 'Razorpay Test Merchant'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $merchantId = $merchantDetail['merchant_id'];

        Config::set('services.bvs.mock', true);

        $bvsMock = $this->mockCreateCredenceCheck($merchantDetail['merchant_id'], 'initiated', Constants::VKYC);

        $bvsMock->expects($this->once())->method('createCredenceCheck')->withAnyParameters();
        $bvsMock->expects($this->once())->method('getCredenceCheckDetailsByAccountID')->withAnyParameters();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/vkyc";

        $this->ba->adminAuth();

        $this->startTest();
    }

    public function testFailCreateVKYCAdminForMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $merchantId = $merchantDetail['merchant_id'];

        Config::set('services.bvs.mock', true);

        $bvsMock = $this->mockCreateCredenceCheck($merchantDetail['merchant_id'], 'initiated', Constants::VKYC);

        $bvsMock->expects($this->once())->method('createCredenceCheck')->withAnyParameters();
        $bvsMock->expects($this->once())->method('getCredenceCheckDetailsByAccountID')->withAnyParameters();

        $testData = &$this->testData['testCreateVKYCAdminForMerchant'];

        $testData['request']['url'] = "/merchant/$merchantId/vkyc";
        $request = $testData['request'];

        $this->ba->adminAuth();

        $this->makeRequestAndCatchException(
            function() use ($request)
            {
                $this->sendRequest($request);
            },
            \RZP\Exception\BadRequestException::class,
            "Promoter Pan Name is missing");
    }

    public function testGetVKYCAdminForMerchant()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $merchantId = $merchantDetail['merchant_id'];

        Config::set('services.bvs.mock', true);

        $bvsMock = $this->mockCreateCredenceCheck($merchantDetail['merchant_id'], 'initiated', Constants::VKYC);

        $bvsMock->expects($this->once())->method('getCredenceCheckDetailsByAccountID')->withAnyParameters();

        $testData = &$this->testData[__FUNCTION__];

        $testData['request']['url'] = "/merchant/$merchantId/vkyc";

        $this->ba->adminAuth();

        $this->startTest();
    }
    */

}
