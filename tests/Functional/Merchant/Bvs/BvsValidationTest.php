<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use DB;
use Config;

use RZP\Tests\Functional\TestCase;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BvsValidationTest extends TestCase
{
    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    public function setUp()
    {
        $this->testDataFilePath = __DIR__ . '/BvsValidationTestData.php';

        parent::setUp();
    }

    public function testCreateBvsValidationPoi()
    {
        $merchantDetailsData['merchant_id'] = '10000000000000';

        $bvsValidation = $this->triggerBvsVerification(__FUNCTION__, $merchantDetailsData);

        $this->validateBvsValidationSuccess($bvsValidation);
    }

    public function testCreateBvsValidationPoiFailed()
    {
        $merchantDetailsData = [
            'merchant_id' => '10000000000000'
        ];

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi', $merchantDetailsData, true, 'failure');

        $this->assertNull($bvsValidation);
    }

    public function testCreateBvsValidationRequestFail()
    {
        $merchantDetailsData = [
            'merchant_id' => '10000000000000'
        ];

        $httpClient = $this->app['bvs_http_client'];

        $httpClient->addException(new \Exception('Failed to complete request to bvs'));

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi', $merchantDetailsData, false);

        $this->assertNull($bvsValidation);
    }

    private function triggerBvsVerification(string $test,
                                            array $merchantDetailsData,
                                            bool $bvsMock = true,
                                            string $responseSuccess = 'success')
    {
        $mid = $merchantDetailsData['merchant_id'];

        $this->fixtures->create('merchant_detail:filledEntity', $merchantDetailsData);

        $this->ba->proxyAuth();

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', $bvsMock);
        Config::set('services.bvs.pan_authentication', $responseSuccess);

        $this->mockRazorX(__FUNCTION__, 'bvs_auto_kyc', 'on', $mid);

        $testData = &$this->testData[$test];

        $this->startTest($testData);

        return $this->getDbEntity('bvs_validation', ['owner_id' => $mid, 'owner_type' => 'merchant']);
    }

    private function validateBvsValidationSuccess(Entity $bvsValidation)
    {
        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEquals('captured', $bvsValidation->getValidationStatus());
        $this->assertNull($bvsValidation->getErrorCode());
        $this->assertNull($bvsValidation->getErrorDescription());
    }
}
