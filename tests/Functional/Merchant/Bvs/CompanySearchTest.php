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

class CompanySearchTest extends TestCase
{

    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
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
        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::SUCCESS);


        $this->mockRazorX('testCompanySearchSuccess',
                          'bvs_company_search',
                          'on',
                          $merchantDetail["merchant_id"]);


        $this->startTest();
    }

    public function testCompanySearchFailure()
    {
        //$this->ba->proxyAuth();

        $merchantDetail = $this->fixtures->create('merchant_detail');

        $this->ba->proxyAuth('rzp_test_' . $merchantDetail['merchant_id']);

        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', Constant::FAILURE);

        $this->mockRazorX('testCompanySearchFailure',
                          'bvs_company_search',
                          'on',
                          $merchantDetail["merchant_id"]);

        $this->startTest();
    }


}
