<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use App;
use Config;

use RZP\Tests\Functional\TestCase;
use Rzp\Bvs\Probe\V1\CompanySearchResponse;
use Rzp\Bvs\Probe\V1\GetGstDetailsResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class GetGstDetailsTest extends TestCase
{

    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/GetGstDetailsTestData.php';

        parent::setUp();
    }

    public function testGetGstDetailsSuccess()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::SUCCESS);

        $this->mockRazorX('testGetGstDetailsSuccess',
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();
    }

    public function testGetGstDetailsFailure()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::FAILURE);

        $this->mockRazorX('testGetGstDetailsFailure',
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();
    }

    public function testGetGstDetailsRateLimitExhausted()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $this->mockRazorX('testGetGstDetailsRateLimitExhausted',
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->app['cache']->put(DetailConstants::GET_GST_DETAILS_ATTEMPT_COUNT_REDIS_KEY_PREFIX .
                                 $merchantDetail['merchant_id'],
                                 DetailConstants::GET_GST_DETAILS_MAX_ATTEMPT + 1,
                                 DetailConstants::GET_GST_DETAILS_ATTEMPT_COUNT_TTL_IN_MIN);

        $this->startTest();
    }

    public function testGetGstDetailsInvalidBusinessType()
    {
        $merchantDetailsData = [
            'business_type' => 5,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $this->mockRazorX('testGetGstDetailsInvalidBusinessType',
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();
    }

}
