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

    public function validateSuccessBvsValidation(Entity $bvsValidation,
                                                 array $expectedValues = [])
    {
        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertNull($bvsValidation->getErrorCode());
        $this->assertNull($bvsValidation->getErrorDescription());
        $this->bvsValidation($bvsValidation, $expectedValues);
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

        $expectedValues = [
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'identifier',
            'owner_id'          => '10000000000000',
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

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

    public function testCreateBvsValidationPoaAadhaar()
    {
        $this->checkCreateBvsValidationPoa('aadhar_front', 'aadhaar');
    }

    public function testCreateBvsValidationPoaVoterId()
    {
        $this->checkCreateBvsValidationPoa('voter_id_front', 'voters_id');
    }

    public function testCreateBvsValidationPoaPassport()
    {
        $this->checkCreateBvsValidationPoa('passport_front', 'passport');
    }

    public function checkCreateBvsValidationPoa(string $documentType, string $artefactType)
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'   => $mid,
            'business_type' => '4',
        ];

        $test = 'testCreateBvsValidationPoa';

        $this->mockRazorX($test, 'bvs_auto_kyc_ocr', 'on', $mid);

        $this->updateUploadDocumentData($test);

        $request = &$this->testData[$test]['request'];

        $request['content']['document_type'] = sprintf($request['content']['document_type'], $documentType);

        $bvsValidation = $this->triggerBvsVerification($test, $merchantDetailsData);

        $expectedValues = [
            'artefact_type' => $artefactType,
            'owner_id'      => $mid,
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $document = $this->getDbEntity('merchant_document', ['merchant_id' => $mid]);
        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $this->assertNotNull($document->getValidationId());
        $this->assertNull($merchantDetails->getPoaVerificationStatus());
    }

    public function testCreateBvsValidationForGstin()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'       => $mid,
            'business_type'     => '1',
            'business_name'     => 'Razorpay',
            'promoter_pan_name' => 'Shk',
        ];

        $this->mockRazorX(__FUNCTION__, 'bvs_gstin_validation', 'on', $mid);

        $bvsValidation = $this->triggerBvsVerification(__FUNCTION__, $merchantDetailsData);

        $expectedValues = [
            'artefact_type'     => 'gstin',
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
            'validation_unit'   => 'identifier',
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);
    }

    public function testUpdateBvsValidationStatusPoa()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $capturedBvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'      => $mid,
                'artefact_type' => 'aadhaar',
            ]);

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'document_type' => 'aadhar_front',
                'validation_id' => $capturedBvsValidation->getValidationId(),
            ]);

        //
        // Poa_verification_status, activation_status are expected value used for validation
        // MerchantDetailData is edit merchantDetails according to test case requirement.
        //
        // 1) POI status failed 2) form submitted false 3) activation status = null only
        //
        $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
            $capturedBvsValidation,
            [
                'poa_verification_status' => 'failed',              //expected status
                'validation_status'       => 'failed',
                'activation_status'       => null,                  //expected status
            ],
            ['merchant_id' => $mid]
        );

        //
        // 1) POA status failed 2) form submitted true 3) bank details verification status = verified
        //
        $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
            $capturedBvsValidation,
            [
                'poa_verification_status' => 'failed',
                'validation_status'       => 'failed',
                'error_code'              => 'EXTERNAL_SERVICE_ERROR',
                'activation_status'       => 'under_review',
            ],
            [
                'merchant_id'       => $mid,
                'submitted'         => 1,
                'activation_status' => 'under_review',
            ]
        );

        //
        // 1) POA status failed 2) 3) activation status = under_review only
        //
        $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
            $capturedBvsValidation,
            [
                'poa_verification_status' => 'incorrect_details',
                'validation_status'       => 'failed',
                'error_code'              => 'VALIDATION_ERROR',
                'activation_status'       => 'under_review',
            ],
            [
                'merchant_id'                      => $mid,
                'submitted'                        => 1,
                'activation_status'                => 'under_review',
                'bank_details_verification_status' => 'verified',
                'poi_verification_status'          => 'verified',
            ]
        );

        //
        // 1) POA status Success 2) activation status = under_review only for registered businesses
        //
        $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
            $capturedBvsValidation,
            [
                'poa_verification_status' => 'verified',
                'validation_status'       => 'success',
                'error_code'              => '',
                'activation_status'       => 'under_review',
            ],
            [
                'merchant_id'                      => $mid,
                'submitted'                        => 1,
                'activation_status'                => 'under_review',
                'bank_details_verification_status' => 'verified',
                'poi_verification_status'          => 'verified',
            ]
        );

        //
        // 1) POA status Success 2) 3) activation status = Activated only unregistered
        //
        $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
            $capturedBvsValidation,
            [
                'poa_verification_status' => 'verified',
                'validation_status'       => 'success',
                'error_code'              => '',
                'activation_status'       => 'activated',
            ],
            [
                'merchant_id'                      => $mid,
                'submitted'                        => 1,
                'business_type'                    => 2,
                'activation_status'                => 'under_review',
                'bank_details_verification_status' => 'verified',
                'poi_verification_status'          => 'verified',
            ]
        );
    }

    public function updateBvsValidationStatusAndCheckMerchantDetailsPoa($capturedBvsValidation,
                                                                        $input,
                                                                        $merchantDetailsData = [])
    {
        $mid = $merchantDetailsData['merchant_id'];

        $this->fixtures->on('test')->edit('merchant_detail', $mid, $merchantDetailsData);
        $this->fixtures->on('live')->edit('merchant_detail', $mid, $merchantDetailsData);

        $this->verifyDocumentVerificationStatus(
            $capturedBvsValidation,
            $mid,
            'poa_verification_status',
            $input['poa_verification_status'],
            $input['validation_status'],
            $input['error_code'] ?? ''
        );

        $updatedMerchantDetails = $this->getDbEntityById('merchant_detail', $mid);

        $this->assertEquals($input['activation_status'], $updatedMerchantDetails->getActivationStatus());
    }

    public function testUpdateBvsValidationStatusGstin()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $capturedBvsValidation = $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'gstin',
        ]);

        $documentTypeStatusKey = 'gstin_verification_status';

        $possibleScenarios = [
            [
                'documentVerificationStatus' => 'verified',
                'validationStatus'           => 'success',
                'errorCode'                  => ''
            ],
            [
                'documentVerificationStatus' => 'failed',
                'validationStatus'           => 'failed',
                'errorCode'                  => 'NO_PROVIDER_ERROR',
            ],
            [
                'validationStatus'           => 'failed',
                'documentVerificationStatus' => 'not_matched',
                'errorCode'                  => 'RULE_EXECUTION_FAILED',
            ],
            [
                'validationStatus'           => 'failed',
                'documentVerificationStatus' => 'incorrect_details',
                'errorCode'                  => 'REMOTE_RECORDS_INCONSISTENT',
            ],
            [
                'validationStatus'           => 'failed',
                'documentVerificationStatus' => 'failed',
                'errorCode'                  => 'UNDEFINED_ERROR_CODE',
            ],
        ];

        foreach ($possibleScenarios as $scenario)
        {
            $this->verifyDocumentVerificationStatus(
                $capturedBvsValidation,
                $mid,
                $documentTypeStatusKey,
                $scenario['documentVerificationStatus'],
                $scenario['validationStatus'],
                $scenario['errorCode']
            );
        }
    }

    public function testUpdateBvsValidationStatusCinInCaseOfOutOfOrderEvents()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $capturedBvsValidation = $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => 'cin',
        ]);

        $this->fixtures->create('bvs_validation', [
            'owner_id'          => $mid,
            'artefact_type'     => 'cin',
            'validation_status' => 'success',
            //
            // Adding time else both timestamp will be same and we want this to be most recent entry
            //
            'created_at'        => time() + 100
        ]);

        $documentTypeStatusKey = 'cin_verification_status';

        $possibleScenarios = [
            [
                'documentVerificationStatus' => 'verified',
                'validationStatus'           => 'failed',
                'errorCode'                  => 'NO_PROVIDER_ERROR'
            ],
            [
                'documentVerificationStatus' => 'verified',
                'validationStatus'           => 'failed',
                'errorCode'                  => 'RULE_EXECUTION_FAILED',
            ],
            [
                'validationStatus'           => 'failed',
                'documentVerificationStatus' => 'verified',
                'errorCode'                  => 'UNDEFINED_ERROR_CODE',
            ],
        ];

        foreach ($possibleScenarios as $scenario)
        {
            $this->verifyDocumentVerificationStatus(
                $capturedBvsValidation,
                $mid,
                $documentTypeStatusKey,
                $scenario['documentVerificationStatus'],
                $scenario['validationStatus'],
                $scenario['errorCode']
            );
        }
    }

    public function testCreateBvsValidationForCin()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'       => $mid,
            'business_type'     => '4',
            'business_name'     => 'Razorpay',
            'promoter_pan_name' => 'Shk',
        ];

        $this->mockRazorX(__FUNCTION__, 'bvs_cin_validation', 'on', $mid);

        $bvsValidation = $this->triggerBvsVerification(__FUNCTION__, $merchantDetailsData);

        $expectedValidationValues = [
            'artefact_type'     => 'cin',
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
            'validation_unit'   => 'identifier',
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValidationValues);
    }

    public function testCreateBvsValidationForLLPIN()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'       => $mid,
            'business_type'     => '6',
            'business_name'     => 'Razorpay',
            'promoter_pan_name' => 'Shk',
        ];

        $this->mockRazorX(__FUNCTION__, 'bvs_cin_validation', 'on', $mid);

        $bvsValidation = $this->triggerBvsVerification(__FUNCTION__, $merchantDetailsData);

        $expectedValidationValues = [
            'artefact_type'     => 'llp_deed',
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
            'validation_unit'   => 'identifier',
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValidationValues);
    }

    /**
     * @param Entity $capturedBvsValidation
     * @param string $mid
     *
     * @param string $documentTypeStatusKey
     * @param string $documentValidationStatus
     * @param string $validationStatus
     * @param string $errorCode
     *
     */
    private function verifyDocumentVerificationStatus(
        Entity $capturedBvsValidation,
        string $mid,
        string $documentTypeStatusKey,
        string $documentValidationStatus,
        string $validationStatus,
        string $errorCode = '') :void
    {
        $countkey = 'bvs_validation_processing_attempt_count_' . $capturedBvsValidation->getValidationId();

        $this->app['cache']->put($countkey, 0, 180);

        $bvsResponse = $this->getBvsResponse(
            $capturedBvsValidation->getValidationId(),
            $validationStatus,
            $errorCode);

        $this->processBvsResponseAndValidate($bvsResponse, $capturedBvsValidation->getValidationId());

        $merchantDetail = $this->getDbEntityById('merchant_detail', $mid);

        $this->assertEquals($documentValidationStatus, $merchantDetail->getAttribute($documentTypeStatusKey));
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

    private function processBvsResponseAndValidate(array $bvsResponse, string $validationId)
    {
        (new KafkaMessageProcessor())->process('api-bvs-validation-result-events',
                                               $bvsResponse, 'test');

        $bvsValidationPostResponse = $this->getDbEntity('bvs_validation',
                                                        ['validation_id' => $validationId]);

        $expectedValues                      = $bvsResponse['data'];
        $expectedValues['validation_status'] = $expectedValues['status'];

        //
        // Unsetting status because field is different in bvs kafka response and bvs validation entity
        //
        unset($expectedValues['status']);

        $this->bvsValidation($bvsValidationPostResponse, $expectedValues);
    }

    private function getBvsResponse(string $validationId,
                                    string $status = 'success',
                                    string $errorCode = '',
                                    string $errorDesc = '')
    {
        $bvsResponse['data'] = [
            'validation_id'     => $validationId,
            'status'            => $status,
            'error_code'        => $errorCode,
            'error_description' => $errorDesc,
        ];

        return $bvsResponse;
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

    private function bvsValidation(Entity $bvsValidation,
                                   array $expectedValues = [])
    {
        //
        // resetting time based data
        //
        unset($expectedValues['created_at']);
        unset($expectedValues['updated_at']);

        foreach ($expectedValues as $key => $value)
        {
            $this->assertEquals($value, $bvsValidation->getAttribute($key));
        }
    }
}
