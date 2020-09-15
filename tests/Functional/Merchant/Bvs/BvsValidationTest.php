<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use DB;
use App;
use Config;

use Illuminate\Http\UploadedFile;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
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
        $mid = '10000000000000';

        $merchantDetailsData['merchant_id'] = $mid;

        $this->mockRazorX('testCreateBvsValidationPoi',
            'bvs_auto_kyc',
            'on',
            $mid);

        $bvsValidation = $this->triggerBvsVerification(__FUNCTION__, $merchantDetailsData);

        $this->validateBvsValidation($bvsValidation);
    }

    public function testCreateBvsValidationPoiFailed()
    {
        $merchantDetailsData = [
            'merchant_id' => '10000000000000'
        ];

        $this->mockRazorX('testCreateBvsValidationPoi',
            'bvs_auto_kyc',
            'on',
            '10000000000000');

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi',
            $merchantDetailsData,
            true,
            'failure');

        $this->assertNull($bvsValidation);
    }

    public function testCreateBvsValidationRequestFail()
    {
        $merchantDetailsData = [
            'merchant_id' => '10000000000000'
        ];

        $this->mockRazorX('testCreateBvsValidationPoi',
            'bvs_auto_kyc',
            'on',
            '10000000000000');

        $httpClient = $this->app['bvs_http_client'];

        $httpClient->addException(new \Exception('Failed to complete request to bvs'));

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi',
            $merchantDetailsData,
            false);

        $this->assertNull($bvsValidation);
    }

    public function testCreateBvsValidationPoa()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'   => $mid,
            'business_type' => '4',
        ];

        $test = 'testAadhaarDocumentUpload';

        $this->mockRazorX($test, 'bvs_auto_kyc_ocr', 'on', $mid);

        $this->updateUploadDocumentData($test);

        $bvsValidation = $this->triggerBvsVerification($test, $merchantDetailsData);

        $input = [
            'artefact_type' => 'aadhaar',
            'owner_id'      => $mid,
        ];

        $this->validateBvsValidation($bvsValidation, $input);
    }

    public function testUpdateBvsValidationStatusPoa()
    {
        $mid = '10000000000000';

        $bvsResponse = $this->getBvsResponse();

        $this->updateBvsValidationStatusCheck($mid, 'aadhaar', $bvsResponse);
    }

    private function updateUploadDocumentData(string $callee)
    {
        $testData = &$this->testData[$callee];

        $testData['request']['files']['file'] = new UploadedFile(
            __DIR__ . '/../../Storage/a.png',
            'a.png',
            'image/png',
            filesize(__DIR__ . '/../../Storage/a.png'),
            null,
            true);
    }

    private function updateBvsValidationStatusCheck(string $mid, string $artefact_type, array $bvsResponse)
    {
        $this->fixtures->create('bvs_validation', [
            'validation_id'     => '1234567890abcd',
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'validation_status' => 'captured',
            'artefact_type'     => $artefact_type,
        ]);

        $app = App::getFacadeRoot();

        $app['rzp.mode'] = 'test';

        KafkaMessageProcessor::dispatch('test');

        (new KafkaMessageProcessor())->process('api-bvs-validation-result-events',
                                               $bvsResponse);

        $bvsValidation = $this->getDbEntity('bvs_validation', ['owner_id' => $mid, 'owner_type' => 'merchant']);

        $input = [
            'artefact_type' => 'aadhaar',
            'owner_id'      => $mid,
        ];

        $input = array_merge($input, $bvsResponse);

        $this->validateBvsValidation($bvsValidation, $input);
    }

    private function getBvsResponse(string $status = 'success', string $errorCode = null, string $errorDesc = null)
    {
        $bvsSuccessResponse['data'] = [
            'validation_id'     => '1234567890abcd',
            'validation_status' => $status,
            'error_code'        => $errorCode,
            'error_description' => $errorDesc,
        ];

        return $bvsSuccessResponse;
    }

    private function triggerBvsVerification(string $test,
                                            array $merchantDetailsData,
                                            bool $bvsMock = true,
                                            string $responseSuccess = 'success')
    {
        $mid = $merchantDetailsData['merchant_id'];

        $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailsData);

        $this->ba->proxyAuth();

        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', $bvsMock);
        Config::set('services.bvs.response', $responseSuccess);

        $testData = &$this->testData[$test];

        $this->startTest($testData);

        return $this->getDbEntity('bvs_validation', ['owner_id' => $mid, 'owner_type' => 'merchant']);
    }

    private function validateBvsValidation(Entity $bvsValidation,
                                           array $input = [])
    {
        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEquals($input['validation_status'] ?? 'captured', $bvsValidation->getValidationStatus());
        $this->assertEquals($input['artefact_type'] ?? 'personal_pan', $bvsValidation->getArtefactType());
        $this->assertEquals($input['owner_id'] ?? '10000000000000', $bvsValidation->getOwnerId());
        $this->assertEquals($input['owner_type'] ?? 'merchant', $bvsValidation->getOwnerType());
        $this->assertEquals($input['error_code'] ?? null, $bvsValidation->getErrorCode());
        $this->assertEquals($input['error_description'] ?? null, $bvsValidation->getErrorDescription());
    }
}
