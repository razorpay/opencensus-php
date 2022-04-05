<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use App;
use Config;

use RZP\Tests\Functional\TestCase;
use Rzp\Bvs\Probe\V1\CompanySearchResponse;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class CompanySearchTest extends TestCase
{

    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/CompanySearchTestData.php';

        parent::setUp();
    }

    public function validateSuccessBvsResponse(CompanySearchResponse $bvsResponse)
    {
        $this->assertNotNull($bvsResponse->getResults());
        $this->assertNull($bvsResponse->getErrorCode());
        $this->assertNull($bvsResponse->getErrorDescription());
    }

    public function testCompanySearchSuccess()
    {
        $merchantDetailsData = [
            'business_type' => 6,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' .$merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::SUCCESS);

        $this->startTest();
    }

    public function testCompanySearchFailure()
    {
        $merchantDetailsData = [
            'business_type' => 4,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::FAILURE);

        $this->startTest();
    }

    public function testCompanySearchRateLimitExhausted()
    {
        $merchantDetailsData = [
            'business_type' => 5,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $this->app['cache']->put(DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_REDIS_KEY_PREFIX .
                                 $merchantDetail['merchant_id'],
                                 DetailConstants::COMPANY_SEARCH_MAX_ATTEMPT + 1,
                                 DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_TTL_IN_SEC);

        $this->startTest();
    }

    public function testCompanySearchInvalidBusinessType()
    {
        $merchantDetailsData = [
            'business_type' => 1,
        ];

        $merchantDetail = $this->fixtures->create('merchant_detail', $merchantDetailsData);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetail['merchant_id']);

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id'], $merchantUser['id']);

        Config::set('services.bvs.mock', true);

        $this->app['cache']->put(DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_REDIS_KEY_PREFIX .
                                 $merchantDetail['merchant_id'],
                                 DetailConstants::COMPANY_SEARCH_MAX_ATTEMPT + 1,
                                 DetailConstants::COMPANY_SEARCH_ATTEMPT_COUNT_TTL_IN_SEC);

        $this->startTest();
    }

}
