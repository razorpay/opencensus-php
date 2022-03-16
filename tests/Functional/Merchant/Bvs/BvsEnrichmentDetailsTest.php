<?php

namespace Functional\Merchant\Bvs;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BvsEnrichmentDetailsTest extends TestCase
{

    use DbEntityFetchTrait;
    use RequestResponseFlowTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BvsEnrichmentDetailsTestData.php';

        parent::setUp();
    }
    /**In case this test ever fails because of data mismatch update below mock data with queries on api test db:
     * for validation data
     * select validation_id,artefact_type,validation_status,created_at from bvs_validation
     * where artefact_type='gstin' order by created_at desc limit 1 ;
     * select validation_id,artefact_type,validation_status,created_at from bvs_validation
     * where artefact_type='llp_deed' order by created_at desc limit 1 ;
     *
     * and then update File BvsEnrichmentDetailsTestData.php with the correct api response
    **/
    public function testFetchEnrichmentDetails()
    {
        //mock the bvs service
        $this-> app['config']['services.bvs.mock'] = true;

        $requestResponseData = $this->testData[__FUNCTION__];

        $merchantDetailsData = [
            'merchant_id' => '10000000000000',
            'business_type' => 6,
            'company_cin' => 'AAA-0001',
            'gstin' => '09AAACR5055K1Z5',
        ];

        $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailsData);

        $bvsLlpValidationData = [
            'validation_id' => 'IuZndF1UeIEHOP',
            'artefact_type' => 'llp_deed',
            'owner_id' => '10000000000000',
            'validation_status' => 'success'
        ];

        $this->fixtures->create('bvs_validation', $bvsLlpValidationData);

        $bvsgstinValidationData = [
            'validation_id' => 'Ity9sdi70CJOJI',
            'artefact_type' => 'gstin',
            'owner_id' => '10000000000000',
            'validation_status' => 'failed'
        ];
        $this->fixtures->create('bvs_validation', $bvsgstinValidationData);

        $responseData = $requestResponseData['response']['content'];

        $this->ba->adminAuth();

        $request = $requestResponseData['request'];

        $response = $this->makeRequestAndGetContent($request);
        foreach ($response as $key => $value)
        {
            $this->assertEquals($value, $responseData[$key]);
        }

    }

}
