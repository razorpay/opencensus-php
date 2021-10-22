<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use App;
use Config;
use RZP\Trace\TraceCode;
use RZP\Tests\Functional\TestCase;
use RZP\Models\Merchant\Store\Core;
use RZP\Models\Merchant\Store\ConfigKey;
use RZP\Models\Merchant\Store\Constants;
use Rzp\Bvs\Probe\V1\CompanySearchResponse;
use Rzp\Bvs\Probe\V1\GetGstDetailsResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Models\Merchant\Store\Core as StoreCore;
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

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::SUCCESS);

        $this->mockRazorX(__FUNCTION__,
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();

       $keys=[
           ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT
       ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        Constants::INTERNAL);

        $this->assertNotNull($data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);

    }
    public function testGetGstDetailsSuccessFromStore()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        $this->mockRazorX(__FUNCTION__,
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $data = [
            Constants::NAMESPACE            => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GST_DETAILS_FROM_PAN => json_encode(["22AAACR5055K1ZH",
                                                            "03AAACR5055K2ZG"
                                                           ]),
        ];

        $data = (new StoreCore())->updateMerchantStore('BRRPK8070K', $data, Constants::INTERNAL);

        $data = [
            Constants::NAMESPACE                              => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => 1,
        ];

        $data = (new StoreCore())->updateMerchantStore($merchantDetail["merchant_id"], $data, Constants::INTERNAL);

        $this->startTest();

        $keys = [
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT
        ];
        $data = (new StoreCore())->fetchValuesFromStore($merchantDetail['merchant_id'],
                                                        ConfigKey::ONBOARDING_NAMESPACE,
                                                        $keys,
                                                        Constants::INTERNAL);

        $this->assertNotNull($data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);
        $this->assertEquals(2, $data[ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT]);

    }
    public function testGetGstDetailsFailure()
    {
        $merchantDetailsData = [
            'business_type'           => 1,
            'poi_verification_status' => 'verified',
            'promoter_pan'            => 'BRRPK8070K'
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::FAILURE);

        $this->mockRazorX(__FUNCTION__,
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

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);

        $this->mockRazorX(__FUNCTION__,
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $data = [
            Constants::NAMESPACE                              => ConfigKey::ONBOARDING_NAMESPACE,
            ConfigKey::GET_GST_DETAILS_FROM_BVS_ATTEMPT_COUNT => DetailConstants::GET_GST_DETAILS_MAX_ATTEMPT + 1
        ];

        (new Core())->updateMerchantStore($merchantDetail['merchant_id'], $data,Constants::INTERNAL);

        $this->startTest();
    }

    public function testGetGstDetailsInvalidBusinessType()
    {
        $merchantDetailsData = [
            'business_type' => 5,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);

        $this->mockRazorX(__FUNCTION__,
                          'bvs_get_gst_details',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();
    }

}
