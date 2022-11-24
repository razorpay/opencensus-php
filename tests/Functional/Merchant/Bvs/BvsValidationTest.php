<?php

namespace RZP\Tests\Functional\Merchant\Bvs;

use Mail;
use DB;
use App;
use Config;
use Illuminate\Support\Facades\Queue;

use Functional\Helpers\BvsTrait;
use Illuminate\Http\UploadedFile;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\Document\Type;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\RazorXClient;
use RZP\Tests\Functional\TestCase;
use RZP\Services\KafkaMessageProcessor;
use RZP\Tests\Functional\Helpers\RazorxTrait;
use RZP\Models\Merchant\BvsValidation\Entity;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Models\Merchant\BvsValidation\Repository;
use RZP\Tests\Functional\RequestResponseFlowTrait;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;

class BvsValidationTest extends TestCase
{
    use BvsTrait;
    use RazorxTrait;
    use RequestResponseFlowTrait;
    use DbEntityFetchTrait;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/BvsValidationTestData.php';

        parent::setUp();
    }

    public function validateSuccessBvsValidation(Entity $bvsValidation,
                                                 array $expectedValues = [])
    {
        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEmpty($bvsValidation->getErrorCode());
        $this->assertEmpty($bvsValidation->getErrorDescription());
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
            'owner_id'          => $mid,
            'owner_type'        => 'merchant',
            'platform'          => 'pg',
            'validation_status' => 'captured',
        ];
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', 'personal_pan');

        // since we've removed that code now.
        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

    }

    public function testCreateBvsValidationPoiFailed()
    {
        $mid='10000000000000';
        $merchantDetailsData = [
            'merchant_id' => $mid
        ];

        $this->mockRazorX('testCreateBvsValidationPoi',
                          'bvs_auto_kyc',
                          'on',
                          $mid);

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi',
                                                       $merchantDetailsData,
                                                       true,
                                                       'failure');
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', 'personal_pan');


        $this->assertNull($bvsValidation);
    }

    public function testCreateBvsValidationRequestFail()
    {
        $mid='10000000000000';
        $merchantDetailsData = [
            'merchant_id' => $mid
        ];

        $this->mockRazorX('testCreateBvsValidationPoi',
                          'bvs_auto_kyc',
                          'on',
                          $mid);

        $httpClient = $this->app['bvs_http_client'];

        $httpClient->addException(new \Exception('Failed to complete request to bvs'));

        $bvsValidation = $this->triggerBvsVerification('testCreateBvsValidationPoi',
                                                       $merchantDetailsData,
                                                       false);

        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', 'personal_pan');
        $this->assertNull($bvsValidation);
    }

    public function testCreateBvsValidationPoaAadhaar()
    {
        $this->checkCreateBvsValidationPoa('aadhar_front', 'aadhaar');
    }

    public function testCreateBvsValidationAadhaarBack()
    {
        $this->checkCreateBvsValidationAadharBack();
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
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', $artefactType);

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $document        = $this->getDbEntity('merchant_document', ['merchant_id' => $mid]);
        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $this->assertNotNull($document->getValidationId());
        $this->assertNull($merchantDetails->getPoaVerificationStatus());
    }

    public function checkCreateBvsValidationAadharBack()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'   => $mid,
            'business_type' => '4',
        ];

        $test = 'testCreateBvsValidationAadharBack';
        $this->mockRazorX($test, 'bvs_auto_kyc_ocr', 'on', $mid);

        $this->updateUploadDocumentData($test);

        $request = &$this->testData[$test]['request'];

        $request['content']['document_type'] = sprintf($request['content']['document_type'], Type::AADHAR_BACK);

        $bvsValidation = $this->triggerBvsVerification($test, $merchantDetailsData);
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', Constant::AADHAAR);

        $expectedValues = [
            'artefact_type' => Constant::AADHAAR,
            'owner_id'      => $mid,
        ];

        $this->validateSuccessBvsValidation($bvsValidation, $expectedValues);

        $document        = $this->getDbEntity('merchant_document', ['merchant_id' => $mid]);

        $this->assertNotNull($document->getValidationId());
    }

    public function testUpdateBvsValidationStatusPoa()
    {
        $this->enableRazorXTreatmentForBvsValidation();

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
        ]);

        $mid = $merchantDetail->getId();

        $this->fixtures->create('stakeholder', ['merchant_id' => $mid]);

        $capturedBvsValidation = $this->fixtures->create('bvs_validation',
                                                         [
                                                             'owner_id'        => $mid,
                                                             'artefact_type'   => Constant::AADHAAR,
                                                             'validation_unit' => 'proof'
                                                         ]);

        $this->fixtures->create('merchant_document',
                                [
                                    'merchant_id'   => $mid,
                                    'document_type' => Type::AADHAR_FRONT,
                                    'validation_id' => $capturedBvsValidation->getValidationId(),
                                ]);

        $possibleScenarios = [
            [
                'input'               => [
                    'documentVerificationKey'    => 'poa_verification_status',
                    'documentVerificationStatus' => 'failed',              //expected status
                    'validation_status'          => 'failed',
                    'activation_status'          => null,                  //expected status
                ],
                'merchantDetailsData' => ['merchant_id' => $mid]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'poa_verification_status',
                    'documentVerificationStatus' => 'failed',
                    'validation_status'          => 'failed',
                    'error_code'                 => 'EXTERNAL_SERVICE_ERROR',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'       => $mid,
                    'submitted'         => 1,
                    'activation_status' => 'under_review',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'poa_verification_status',
                    'documentVerificationStatus' => 'incorrect_details',
                    'validation_status'          => 'failed',
                    'error_code'                 => 'VALIDATION_ERROR',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'                      => $mid,
                    'submitted'                        => 1,
                    'activation_status'                => 'under_review',
                    'bank_details_verification_status' => 'verified',
                    'poi_verification_status'          => 'verified',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'poa_verification_status',
                    'documentVerificationStatus' => 'verified',
                    'validation_status'          => 'success',
                    'error_code'                 => '',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'                      => $mid,
                    'submitted'                        => 1,
                    'activation_status'                => 'under_review',
                    'bank_details_verification_status' => 'verified',
                    'poi_verification_status'          => 'verified',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'poa_verification_status',
                    'documentVerificationStatus' => 'verified',
                    'validation_status'          => 'success',
                    'error_code'                 => '',
                    'activation_status'          => 'activated_mcc_pending',
                ],
                'merchantDetailsData' => [
                    'merchant_id'                      => $mid,
                    'submitted'                        => 1,
                    'business_type'                    => 2,
                    'activation_status'                => 'under_review',
                    'bank_details_verification_status' => 'verified',
                    'poi_verification_status'          => 'verified',
                ]
            ],
        ];
        foreach ($possibleScenarios as $possibleScenario)
        {
            $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
                $capturedBvsValidation,
                $possibleScenario['input'],
                $possibleScenario['merchantDetailsData']
            );
        }
    }

    public function testUpdateBvsValidationStatusBankDetails()
    {
        $this->enableRazorXTreatmentForBvsValidation();

        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
        ]);

        $mid = $merchantDetail->getId();

        $capturedBvsValidation = $this->fixtures->create('bvs_validation',
                                                         [
                                                             'owner_id'      => $mid,
                                                             'artefact_type' => 'bank_account',
                                                         ]);

        $possibleScenarios = [
            [
                'input'               => [
                    'documentVerificationKey'    => 'bank_details_verification_status',
                    'documentVerificationStatus' => 'failed',              //expected status
                    'validation_status'          => 'failed',
                    'activation_status'          => null,                  //expected status
                ],
                'merchantDetailsData' => ['merchant_id' => $mid]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'bank_details_verification_status',
                    'documentVerificationStatus' => 'failed',
                    'validation_status'          => 'failed',
                    'error_code'                 => 'EXTERNAL_SERVICE_ERROR',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'       => $mid,
                    'submitted'         => 1,
                    'activation_status' => 'under_review',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'bank_details_verification_status',
                    'documentVerificationStatus' => 'incorrect_details',
                    'validation_status'          => 'failed',
                    'error_code'                 => 'VALIDATION_ERROR',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'                      => $mid,
                    'submitted'                        => 1,
                    'activation_status'                => 'under_review',
                    'bank_details_verification_status' => 'verified',
                    'poi_verification_status'          => 'verified',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'bank_details_verification_status',
                    'documentVerificationStatus' => 'verified',
                    'validation_status'          => 'success',
                    'error_code'                 => '',
                    'activation_status'          => 'under_review',
                ],
                'merchantDetailsData' => [
                    'merchant_id'             => $mid,
                    'submitted'               => 1,
                    'activation_status'       => 'under_review',
                    'poa_verification_status' => 'verified',
                    'poi_verification_status' => 'verified',
                ]
            ],
            [
                'input'               => [
                    'documentVerificationKey'    => 'bank_details_verification_status',
                    'documentVerificationStatus' => 'verified',
                    'validation_status'          => 'success',
                    'error_code'                 => '',
                    'activation_status'          => 'activated_mcc_pending',
                ],
                'merchantDetailsData' => [
                    'merchant_id'             => $mid,
                    'submitted'               => 1,
                    'business_type'           => 2,
                    'activation_status'       => 'under_review',
                    'poa_verification_status' => 'verified',
                    'poi_verification_status' => 'verified',
                ]
            ],
        ];

        foreach ($possibleScenarios as $possibleScenario)
        {
            $this->updateBvsValidationStatusAndCheckMerchantDetailsPoa(
                $capturedBvsValidation,
                $possibleScenario['input'],
                $possibleScenario['merchantDetailsData']
            );
        }
    }

    protected function enableRazorXTreatmentForBvsValidation()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->will($this->returnCallback(
                function($mid, $feature, $mode) {
                    return 'off';
                }));
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
            $input['documentVerificationKey'],
            $input['documentVerificationStatus'],
            $input['validation_status'],
            $input['error_code'] ?? ''
        );

        $updatedMerchantDetails = $this->getDbEntityById('merchant_detail', $mid);

        $this->assertEquals($input['activation_status'], $updatedMerchantDetails->getActivationStatus());

        if ($input['documentVerificationKey'] === 'poa_verification_status')
        {
            $stakeholder = $this->getDbEntities('stakeholder', ['merchant_id' => $mid])->first();
            $this->assertNotNull($stakeholder);
            $this->assertEquals($input['documentVerificationStatus'], $stakeholder->getPoaStatus());
            $this->assertEquals($updatedMerchantDetails->getPoaVerificationStatus(), $stakeholder->getPoaStatus());
        }
    }

    public function testUpdateBvsValidationStatusGstin()
    {
        //Queue::fake();

        $this->verifyArtefactValidation('gstin', "gstin_verification_status");

        //Queue::assertPushed(UpdateMerchantContext::class, 5);
    }

    public function verifyArtefactValidation(string $artefactType, string $documentTypeStatusKey)
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields');

        $mid = $merchantDetail->getId();

        $capturedBvsValidation = $this->fixtures->create('bvs_validation', [
            'owner_id'      => $mid,
            'artefact_type' => $artefactType,
        ]);

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

    public function testUpdateBvsValidationStatusShopEstbNumber()
    {
        $this->verifyArtefactValidation('shop_establishment', 'shop_establishment_verification_status');
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
        string $errorCode = ''): void
    {
        $countkey = 'bvs_validation_processing_attempt_count_' . $capturedBvsValidation->getValidationId();

        $this->app['cache']->put($countkey, 0, 648000);

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
            null,
            true);
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

    private function createAndFetchMocks($razorXEnabled)
    {
        Mail::fake();
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        if ($razorXEnabled)
        {
            $this->app['razorx']->method('getTreatment')
                                ->will($this->returnCallback(
                                    function($mid, $feature, $mode) {
                                        if ($feature === RazorxTreatment::BVS_PENNY_TESTING)
                                        {
                                            return 'on';
                                        }
                                        elseif ($feature===RazorxTreatment::AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION){
                                            return 'off';
                                        }
                                        return 'on';
                                    }));
        }
        else
        {
            $this->app['razorx']->method('getTreatment')
                                ->will($this->returnCallback(
                                    function($mid, $feature, $mode) {
                                        if ($feature === RazorxTreatment::BVS_PENNY_TESTING)
                                        {
                                            return 'on';
                                        }

                                        elseif ($feature===RazorxTreatment::AADHAAR_FRONT_AND_BACK_JOINT_VALIDATION){
                                            return 'off';
                                        }
                                        return 'off';
                                    }));
        }

    }

    public function testCreateBvsValidationPoaAadhaarSyncEnabled()
    {
        $this->checkSyncEnabledPOA('aadhar_front', 'aadhaar');
    }

    public function testCreateBvsValidationAadhaarBackSyncEnabled()
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'             => $mid,
            'business_type'           => '4',
            'poa_verification_status' => null
        ];
        $documentType        = 'aadhar_back';
        $artefactType        = 'aadhaar';
        $test                = 'testCreateBvsValidationPoa';

        $this->createAndFetchMocks(true);

        $this->updateUploadDocumentData($test);

        $request = &$this->testData[$test]['request'];

        $request['content']['document_type'] = sprintf($request['content']['document_type'], $documentType);

        $bvsValidation = $this->triggerBvsVerification($test, $merchantDetailsData);

        $expectedValues = [
            'artefact_type' => $artefactType,
            'owner_id'      => $mid,
        ];
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', $artefactType);

        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEmpty($bvsValidation->getErrorCode());
        $this->assertEmpty($bvsValidation->getErrorDescription());
        $this->assertEquals("captured", $bvsValidation->getValidationStatus());

        $document        = $this->getDbEntity('merchant_document', ['merchant_id' => $mid]);
        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $this->assertNotNull($document->getValidationId());
        $this->assertNull($merchantDetails->getPoaVerificationStatus());

    }

    public function testCreateBvsValidationPoaVoterIdSyncEnabled()
    {
        $this->checkSyncEnabledPOA('voter_id_front', 'voters_id');
    }

    public function testCreateBvsValidationPoaPassportSyncEnabled()
    {
        $this->checkSyncEnabledPOA('passport_front', 'passport');
    }

    public function checkSyncEnabledPOA($documentType,$artefactType)
    {
        $mid = '10000000000000';

        $merchantDetailsData = [
            'merchant_id'   => $mid,
            'business_type' => '4',
        ];

        $test                = 'testCreateBvsValidationPoa';

        $this->createAndFetchMocks(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($mid);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $mid,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->updateUploadDocumentData($test);

        $request = &$this->testData[$test]['request'];

        $request['content']['document_type'] = sprintf($request['content']['document_type'], $documentType);

        $bvsValidation = $this->triggerBvsVerification($test, $merchantDetailsData);

        $expectedValues = [
            'artefact_type' => $artefactType,
            'owner_id'      => $mid,
        ];
        // Verify bvs_validation entity is created
        $bvsValidation = (new Repository)->getLatestArtefactValidationForOwnerIdAndOwnerType($mid, 'merchant', $artefactType);

        $this->assertNotNull($bvsValidation->getValidationId());
        $this->assertEmpty($bvsValidation->getErrorCode());
        $this->assertEmpty($bvsValidation->getErrorDescription());
        $this->assertEquals("success", $bvsValidation->getValidationStatus());

        $document        = $this->getDbEntity('merchant_document', ['merchant_id' => $mid]);
        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $this->assertNotNull($document->getValidationId());
        $this->assertEquals("verified", $merchantDetails->getPoaVerificationStatus());
    }
}
