<?php

namespace Unit\Models\Merchant\Detail;

use App;
use Queue;
use Config;
use Mockery;
use ReflectionClass;
use Carbon\Carbon;
use RZP\Models\Coupon;
use RZP\Constants\Mode;
use RZP\Services\Stork;
use RZP\Models\Merchant\Store;
use RZP\Models\Coupon\Constants;
use RZP\Jobs\UpdateMerchantContext;
use RZP\Models\Merchant\Detail\Core;
use RZP\Services\KafkaMessageProcessor;
use RZP\Services\KafkaProducerClient;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Models\ClarificationDetail\Service;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant;
use RZP\Tests\Traits\TestsStorkServiceRequests;
use RZP\Models\Merchant\Detail\Core as DetailCore;
use RZP\Services\Segment\EventCode as SegmentEvent;
use RZP\Models\Feature as Feature;
use RZP\Models\Merchant\Website\Service as WebsiteService;
use RZP\Models\Merchant\Escalations;
use RZP\Services\Mock\HarvesterClient;
use RZP\Services\RazorXClient;
use RZP\Tests\Traits\MocksSplitz;
use Illuminate\Support\Facades\Mail;
use RZP\Models\Admin\Org\Entity as OrgEntity;
use RZP\Mail\Merchant\MerchantOnboardingEmail;
use RZP\Models\Merchant\Store\Core as StoreCore;
use RZP\Tests\Functional\Merchant\MerchantTest;
use RZP\Services\Segment\SegmentAnalyticsClient;
use RZP\Tests\Functional\Fixtures\Entity\BvsValidation;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Tests\Functional\Helpers\DbEntityFetchTrait;
use RZP\Tests\Functional\TestCase;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Detail\Service as MDS;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Merchant\Detail\Status;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Admin\Org\Entity as ORG_ENTITY;
use RZP\Models\Merchant\Detail\Entity as DetailEntity;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Detail\SelectiveRequiredFields;
use RZP\Models\Merchant\Store\ConfigKey as StoreConfigKey;
use RZP\Models\Merchant\Store\Constants as StoreConstants;
use RZP\Models\Merchant\Detail\Constants as DetailConstant;
use RZP\Models\Merchant\M2MReferral\Status as M2MEntityStatus;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\BvsValidation\Entity as BVSEntity;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\Cron as CronJobHandler;
use RZP\Models\Merchant\Document;
use RZP\Services\Mock\KafkaProducerClient as KafkaProducerClientMock;
use RZP\Services\Mock\DataLakePresto as DataLakePrestoMock;

class CoreTest extends TestCase
{
    protected $repo;
    protected $app;
    protected $config;

    use DbEntityFetchTrait;
    use MocksSplitz;
    use TestsStorkServiceRequests;

    protected function setUp(): void
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CoreTestData.php';

        parent::setUp();
        $this->app = App::getFacadeRoot();
        $this->repo = $this->app['repo'];

        $this->config = \Illuminate\Support\Facades\App::getFacadeRoot()['config'];

        Config::set('services.kafka.producer.mock', true);
    }
    protected function mockRazorxTreatment(string $returnValue = 'on')
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
                           ->setConstructorArgs([$this->app])
                           ->setMethods(['getTreatment'])
                           ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
                          ->willReturn($returnValue);
    }

    protected function createAndFetchMocks()
    {
        $mockMC = $this->getMockBuilder(MerchantCore::class)
            ->setMethods(['isRazorxExperimentEnable'])
            ->getMock();

        $mockMC->expects($this->any())
            ->method('isRazorxExperimentEnable')
            ->willReturn(true);

        return [
            "merchantCoreMock"    => $mockMC
        ];
    }

    private function createTransaction(string $merchantId, string $type, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('transaction', [
            'type'          => $type,
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);

        $this->mockPinot($merchantId, $amount);
    }

    private function createPayment(string $merchantId, int $amount, int $createdAt = null)
    {
        if($createdAt === null)
        {
            $createdAt = Carbon::now()->getTimestamp();
        }

        $transaction = $this->fixtures->on('live')->create('payment', [
            'amount'        => $amount * 100,   // in paisa
            'merchant_id'   => $merchantId,
            'created_at'    => $createdAt
        ]);

        $this->mockPinot($merchantId, $amount);
    }

    protected function createAndFetchFixtures($customMerchantAttributes, $customVerificationDetailAttributes, $customBvsDetails)
    {
        $defaultMerchantAttributes = [
            'promoter_pan'            => 'BRRPK8070K',
            'promoter_pan_name'       => 'kakarla vasanthi',
            'company_pan'             => 'ABCCD1234A',
            'business_name'           => 'xyz',
            'bank_account_number'     => '1234567890',
            'bank_branch_ifsc'        => 'UTIB0002953',
            'bank_account_name'       => 'XYZ',
            'company_cin'             => 'U67190TN2014PTC096971',
            'gstin'                   => '01AADCB1234M1ZX',
        ];

        $merchantDetail = $this->fixtures->create(
            'merchant_detail:valid_fields',
            array_merge($defaultMerchantAttributes, $customMerchantAttributes));

        $mid = $merchantDetail->getId();

        $defaultVerificationDetailAttributes = [
            'merchant_id'          => $mid,
            'artefact_type'        => 'gstin',
            'artefact_identifier'  => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
            array_merge($defaultVerificationDetailAttributes, $customVerificationDetailAttributes));

        $defaultBvsDetails = [
            'owner_type'        => 'merchant',
            'owner_id'          => $mid,
            'validation_status' => BvsValidationConstants::CAPTURED,
            'platform'          => 'pg',
            'created_at'        => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $bvsValidation = $this->fixtures->create(
            'bvs_validation',
            array_merge($defaultBvsDetails, $customBvsDetails));

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_FRONT,
                'file_store_id' => '123123',
            ]);

        $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $mid,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::AADHAR_BACK,
                'file_store_id' => '123123'
            ]);

        return [
            'merchant_detail'    => $merchantDetail,
            'verificationDetail' => $verificationDetail,
            'bvsValidation'      => $bvsValidation
        ];
    }

    protected function mockBvsService(string $bvsResponse)
    {
        Config::set('applications.kyc.mock', true);
        Config::set('services.bvs.mock', true);
        Config::set('services.bvs.response', $bvsResponse);
    }

    private function createSignatoryVerified($merchantId)
    {
        $kafkaPayload  = [
            0 => [
                'rule' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def' => [
                        0 => [
                            'fuzzy_wuzzy' => [
                                0 => [
                                    'var' => 'artefact.details.business_name.value',
                                ],
                                1 => [
                                    'var' => 'enrichments.ocr.details.1.business_name.value',
                                ],
                                2 => 60,
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => true,
                    'operator' => 'fuzzy_wuzzy',
                    'operands' => [
                        'operand_1' => 'ORANGEE CLOTHINGLINE',
                        'operand_2' => 'ORANGEE CLOTHINGLINE',
                        'operand_3' => 60,
                        'operand_4' => [
                            "private limited",
                            "limited liability partnership",
                            "pvt",
                            "ltd",
                            "."
                        ]
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_custom_algorithm_4',
                        'match_percentage'      => 100,
                        'required_percentage'   => 60,
                    ],
                ],
                'error' => '',
            ],
            1 => [
                'rule' => [
                    'rule_type' => 'array_comparison_rule',
                    'rule_def' => [
                        'any' => [
                            0 => [
                                'var' => 'enrichments.ocr.details.1.name_of_partners',
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'each_array_element',
                                    ],
                                    1 => [
                                        'var' => 'artefact.details.name_of_partners',
                                    ],
                                    2 => 60,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result'   => true,
                    'operator' => 'some',
                    'operands' => [
                        'operand_1' => [
                            'result'   => true,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'HARSHILMATHUR ',
                                'operand_2' => 'HARSHILMATHUR',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 100,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result'   => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Shashank kumar ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ],
                    ],
                    'remarks' => [
                        'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                        'match_percentage'      => 29,
                        'required_percentage'   => 70,
                    ],
                ]
            ]
        ];

        $bvsValidation = $this->fixtures->create('bvs_validation',
            [
                'owner_id'        => $merchantId,
                'artefact_type'   => Constant::PARTNERSHIP_DEED,
                'validation_unit' => 'proof',
            ]);

        $input = [
            "experiment_id" => "LhL34xFB6fki66",
            "id"            => $merchantId,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $kafkaEventPayload = [
            'data'  => [
                'validation_id'         => $bvsValidation->getValidationId(),
                'status'                => 'success',
                'rule_execution_list'   => $kafkaPayload,
                'error_description'     => '',
                'error_code'            => '',
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-validation-result-events', $kafkaEventPayload);
    }

    private function createWebsitePolicyAndNegativeKeywordFixtures($merchantId)
    {
        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantId,
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);
    }

    public function testBvsPartlyExecutedValidationProcessForPOIValidation()
    {
        $this->createAndFetchMocks();
        // set poi_verification_status as null and validation status as success
        $fixtures = $this->createAndFetchFixtures([
            Entity::POI_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PERSONAL_PAN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationProcessForPOIValidationInitiated()
    {
        $this->createAndFetchMocks();
        // set poi_verification_status as null and validation status as success
        $fixtures = $this->createAndFetchFixtures([
                                                      Entity::POI_VERIFICATION_STATUS => 'initiated',
                                                  ],[],[
                                                      BVSConstants::ARTEFACT_TYPE     => BVSConstants::PERSONAL_PAN,
                                                      BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
                                                      BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
                                                  ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationProcessForPOIValidationFailure()
    {
        $this->createAndFetchMocks();

        // set poi_verification_status as null and validation status as failure
        $fixtures = $this->createAndFetchFixtures([
            Entity::POI_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PERSONAL_PAN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);


        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForPersonalPanValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'poi_verification_status' => 'initiated',
        ],[],[
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::POI_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForPersonalPanValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'personal_pan_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'personal_pan',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::PERSONAL_PAN_DOC_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAVoterIdUnitProof()
    {
        $this->createAndFetchMocks();

        // set poa verification as null and test for artifact voter id with validation unit proof
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::VOTER_ID_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAPassportUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAVoterIdIdentifierProof()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for voters id with validation unit as identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::VOTER_ID_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAPassportIdentifierProof()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for passport with validation unit identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedValidationPOAFailed()
    {
        $this->createAndFetchMocks();

        // set poa_verification_status as null for passport with validation unit identifier
        $fixtures = $this->createAndFetchFixtures([
            Entity::POA_VERIFICATION_STATUS => null,
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::PASSPORT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvsValidation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $merchant_document = $this->fixtures->create('merchant_document',
            [
                'merchant_id'   => $merchantId,
                'validation_id' => $bvsValidation->getValidationId(),
                'document_type' => Document\Type::PASSPORT_FRONT,
                'file_store_id' => '123123',
            ]);

        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvsValidation = $this->repo->bvs_validation->findOrFail($bvsValidation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvsValidation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::POA_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBusinessPanValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'company_pan_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'business_pan',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::COMPANY_PAN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBusinessPanValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'company_pan_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'business_pan',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('failed', $merchant->getAttribute(Entity::COMPANY_PAN_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBankAccountValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'bank_details_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'bank_account',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedBankAccountValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        // set bank_details_verification_status as null with validation status success
        $fixtures = $this->createAndFetchFixtures([
            Entity::BANK_DETAILS_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();


        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::SUCCESS, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::VERIFIED, $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsPartlyExecutedBankAccountValidationUnitIdentifierFailure()
    {
        $this->createAndFetchMocks();

        // set bank_details_verification_status as null with validation status success
        $fixtures = $this->createAndFetchFixtures([
            Entity::BANK_DETAILS_VERIFICATION_STATUS => null
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();


        (new CronJobHandler\Core())->handleCron(CronConstants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals(BvsValidationConstants::FAILED, $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals(BvsValidationConstants::FAILED, $merchant->getAttribute(Entity::BANK_DETAILS_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForBankAccountValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'bank_details_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'bank_account',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::BANK_DETAILS_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForCINValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'cin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'cin',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::CIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForLLPDeedValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'cin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'llp_deed',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('failed', $merchant->getAttribute(Entity::CIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForGSTINValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'gstin_verification_status'  => 'initiated'
        ],[],[
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::GSTIN_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForGSTINValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([],[
            'artefact_type'        => 'gstin',
            'artefact_identifier'  => 'doc',
        ],[
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];
        $verificationDetail = $fixtures['verificationDetail'];
        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

         $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
         $this->assertEquals('success', $bvs_validation->getValidationStatus());

         $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
             $merchantId,
             'gstin',
             'doc'
         );

         $this->assertEquals('verified', $verificationDetail->getAttribute('status'));
    }

    public function testBvsValidationProcessForMSMEValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'msme_doc_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'msme',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::MSME_DOC_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForShopEstablishmentValidationUnitIdentifier()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'shop_establishment_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));
    }

    public function testBvsValidationProcessForShopEstablishmentValidationUnitProof()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([], [
            'artefact_type'        => 'shop_establishment',
            'artefact_identifier'  => 'doc',
        ], [
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'proof',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation = $fixtures['bvsValidation'];
        $verificationDetail = $fixtures['verificationDetail'];
        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        $this->fixtures->connection('live')->create('merchant_verification_detail', $verificationDetail->toArray());

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());

        $verificationDetail = $this->repo->merchant_verification_detail->getDetailsForTypeAndIdentifier(
            $merchantId,
            'shop_establishment',
            'doc'
        );

        $this->assertEquals('verified', $verificationDetail->getAttribute('status'));
    }

    public function testBvsValidationProcessForOnlyLatestValidation()
    {
        $this->createAndFetchMocks();

        $fixtures = $this->createAndFetchFixtures([
            'shop_establishment_verification_status' => 'initiated'
        ],[],[
            'artefact_type'     => 'shop_establishment',
            'validation_unit'   => 'identifier',
        ]);

        $merchantDetail = $fixtures['merchant_detail'];
        $bvs_validation_latest = $fixtures['bvsValidation'];

        $merchantId = $merchantDetail->getMerchantId();

        $bvsResponse = 'success';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation_latest->getValidationId());
        $this->assertEquals('success', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));

        $bvs_validation_old = $this->fixtures->create(
            'bvs_validation',[
            'owner_type'        => 'merchant',
            'owner_id'          => $merchantId,
            'validation_status' => 'captured',
            'platform'          => 'pg',
            'created_at'        => Carbon::now()->subDays(3)->getTimestamp(),
            'artefact_type'     => 'gstin',
            'validation_unit'   => 'identifier',
        ]);

        $bvsResponse = 'failed';
        $this->mockBvsService($bvsResponse);

        (new CronJobHandler\Core())->handleCron("bvs_cron", [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $bvs_validation = $this->repo->bvs_validation->findOrFail($bvs_validation_old->getValidationId());
        $this->assertEquals('failed', $bvs_validation->getValidationStatus());
        $merchant = $this->repo->merchant_detail->findOrFail($merchantId);
        $this->assertEquals('verified', $merchant->getAttribute(Entity::SHOP_ESTABLISHMENT_VERIFICATION_STATUS));
    }

    public function testSegmentEventPushForFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }
    public function testM2MSegmentEventPushForFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchant=$merchantDetail->merchant;

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $merchantId,
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];
        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);

        (new Escalations\Core)->handleMtuCouponApply();
    }
    protected function enableRazorXTreatmentForRazorX()
    {
        $razorxMock = $this->getMockBuilder(RazorXClient::class)
            ->setConstructorArgs([$this->app])
            ->setMethods(['getTreatment', 'getCachedTreatment'])
            ->getMock();

        $this->app->instance('razorx', $razorxMock);

        $this->app->razorx->method('getTreatment')
            ->willReturn('on');
    }

    private function mockPinot(string $merchantId, int $amount)
    {
        $pinotService = $this->getMockBuilder(HarvesterClient::class)
                             ->setConstructorArgs([$this->app])
                             ->setMethods(['getDataFromPinot'])
                             ->getMock();

        $this->app->instance('eventManager', $pinotService);

        $dataFromPinot = ['merchant_id' => $merchantId, "amount" => $amount * 100, "transacted_merchants_count" => 1];

        $pinotService->method('getDataFromPinot')
                     ->willReturn([$dataFromPinot]);
    }


    public function testMtuCouponApplicationOnFirstTransactionExistingPromotion()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $p = $this->fixtures->on('live')->create('promotion', [
            'name'          => 'RZPNEO',
            'product'       => 'banking',
            'credit_amount' => 0,
            'iterations'    => 1
        ]);

        $this->fixtures->on('live')->create('merchant_promotion', [
            'merchant_id'           => $merchantId,
            'promotion_id'          => $p['id'],
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $promotion = $this->fixtures->on('live')->create('promotion', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::MTU_COUPON
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $data = [
            StoreConstants::NAMESPACE                    => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::MTU_COUPON_POPUP_COUNT       => 1
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $data, StoreConstants::INTERNAL);

        (new Escalations\Core)->handleMtuCouponApply();

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP],
            StoreConstants::INTERNAL);

        $this->assertNull($data[StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP]);

        $merchant = $this->getDbLastEntity('merchant');

        $isCouponApplied = (new Coupon\Core)->isCouponApplied($merchant, Coupon\Constants::MTU_COUPON);

        $this->assertFalse($isCouponApplied);
    }

    public function testNonRazorpayMerchantMtuCouponApplicationOnFirstTransaction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $this->fixtures->org->createHdfcOrg();

        $this->fixtures->on('live')->edit('merchant', $merchantId, ['org_id' => Org::HDFC_ORG]);

        $promotionAttributes = [
            'pricing_plan_id' => 'BAJq6FJDNJ4ZqD',
        ];

        $promotion = $this->fixtures->on('live')->create('promotion', $promotionAttributes);

        $couponAttributes = [
            'entity_id'   => $promotion->getId(),
            'entity_type' => 'promotion',
            'merchant_id' => '100000Razorpay',
            'code'        => Constants::MTU_COUPON
        ];

        $this->fixtures->on('live')->create('coupon', $couponAttributes);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP],
            StoreConstants::INTERNAL);

        $this->assertNull($data[StoreConfigKey::ENABLE_MTU_CONGRATULATORY_POPUP]);

        $merchant = $this->getDbLastEntity('merchant');

        $isCouponApplied = (new Coupon\Core)->isCouponApplied($merchant, Coupon\Constants::MTU_COUPON);

        $this->assertFalse($isCouponApplied);
    }

    public function testEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }

    public function testBlockMerchantActivation()
    {
        $core = new DetailCore();

        $merchant_id = '1X4hRFHFx4UiXt';

        $merchant = $this->fixtures->create('merchant', [
            'id' => $merchant_id
        ]);

        $splitzInput = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $response = $core->blockMerchantActivations($merchant);

        $this->assertFalse($response);

    }

    public function testBlockMerchantActivationForMalaysiaRegion()
    {
        $core = new DetailCore();

        $merchant_id = '1X4hRFHFx4UiXt';

        $merchant = $this->fixtures->create('merchant', [
            'id' => $merchant_id,
            'country_code' => 'MY'
        ]);

        $response = $core->blockMerchantActivations($merchant);

        $this->assertFalse($response);

    }

    public function testBlockMerchantActivationForBlacklisted()
    {
        $core = new DetailCore();

        $merchant_id = '1X4hRFHFx4UiXt';

        $merchant = $this->fixtures->create('merchant', [
            'id' => $merchant_id
        ]);

        $splitzInput = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => []
        ];

        $splitzBlackListInput = [
            'id'            => $merchant_id,
            'experiment_id' => "LJRBw7srZz3Psh",
            'request_data'  => json_encode(
                [
                    'merchant_id' => $merchant->getId(),
                ]),
        ];

        $splitzBlackListOutput = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $this->mockSplitzTreatment($splitzBlackListInput, $splitzBlackListOutput);

        Config::set('applications.test_case.execution', false);

        // this is to bypass production env check, so that flow reaches the code we want to test
        $this->app['env'] = "production";

        $response = $core->blockMerchantActivations($merchant);

        $this->assertTrue($response);

    }

    public function testBlockMerchantActivationForOptimiserOnlyMerchants()
    {
        $core = new DetailCore();

        $merchant_id = '1X4hRFHFx4UiXt';

        $merchant = $this->fixtures->create('merchant', [
            'id' => $merchant_id
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchant->getId(),
        ]);

        $splitzInput = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => []
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        Config::set('applications.test_case.execution', false);

        // this is to bypass production env check, so that flow reaches the code we want to test
        $this->app['env'] = "production";

        $featureParams = [
            Feature\Entity::ENTITY_ID   => $merchant['id'],
            Feature\Entity::ENTITY_TYPE => 'merchant',
            Feature\Entity::NAMES       => [Feature\Constants::OPTIMIZER_ONLY_MERCHANT],
            Feature\Entity::SHOULD_SYNC => false
        ];

        (new Feature\Service)->addFeatures($featureParams);

        $response = $core->blockMerchantActivations($merchant);

        $this->assertFalse($response);
    }

    public function testGetSegmentEventPropertiesForActivationStatusChangeTrue()
    {
        $core = new DetailCore();
        // toDoo
        // create a merchant who will be applicable for creating a payment handle
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_website' => 'www.google.com',

        ]);

        $merchant = $merchantDetails->merchant;

        $previousActivationStatus = $merchantDetails->getActivationStatus();

        $splitzInput = [
            "experiment_id" => "KDU9Zk7cp7SGQy",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $response = $core->getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $previousActivationStatus);
        // checking whether the splitz mock is working fine
        $this->assertArrayHasKey('product_led', $response);
    }

    public function testGetSegmentEventPropertiesForFundsOnHoldActivatedLive()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_website' => 'www.google.com',

        ]);

        $merchant = $merchantDetails->merchant;

        $previousActivationStatus = $merchantDetails->getActivationStatus();

        $splitzInput = [
            "experiment_id" => "KDU9Zk7cp7SGQy",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $response = $core->getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $previousActivationStatus);

        $this->assertArrayHasKey('product_led', $response);

        $this->assertArrayHasKey('activated', $response);

        $this->assertArrayHasKey('live', $response);

        $this->assertArrayHasKey('funds_on_hold', $response);
    }

    public function testSegmentEventPropertiesForMerchantNotProductLed()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_website' => 'www.google.com',
        ]);

        $merchant = $merchantDetails->merchant;

        $previousActivationStatus = $merchantDetails->getActivationStatus();

        $splitzInput = [
            "experiment_id" => "KDU9Zk7cp7SGQy",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $response = $core->getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $previousActivationStatus);

        $this->assertArrayHasKey('activation_status', $response);

        $this->assertArrayHasKey('mcc', $response);

        $this->assertArrayNotHasKey('product_led', $response);
    }

    public function testSegmentEventPropertiesForMerchantNCRevampEligible()
    {
        $core = new DetailCore();

        $this->fixtures->create('merchant',[
            'id' => 'HNhLp6FDNX0Ov5'
        ]);

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id' => 'HNhLp6FDNX0Ov5'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant('HNhLp6FDNX0Ov5');

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => 'HNhLp6FDNX0Ov5',
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('clarification_detail', [
            "merchant_id" => 'HNhLp6FDNX0Ov5',
            "group_name"  => "bank_details",
            "status"      => 'needs_clarification',
            "metadata"    => [
                'admin_email' => '123@gmail.com',
            ],
        ]);

        $merchant = $merchantDetails->merchant;

        $previousActivationStatus = $merchantDetails->getActivationStatus();

        $splitzInput = [
            "experiment_id" => "KDU9Zk7cp7SGQy",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => [
                "variant" => [
                    "name" => 'disable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $this->mockRazorxTreatment();

        $response = $core->getSegmentEventPropertiesforActivationStatusChange($merchant, $merchantDetails, $previousActivationStatus);

        $this->assertArrayHasKey('nc_fields', $response);

        $ncFields = $response['nc_fields'];

        $this->assertArrayHasKey('nc_count', $ncFields);

        $this->assertArrayHasKey('bank_details', $ncFields);

        $this->assertArrayHasKey('admin_email', $ncFields['bank_details']);

    }
    public function testEligibleForMtuPopupShowSignupCampaign()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['showMtuPopup']);
    }

    public function testEligibleForMtuPopupShowSignupSourceIos()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_source' => 'ios'
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['showMtuPopup']);
    }

    public function testEligibleForMtuPopupShowSignupSourceAndroid()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_source' => 'android'
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['showMtuPopup']);
    }

    public function testM2MNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $merchant=$this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $input = [
            M2MReferralEntity::MERCHANT_ID => $merchantId,
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];
        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }
    public function testNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDay()->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }

    public function testSubMerchantFalseCase()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['isSubMerchant']);
    }

    public function testSubMerchantTrueCase()
    {
        $merchantId = '1X4hRFHFx4UiXX';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $this->fixtures->create('merchant_access_map', ['merchant_id' => $merchantId]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertTrue($response['isSubMerchant']);
    }

    public function testNonRazorpayMerchantNotEligibleForMtuPopupShow()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $this->fixtures->org->createHdfcOrg();

        $merchantAttributes = [
            'id' => $merchantId,
            'activated' => 1,
            'live' => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp(),
            'org_id' => Org::HDFC_ORG
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
        ]);

        $response = (new Core)->createResponse($merchantDetail);

        $this->assertFalse($response['showMtuPopup']);
    }

    public function testSegmentEventPushForFirstTransactionWithUserDeviceDetail()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $this->fixtures->on('live')->create('user_device_detail', [
            'merchant_id' => $merchantDetail->getMerchantId()
        ]);

        $this->fixtures->on('live')->create('merchant_user', [
            'merchant_id' => $merchantDetail->getMerchantId()
        ]);

        $merchantId = $merchantDetail->getMerchantId();

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }

    public function testSegmentEventSkipIfNotFirstTransaction()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create transaction that is 4 days old (since cron picks last 3 days transacted merchants)
        $this->createTransaction(
            $merchantId, 'payment', 10000, Carbon::now()->subDays(4)->getTimestamp());
        $this->createPayment($merchantId, 10000, Carbon::now()->subDays(4)->getTimestamp());

        // Create new transaction
        $this->createTransaction($merchantId, 'payment', 10000);
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }

    public function testSegmentEventIfTwoTransactionsDuringSameTime()
    {
        $this->createAndFetchMocks();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        // Create new 2 transactions
        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());
        $this->createPayment($merchantId, 10000);

        (new Escalations\Core)->handleMtuCouponApply();
    }


    public function testValidationFieldsIfAadhaarEsignVerificationIsDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
            'aadhaar_esign_status'                 => 'verified'
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertFalse(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarEsignVerificationIsNotDone()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $mid,
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testValidationFieldsIfAadhaarIsNotLinked()
    {
        $merchantDetail = $this->fixtures->create('merchant_detail:valid_fields', [
            'business_type'                         => '11',
        ]);

        $mid = $merchantDetail->getId();

        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'         => $mid,
            'aadhaar_linked'      => 0
        ]);

        $mocks = $this->createAndFetchMocks();
        $core = new DetailCore();
        $core->setMerchantCoreForRazorx($mocks['merchantCoreMock']);

        [$validationFields, $validationSelectiveRequiredFields, $validationOptionalFields] = $core->getValidationFields($merchantDetail);

        $this->assertTrue(isset($validationSelectiveRequiredFields[SelectiveRequiredFields::POA_DOCUMENTS]));
    }

    public function testCouponFlowForInvalidInput()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage("The code field is required");

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $input = [];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForInvalidCoupon()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_COUPON_CODE);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $input = [
            'code' => "XYZ"
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForCouponCodeAlreadyUsed()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_COUPON_ALREADY_USED);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        $this->fixtures->on('live')->create('merchant_promotion', [
            'merchant_id'           => $merchant->getId(),
            'promotion_id'          => $promotion->getId(),
            'start_time'            => time(),
            'remaining_iterations'  => 1,
            'expired'               => 0
        ]);

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForCouponCodeLimitReached()
    {
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_COUPON_LIMIT_REACHED);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $coupon->setAttribute('used_count', 200);

        $coupon->saveOrFail();

        $input = [
            'code' => $couponCode
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForInvalidCreditType()
    {
        $this->expectException(BadRequestException::class);

        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_ONLY_AMOUNT_CREDITS_COUPON_APPLICABLE);

        $merchant = $this->fixtures->create('merchant');

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
            'credit_type'    => 'reward_fee',
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        (new MDS())->postApplyCoupon($input);
    }

    public function testCouponFlowForSuccessCase()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode = 'randomXYZ';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode
        ];

        $this->fixtures->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());
    }

    public function testCouponFlowForExistingCredits()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode1 = 'randomXYZ1';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode1
        ];

        $balance = $this->fixtures->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());

        $couponCode2 = 'randomXYZ2';

        $promotion = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode2,
            'product'        => 'primary',
            'credit_amount'  => 50,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode2,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode2
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($response['data']['available_credits'], 1000);
    }

    public function testCouponFlowForForceExpireExistingCredits()
    {
        $merchant = $this->fixtures->on('live')->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $couponCode1 = 'randomXYZ1';

        $promotion1 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 1000,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput1 = [
            "entity_id"     => "prom_".$promotion1->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput1);

        $input = [
            'code' => $couponCode1
        ];

        $balance = $this->fixtures->on('live')->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId()
        ]);

        $primaryBalance = $this->getDbEntityById('merchant', $merchant->getId())->primaryBalance;

        $this->assertEquals(0, $primaryBalance->getAmountCredits());

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);

        $this->assertEquals(1000, $primaryBalance->reload()->getAmountCredits());

        $couponCode2 = 'randomXYZ2';

        $promotion2 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode2,
            'product'        => 'primary',
            'credit_amount'  => 20,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput = [
            "entity_id"     => "prom_".$promotion2->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode2,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput);

        $input = [
            'code' => $couponCode2
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($response['data']['available_credits'], 1000);

        $token = $response['token'];

        $input = [
            'code'  => $couponCode2,
            'token' => $token
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);
        $this->assertEquals(20, $primaryBalance->reload()->getAmountCredits());
    }

    public function testCouponFlowForExistingCreditsNotThroughCoupon()
    {
        $merchant = $this->fixtures->create('merchant',[
            'activated' => 1,
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $existingAmountCredits = 20000;

        $credits = $this->fixtures->on('live')->create('credits', [
            'merchant_id'  => $merchant->getId(),
            'value'        => $existingAmountCredits,
            'used'         => 0,
            'campaign'     => 'OFFERMTU2',
            'type'         => 'amount',
            'promotion_id' => null,
            'expired_at'   => Carbon::now()->addYear()->timestamp,
        ]); //amount credits not through coupon flow

        $merchantBalance = $this->fixtures->on('live')->create('balance', [
            'id'            => '100def000def00',
            'balance'       => 0,
            'type'          => 'primary',
            'merchant_id'   => $merchant->getId(),
            'credits'       => $existingAmountCredits
        ]);

        $this->assertEquals($existingAmountCredits, $merchantBalance->getAmountCredits());

        $couponCode1 = 'randomXYZ1';

        $promotion1 = $this->fixtures->on('live')->create('promotion', [
            'name'           => $couponCode1,
            'product'        => 'primary',
            'credit_amount'  => 999,
            'iterations'     => 1,
            'credits_expire' => 0,
        ]);

        $couponInput1 = [
            "entity_id"     => "prom_".$promotion1->getId(),
            "entity_type"   => "promotion",
            "code"          => $couponCode1,
            "max_count"     => "200",
        ];

        $coupon = (new Coupon\Core())->create($couponInput1);

        $input = [
            'code' => $couponCode1
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertFalse($response['applied']);
        $this->assertEquals($existingAmountCredits, $response['data']['available_credits']);

        $token = $response['token'];

        $input = [
            'code'  => $couponCode1,
            'token' => $token
        ];

        $response = (new MDS())->postApplyCoupon($input);

        $this->assertTrue($response['applied']);
        $this->assertEquals(999, $merchantBalance->reload()->getAmountCredits());
    }

    public function testBusinessRegisteredStateCodeValidation()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'Maharashtra'
        ];

        (new DetailEntity)->build($input);
    }

    public function testIsMerchantTncApplicableSuccess()
    {
        $core = new DetailCore();

        $merchantId = '2cXSLlUU8V9sXl';

        $this->fixtures->create('org',[
            'id' => ORG_ENTITY::AXIS_ORG_ID,
        ]);

        $merchant = $this->fixtures->create('merchant',[
            'org_id'      => ORG_ENTITY::AXIS_ORG_ID,
            'id'          => $merchantId,
        ]);

        $this->fixtures->create('merchant_detail',[
            'merchant_id'      => $merchant->getId(),
        ]);

        $this->mockRazorxTreatment();

        $isMerchantTncApplicable = (new WebsiteService)->isMerchantTncApplicable($merchant);

        $this->assertEquals(true, $isMerchantTncApplicable);
    }

    public function testBusinessRegisteredStateCodeValidationOnInvalid2DigitCode()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'XT'     // invalid state code
        ];

        (new DetailEntity)->build($input);
    }

    public function testBusinessRegisteredStateCodeValidationSuccess()
    {
        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'MH'     // valid state code
        ];

        $merchantDetail = (new DetailEntity)->build($input);

        $this->assertEquals($merchantDetail->getBusinessRegisteredState(), 'MH');
    }

    public function testActivationProgressAfterFirstLogin()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(10, $response['verification']['activation_progress']);
    }

    public function testGroupBankDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupBankDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupBankDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupPromoterPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedPromoterPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedPromoterPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testGroupCompanyPanDetails()
    {
        $core = new DetailCore();

        $testData = $this->testData['testGroupedCompanyPanDetailsInput'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $output= $core->getUpdatedKycClarificationReasons(
            [],
            $merchantDetails->getId()
        );

        $expectedOutput = $this->testData['testGroupedCompanyPanDetailsOutput'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testAddToExistingClarificationReasonV2()
    {
        $core = new DetailCore();

        $testData = $this->testData['existingClarificationReasonV2Data'];

        $merchantDetails = $this->fixtures->create('merchant_detail',$testData);

        $newClarificationReasonV2 = $this->testData['newClarificationReasonV2Data'];

        $fixedTime = (new Carbon())->timestamp(1583548200);

        Carbon::setTestNow($fixedTime);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'needs_clarification'
        ]);

        $this->fixtures->create('state', [
            'entity_id'   =>  $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'under_review'
        ]);

        $output= $core->getUpdatedKycClarificationReasons(
            $newClarificationReasonV2,
            $merchantDetails->getId(),
            DetailConstant::ADMIN
        );

        $expectedOutput = $this->testData['updatedClarificationReasonV2Output'];

        $this->assertEquals($expectedOutput, $output);
    }

    public function testActivationProgressL1Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L1'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(60, $response['verification']['activation_progress']);
    }

    public function testActivationProgressL2Filled()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category' => 'financial_services',
            'business_subcategory' => 'accounting',
            'activation_form_milestone' => 'L2'
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(80, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivatedMCCPending()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated_mcc_pending',
        ]);

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'entity_type' => 'merchant_detail',
            'name'        => 'activated_mcc_pending',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(90, $response['verification']['activation_progress']);
    }

    public function testActivationProgressActivated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'activation_status'         => 'activated',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(100, $response['verification']['activation_progress']);
    }

    public function testActivationProgressTncGenerated()
    {
        $this->mockRazorxTreatment();

        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
        ]);

        $this->fixtures->create('merchant_website', [
            'merchant_id'           => $merchantDetails->getId(),
            'deliverable_type'      => 'services',
            'shipping_period'       => '2 hours',
            'refund_request_period' => '29 days',
            'refund_process_period' => '1 day',
        ]);

        $merchant = $merchantDetails->merchant;

        $response = $core->setVerificationDetails($merchantDetails, $merchant, []);

        $this->assertEquals(85, $response['verification']['activation_progress']);
    }

    public function testL2RequiresPOI()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(false, $response);
    }

    public function testL2RequiresPoiVerificationPending()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'pending',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA1234J']);

        $this->assertEquals(true, $response);
    }

    public function testL2RequiresPoiNew()
    {
        $core = new DetailCore();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J'
        ]);

        $response = $core->isPOIVerificationRequiredForL2($merchantDetails, ['promoter_pan'=>'AAAPA6969J']);

        $this->assertEquals(true, $response);
    }

    public function testApplicableActivationStatusForRiskyMerchant()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'risk_review_suspend');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    // Below test case is to check that the merchant(unregistered) should not go from nc to amp
    // if he has been in Nc already

    public function testGetApplicableActivationStatusForUnRegisteredMerchantInNeedsClarification()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'needs_clarification',
        ]);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'name'        => 'needs_clarification',
            'entity_type' => 'merchant_detail'
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    // Below testcase is to check merchant should not go in under review
    // state if it is rejected from merchant auth
    public function testUpdateActivationStatusForRejectedToUnderReviewMerchants()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $merchantDetails      = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'blacklist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'rejected',
            'submitted'                 => true,
            'business_website'          => null
        ]);
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Rejected merchants are not allowed to submit activation form');

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }

    public function testUpdateActivationStatusToUnderReviewMerchantsActivationFormLocked()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $merchantDetails      = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'needs_clarification',
            'submitted'                 => true,
            'business_website'          => null
        ]);
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $this->ba->adminAuth();

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->assertEquals(false, $merchantDetails->islocked());

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $merchantDetailData['locked']);

    }

    // Below test case is to check that the merchant(registered) should not go from nc to amp
    // if he has been in Nc already

    public function testGetApplicableActivationStatusForRegisteredMerchantInNeedsClarification()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'name'        => 'needs_clarification',
            'entity_type' => 'merchant_detail'
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    // Below test case is to check that the merchant(registered) should not go from ur to amp
    // if he has been in Nc already once

    public function testGetApplicableActivationStatusForRegisteredMerchantInUnderReview()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'name'        => 'needs_clarification',
            'entity_type' => 'merchant_detail'
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    // Below test case is to check that the merchant(unregistered) should not go from ur to amp
    // if he has been in Nc already

    public function testGetApplicableActivationStatusForUnRegisteredMerchantInUnderReview()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->fixtures->create('state', [
            'entity_id'   => $merchantDetails->getId(),
            'name'        => 'needs_clarification',
            'entity_type' => 'merchant_detail'
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testApplicableActivationStatusForNonRiskyMerchant()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
        ]);

        $merchant = $merchantDetails->merchant;
        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testApplicableActivationStatus()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testActivatedMccPendingActivationStatus()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);


    }

    public function testActivatedMccPendingActivationStatusPartnership()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
                         'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);

        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
    }

    public function testGetApplicableActivationStatusMccAdditionalDoc()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'others',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchantDetails->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsent()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsAbsent()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchantDetails->getId());

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsPresent()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchant->getId());

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteNegativeKeywordFail()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteNegativeKeywordFailAviation()
    {

        // This testcase was added as a part of-
        // Do not activate when MCC is present in the list (exclusion method)

        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'aviation',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '4511',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteNegativeKeywordFailOthers()
    {

        // This testcase was added as a part of-
        // Do not activate when MCC is present in the list (exclusion method)

        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'others',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '4511',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        // negative keyword check has failed => under review
        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteActivated()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'education',
                'subcategory'       => 'college',
                'predicted_mcc'     => 8220,
                'confidence_score'  => 0.83
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsitePolicyFail()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $detailCoreMock->expects($this->once())
            ->method('updateActivationStatus')
            ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'initiated',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'initiated',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'social',
                'subcategory'       => 'matchmaking',
                'predicted_mcc'     => 7273,
                'confidence_score'  => 0.99
            ]
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'validation_unit'   => 'identifier',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'success'
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $detailCoreMock->saveMerchantDetails($input, $merchant);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'website_policy'
        ]);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        // here we expect website policy to fail because the result stored in metadata does not contain all links i.e. privacy, terms, contact_us, refund, shipping.
        $this->assertEquals('failed', $verificationData['status']);

        // website policy check has failed => under review
        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusMccFail()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $detailCoreMock->expects($this->once())
            ->method('updateActivationStatus')
            ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'failed',
            'metadata'             => [
                'error_code'    => 'EXTERNAL_VENDOR_ERROR',
                'error_reason'  => "Google NLP Couldn't classify",
                'status'        => 'failed'
            ]
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'validation_unit'   => 'identifier',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'success'
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $detailCoreMock->saveMerchantDetails($input, $merchant);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'website_policy'
        ]);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusMccCategFail()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'failed',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'education',
                'subcategory'       => 'college',
                'predicted_mcc'     => 8220,
                'confidence_score'  => 0.83
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteActivatedPilot()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'education',
                'subcategory'       => 'college',
                'predicted_mcc'     => 8220,
                'confidence_score'  => 0.83
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'pilot',
                ]
            ]
        ];

        $this->createSignatoryVerified($merchant->getId());

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::ACTIVATED, $businessDetail['metadata']['activation_status']);
    }

    public function testGetApplicableActivationStatusWebsiteAbsentPilot()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'pilot',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $businessDetail = $this->getDbLastEntity('merchant_business_detail');

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $businessDetail['metadata']['activation_status']);
    }

    public function testActivatedMccPendingActivationStatusCOI()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 6,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 =>true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
            'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);
    }

    public function testFetchVerificationErrorCodesNoArtefactMatch()
    {
        // when no records are matched for unsupported  artefact type
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::VOTERS_ID,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesNoErrorRecords()
    {
        // when no records are matched for supported artefact type
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::SUCCESS
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesMatchDescriptionStatusFailed()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'NO_PROVIDER_ERROR',
            BVSEntity::ERROR_DESCRIPTION => 'input document does not match  AadhaarBack document'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [Entity::POA_VERIFICATION_STATUS => 'AADHAAR_BACK_NOT_MATCHED'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesEmptyDescription()
    {
        // when records are found and error description does not exist.
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'DOCUMENT_UNIDENTIFIABLE',
            BVSEntity::ERROR_DESCRIPTION => ''
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        // output falls back to the error code
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesNotMatchDescription()
    {
        // when records are found and error description is not defined in map
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ], [], [
            BVSConstants::ARTEFACT_TYPE => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE => 'INPUT_DATA_ISSUE',
            BVSEntity::ERROR_DESCRIPTION => 'UNDEFINED IN MAP'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        // output falls back to the error code
        $this->assertEmpty($error_codes);
    }

    public function testFetchVerificationErrorCodesEmptyDescriptionBankAccount()
    {
        // when no error is to be shown to the user
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS    => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE           => 'NO_PROVIDER_ERROR',
            BVSEntity::ERROR_DESCRIPTION    => 'KC04: Amount Limit Exceeded'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesMatchDescriptionStatusFailedBankAccount()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS    => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE           => 'INPUT_DATA_ISSUE',
            BVSEntity::ERROR_DESCRIPTION    => 'KC03: Invalid Beneficiary Account Number or IFSC'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [Entity::BANK_DETAILS_VERIFICATION_STATUS => 'INVALID_BENEFICIARY_NUMBER_OR_IFSC'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesStatusNotMatchedBankAccount()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::BANK_ACCOUNT,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS    => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE           => 'RULE_EXECUTION_FAILED',
            BVSEntity::ERROR_DESCRIPTION    => ''
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [Entity::BANK_DETAILS_VERIFICATION_STATUS => 'NOT_MATCHED'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testActivatedMccPendingActivationStatusTrust()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 9,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 =>true,
            'business_Website'          => null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        //verify email has been sent
        $expectedEmails=['emails.merchant.onboarding.activated_mcc_pending_success',
            'emails.merchant.onboarding.activated_mcc_pending_action_required'];

        $queuedEmails =Mail::queued(MerchantOnboardingEmail::class);
        $this->assertCount(2,$queuedEmails);
        $this->assertContains($queuedEmails->get(0)->getTemplate(),$expectedEmails);
        $this->assertContains($queuedEmails->get(1)->getTemplate(),$expectedEmails);

    }


    public function testM2MOfferMtuCronJob()
    {
        $this->createAndFetchMocks();
        $this->mockRazorxTreatment();

        $merchant = $this->repo->merchant->findorfail('10000000000011');
        $input    = [
            M2MReferralEntity::MERCHANT_ID => '10000000000011',
            M2MReferralEntity::STATUS      => M2MEntityStatus::MTU_EVENT_SENT
        ];

        $m2m = (new \RZP\Models\Merchant\M2MReferral\Core())->createM2MReferral($merchant, $input);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(0))
                    ->method('pushIdentifyAndTrackEvent');


        $this->dontExpectAnyStorkServiceRequest();

        (new CronJobHandler\Core())->handleCron(CronConstants::FIRST_PAYMENT_OFFER_DAILY_NOTIFICATION, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

    }

    public function testCreatePayloadForDedupeCheckForGstin ()
    {
        $detailCore = (new DetailCore());

        $reflection = new ReflectionClass($detailCore);

        $method = $reflection->getMethod('prepareRequestForNoDocDedupeCheck');
        $method->setAccessible(true);

        $fieldMap = [
            'gstin' => ['ABCDEFGH']
        ];

        $expectedpayload =[
            'field' => 'gstin',
            'list' => 'xpress-onboarding',
            'value' => 'ABCDEFGH'
        ];

        $noDocConfig = [];

        $result = $method->invokeArgs($detailCore, [$fieldMap, & $noDocConfig]);

        $this->assertEquals ($expectedpayload['value'], $result[0]['value']);
    }


    public function testFirstPaymentOfferCommunicationJob()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantId = '1X4hRFHFx4UiXt';

        $merchantAttributes = [
            'id'           => $merchantId,
            'activated'    => 1,
            'live'         => 1,
            'activated_at' => Carbon::now()->subDays(2)->getTimestamp()
        ];

        $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'    => $merchantId,
            'contact_mobile' => '9980004017'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantId,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->setMethods(['pushIdentifyAndTrackEvent'])
                            ->getMock();

        $this->app['rzp.mode'] = Mode::LIVE;
        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
                    ->method('pushIdentifyAndTrackEvent')
                    ->will($this->returnCallback(function($merchant, $properties, $eventName) {
                        $this->assertNotNull($properties);
                        $this->assertTrue(in_array($eventName, [SegmentEvent::OFFERMTU_TARGETED_MERCHANT], true));
                    }));

        $this->expectAnyStorkServiceRequest();

        (new CronJobHandler\Core())->handleCron(CronConstants::FIRST_PAYMENT_OFFER_DAILY_NOTIFICATION, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);
    }

    public function testTriggerOcrServiceForMccCategorisationSuccess()
    {
        Config::set('services.ocr_service.mock', true);

        Config::set('services.response', 'success');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService($ocrInput, 'mcc_categorisation');

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'      => $merchantDetails->getId(),
                                             'owner_type'    => 'merchant',
                                             'artefact_type' => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'         => $merchantDetails->getId(),
                                                'artefact_identifier' => 'number',
                                                'artefact_type'       => 'mcc_categorisation_website']);

        $this->assertNotNull($bvsValidation);

        $this->assertEquals('captured', $bvsValidation['validation_status']);

        $this->assertNotNull($verificationData);

        $this->assertEquals('initiated', $verificationData['status']);

    }

    public function testTriggerOcrServiceForMccCategorisationFailure()
    {
        Config::set('services.ocr_service.mock', true);

        Config::set('services.response', 'failure');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService($ocrInput, 'mcc_categorisation');

        $bvsValidation = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchantDetails->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $this->assertNull($bvsValidation);

        $this->assertNull($verificationData);
    }

    public function testMccEventResponseSuccessWithConfidenceScoreAboveThreshold()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'         => 'financial_services',
                        'subcategory'      => 'trading',
                        'predicted_mcc'    => 6211,
                        'confidence_score' => 0.91,
                        'status'           => 'completed'
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
                                            ['owner_id'        => $merchantDetails->getId(),
                                             'owner_type'      => 'merchant',
                                             'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $this->assertEquals('success', $newBvsValidationData['validation_status']);

        $this->assertEquals('verified', $verificationData['status']);
    }

    public function testOCRPassedActivatedSplitzLive()
    {
        $this->markTestSkipped('need to mock activate and pricing calls for this test to succeed');

        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'bank_details_doc_verification_status' =>  null,
            Entity::CIN_VERIFICATION_STATUS        => 'verified',
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'metadata'             => [
                'policy_details_file' => 'file_LSAa41ZugJ1BPj',
                'terms' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/termsofuse'
                        ],
                        'confidence_score' => 0.5775,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'refund' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/refundpolicy'
                        ],
                        'confidence_score' => 0.6185,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'privacy' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/privacypolicy'
                        ],
                        'confidence_score' => 0.70671977996826,
                        'relevant_details' => [
                            'note' => 'Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,'
                        ],
                        'validation_result' => true
                    ]
                ],
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/contactus'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ],
                'shipping' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/shipping'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ]
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'metadata'             => [
                'policy_details_file' => 'file_LSAa41ZugJ1BPj',
                'terms' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/termsofuse'
                        ],
                        'confidence_score' => 0.5775,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'refund' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/refundpolicy'
                        ],
                        'confidence_score' => 0.6185,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'privacy' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/privacypolicy'
                        ],
                        'confidence_score' => 0.70671977996826,
                        'relevant_details' => [
                            'note' => 'Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,'
                        ],
                        'validation_result' => true
                    ]
                ],
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/contactus'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ],
                'shipping' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/shipping'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ]
            ]
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'         => 'ecommerce',
                        'subcategory'      => 'arts_and_collectibles',
                        'predicted_mcc'    => 5971,
                        'confidence_score' => 0.91,
                        'status'           => 'completed'
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $kafkaEventPayload = [
            'data' => [
                "website_verification_id" => "L61kGPVWKT05Qx",
                "status" => "completed",
                "verification_result" => [
                    "terms" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/termsofuse"],
                            "confidence_score" => 0.5775,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "refund" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/refundpolicy"],
                            "confidence_score" => 0.6185,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "privacy" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/privacypolicy"],
                            "confidence_score" => 0.7067197799682617,
                            "relevant_details" => [
                                "note" =>
                                    "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,",
                            ],
                            "validation_result" => true,
                        ],
                    ],
                    "contact_us" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/contactus"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                    "shipping" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/shipping"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-website-verification-notification-events', $kafkaEventPayload, 'test');

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "success",
                "document_details" => [
                    "result" => [
                        "prohibited" => [
                            "drugs" => [
                                "Phrases" => [
                                    "cannabis" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "financial services" => [
                                "Phrases" => [
                                    "investment" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "miscellaneous" => [
                                "Phrases" => [
                                    "cash" => 0,
                                    "cigarette" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "pharma" => [
                                "Phrases" => [
                                    "alcohol" => 0,
                                    "cannabinoid" => 0,
                                    "codeine" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "tobacco products" => [
                                "Phrases" => [
                                    "tobacco" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "travel" => [
                                "Phrases" => [
                                    "booking" => 0,
                                    "travel" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ]
                        ],
                        "required" => [
                            "policy disclosure" => [
                                "Phrases" => [
                                    "cancellations" => 1,
                                    "claims" => 11,
                                    "contact us" => 1,
                                    "delivery" => 46,
                                    "payment" => 4,
                                    "payments" => 2,
                                    "privacy" => 5,
                                    "privacy policy" => 8,
                                    "refund" => 6,
                                    "refund policy" => 4,
                                    "refunds" => 1,
                                    "return" => 3,
                                    "return policy" => 2,
                                    "returns" => 3,
                                    "terms of service" => 1
                                ],
                                "total_count" => 98,
                                "unique_count" => 15
                            ],
                        ],
                    ],
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $this->createSignatoryVerified($merchant->getId());

        Queue::assertPushed(UpdateMerchantContext::class);

        (new UpdateMerchantContext(Mode::LIVE, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
                                                   ['owner_id'        => $merchantDetails->getId(),
                                                    'owner_type'      => 'merchant',
                                                    'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $merchantData = $this->getDbEntity('merchant',
                                            ['id' => $merchantDetails->getId()]);

        $merchantDetailsData =  $this->getDbEntity('merchant_detail',
                                                   ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals('success', $newBvsValidationData['validation_status']);

        $this->assertEquals('verified', $verificationData['status']);

        $this->assertEquals('5971', $merchantData['category']);

        $this->assertEquals('ecommerce', $merchantData['category2']);

        $this->assertEquals('ecommerce', $merchantDetailsData['business_category']);

        $this->assertEquals('arts_and_collectibles', $merchantDetailsData['business_subcategory']);

        $this->assertEquals('activated', $merchantDetailsData['activation_status']);

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals([
            'terms' =>  ['url' => "https://www.sukhdev.org/termsofuse"],
            'refund' =>  ['url' => "https://www.sukhdev.org/refundpolicy"],
            'privacy' =>  ['url' => "https://www.sukhdev.org/privacypolicy"],
            'contact_us' =>  ['url' => "https://www.sukhdev.org/contactus"],
            'shipping' =>  ['url' => "https://www.sukhdev.org/shipping"],
        ], $merchantWebsiteDetail['admin_website_details']['website']['https://www.sukhdev.org']);
    }

    public function testOCRPassedActivatedPilot()
    {
        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'pilot',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'bank_details_doc_verification_status' =>  null,
            Entity::CIN_VERIFICATION_STATUS        => 'verified',
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'         => 'ecommerce',
                        'subcategory'      => 'arts_and_collectibles',
                        'predicted_mcc'    => 5971,
                        'confidence_score' => 0.91,
                        'status'           => 'completed'
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $kafkaEventPayload = [
            'data' => [
                "website_verification_id" => "L61kGPVWKT05Qx",
                "status" => "completed",
                "verification_result" => [
                    "terms" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/termsofuse"],
                            "confidence_score" => 0.5775,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "refund" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/refundpolicy"],
                            "confidence_score" => 0.6185,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "privacy" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/privacypolicy"],
                            "confidence_score" => 0.7067197799682617,
                            "relevant_details" => [
                                "note" =>
                                    "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,",
                            ],
                            "validation_result" => true,
                        ],
                    ],
                    "contact_us" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/contactus"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                    "shipping" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/shipping"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-website-verification-notification-events', $kafkaEventPayload, 'test');

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "success",
                "document_details" => [
                    "result" => [
                        "prohibited" => [
                            "drugs" => [
                                "Phrases" => [
                                    "cannabis" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "financial services" => [
                                "Phrases" => [
                                    "investment" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "miscellaneous" => [
                                "Phrases" => [
                                    "cash" => 0,
                                    "cigarette" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "pharma" => [
                                "Phrases" => [
                                    "alcohol" => 0,
                                    "cannabinoid" => 0,
                                    "codeine" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "tobacco products" => [
                                "Phrases" => [
                                    "tobacco" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "travel" => [
                                "Phrases" => [
                                    "booking" => 0,
                                    "travel" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ]
                        ],
                        "required" => [
                            "policy disclosure" => [
                                "Phrases" => [
                                    "cancellations" => 1,
                                    "claims" => 11,
                                    "contact us" => 1,
                                    "delivery" => 46,
                                    "payment" => 4,
                                    "payments" => 2,
                                    "privacy" => 5,
                                    "privacy policy" => 8,
                                    "refund" => 6,
                                    "refund policy" => 4,
                                    "refunds" => 1,
                                    "return" => 3,
                                    "return policy" => 2,
                                    "returns" => 3,
                                    "terms of service" => 1
                                ],
                                "total_count" => 98,
                                "unique_count" => 15
                            ],
                        ],
                    ],
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $this->createSignatoryVerified($merchant->getId());

        Queue::assertPushed(UpdateMerchantContext::class);

        (new UpdateMerchantContext(Mode::LIVE, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
                                                   ['owner_id'        => $merchantDetails->getId(),
                                                    'owner_type'      => 'merchant',
                                                    'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $this->assertEquals('success', $newBvsValidationData['validation_status']);

        $this->assertEquals('verified', $verificationData['status']);

        $businessDetail = $this->getDbEntity('merchant_business_detail', [
            'merchant_id'        => $merchantDetails->getId()
        ]);

        $this->assertEquals('activated', $businessDetail['metadata']['activation_status']);
    }

    public function testMccEventResponseSuccessWithConfidenceScoreBelowThreshold()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'         => 'financial_services',
                        'subcategory'      => 'trading',
                        'predicted_mcc'    => 6211,
                        'confidence_score' => 0.88,
                        'status'           => 'completed'
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
                                                   ['owner_id'        => $merchantDetails->getId(),
                                                    'owner_type'      => 'merchant',
                                                    'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $this->assertEquals('success', $newBvsValidationData['validation_status']);

        $this->assertEquals('failed', $verificationData['status']);

    }

    public function testMccEventResponseFailure()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'error_code'    => 'EXTERNAL_VENDOR_ERROR',
                        'error_reason'  => "Google NLP Couldn't classify",
                        'status'        => 'failed'

                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
                                                   ['owner_id'        => $merchantDetails->getId(),
                                                    'owner_type'      => 'merchant',
                                                    'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
                                               ['merchant_id'          => $merchantDetails->getId(),
                                                'artefact_identifier'  => 'number',
                                                'artefact_type'        => 'mcc_categorisation_website']);

        $this->assertEquals('failed', $newBvsValidationData['validation_status']);

        $this->assertEquals('failed', $verificationData['status']);
    }

    public function testTerminalCreationForRegularMerchant()
    {
        Mail::fake();

        $this->mockRazorxTreatment();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'          => 'under_review',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $kafkaProducerMock = Mockery::mock(KafkaProducerClientMock::class)->makePartial();

        $this->app->instance('kafkaProducerClient', $kafkaProducerMock);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
                    ->method('pushTrackEvent')
                    ->will($this->returnCallback(function($merchant, $eventAttributes, $eventName) {
                        $this->assertTrue(array_key_exists("merchant_id", $eventAttributes));
                        $this->assertTrue(array_key_exists("event_timestamp", $eventAttributes));
                        $this->assertTrue(array_key_exists("type", $eventAttributes));
                        $this->assertTrue(in_array($eventName, ["UPI Wrapper Requested"], true));
                    }));

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        $kafkaProducerMock->shouldHaveReceived('produce');

        $methods = $this->getDbEntityById('methods', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(false, $methods['upi']);
    }

    public function testTerminalCreationForRegularMerchantFromAMPToActivated()
    {
        Mail::fake();

        $this->mockRazorxTreatment();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'activated_mcc_pending',
            'submitted'=>true,
            'business_Website'=> null
        ]);

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $kafkaProducerMock = Mockery::mock(KafkaProducerClientMock::class)->makePartial();

        $this->app->instance('kafkaProducerClient', $kafkaProducerMock);

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setConstructorArgs([$this->app])
                            ->getMock();

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->exactly(1))
                    ->method('pushTrackEvent')
                    ->will($this->returnCallback(function($merchant, $eventAttributes, $eventName) {
                        $this->assertTrue(array_key_exists("merchant_id", $eventAttributes));
                        $this->assertTrue(array_key_exists("event_timestamp", $eventAttributes));
                        $this->assertTrue(array_key_exists("type", $eventAttributes));
                        $this->assertTrue(in_array($eventName, ["UPI Wrapper Requested"], true));
                    }));

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData,$merchantDetails->merchant);

        $kafkaProducerMock->shouldHaveReceived('produce');

        $methods = $this->getDbEntityById('methods', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(false, $methods['upi']);
    }

    public function testTerminalCreationForNonRegularMerchant()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $kafkaProducerMock = $this->getMockBuilder(KafkaProducerClient::class)
                                  ->onlyMethods(['produce'])
                                  ->getMock();

        $this->app->instance('kafkaProducerClient', $kafkaProducerMock);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $kafkaProducerMock->expects($this->exactly(0))->method('produce');

        $methods = $this->getDbEntityById('methods', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $methods['upi']);
    }

    public function testFtuxDashboardKeysOnFirstTransaction()
    {
        $this->enableRazorXTreatmentForRazorX();

        $merchantDetail = $this->fixtures->on('live')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $prestoService = $this->getMockBuilder(DataLakePrestoMock::class)
                              ->setConstructorArgs([$this->app])
                              ->onlyMethods([ 'getDataFromDataLake'])
                              ->getMock();

        $this->app->instance('datalake.presto', $prestoService);

        $prestoServiceData = [
            [
                'merchant_id' => $merchantId,
            ]
        ];

        $prestoService->method( 'getDataFromDataLake')
                      ->willReturn($prestoServiceData);

        $this->createTransaction($merchantId, 'payment', 10000, Carbon::now()->subHour()->getTimestamp());

        $this->createPayment($merchantId, 10000);

        (new CronJobHandler\Core())->handleCron(CronConstants::MTU_TRANSACTED_MERCHANTS_CRON_JON_NAME, [
            "start_time" => Carbon::now()->subDecade()->getTimestamp(),
            "end_time"   => Carbon::now()->getTimestamp(),
        ]);

        $showFtuxFinalScreenData = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::SHOW_FTUX_FINAL_SCREEN],
            StoreConstants::PUBLIC);

        $showFirstPaymentBannerData = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::SHOW_FIRST_PAYMENT_BANNER],
            StoreConstants::PUBLIC);

        $this->assertTrue($showFtuxFinalScreenData[StoreConfigKey::SHOW_FTUX_FINAL_SCREEN]);

        $this->assertTrue($showFirstPaymentBannerData[StoreConfigKey::SHOW_FIRST_PAYMENT_BANNER]);
    }

    public function testTriggerOcrServiceForWebsitePolicySuccess()
    {
        Config::set('services.ocr_service.mock', true);

        Config::set('services.response', 'success');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);


        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService($ocrInput, 'website_policy');

        $bvsValidation = $this->getDbEntity('bvs_validation', [
            'owner_id'        => $merchantDetails->getId(),
            'owner_type'      => 'merchant',
            'artefact_type'   => 'website_policy'
        ]);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'website_policy'
        ]);

        $this->assertNotNull($bvsValidation);

        $this->assertEquals('captured', $bvsValidation['validation_status']);

        $this->assertNotNull($verificationData);

        $this->assertEquals('initiated', $verificationData['status']);
    }

    public function testTriggerOcrServiceForWebsitePolicyFailure()
    {
        Config::set('services.ocr_service', true);

        Config::set('services.response', 'failure');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService($ocrInput, 'website_policy');

        $bvsValidation = $this->getDbEntity('bvs_validation',
            ['owner_id'        => $merchantDetails->getId(),
                'owner_type'      => 'merchant',
                'artefact_type'   => 'website_policy']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
            ['merchant_id'          => $merchantDetails->getId(),
                'artefact_identifier'  => 'number',
                'artefact_type'        => 'website_policy']);

        $this->assertNull($bvsValidation);

        $this->assertNull($verificationData);
    }

    public function testUpdateActivationStatusFromAMPToKQU()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchantDetails->getMerchantId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('kyc_qualified_unactivated', $merchantDetailData['activation_status']);
    }

    public function testUpdateActivationStatusFromURToKQU()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(false);

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchantDetails->getMerchantId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('kyc_qualified_unactivated', $merchantDetailData['activation_status']);
    }

    public function testTriggerOcrServiceForNegativeKeywordsSuccess()
    {
        Config::set('services.ocr_service.mock', true);

        Config::set('services.response', 'success');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);


        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService([
            'owner_id'                                  => $merchantDetails->getId(),
            'platform'                                  => Constant::PG,
            Constant::DOCUMENT_TYPE                     => Constant::SITE_CHECK,
            Constant::DETAILS                           => $ocrInput
        ], Constant::NEGATIVE_KEYWORDS);;

        $bvsValidation = $this->getDbEntity('bvs_validation', [
            'owner_id'        => $merchantDetails->getId(),
            'owner_type'      => 'merchant',
            'artefact_type'   => 'negative_keywords'
        ]);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'negative_keywords'
        ]);

        $this->assertNotNull($bvsValidation);

        $this->assertEquals('captured', $bvsValidation['validation_status']);

        $this->assertNotNull($verificationData);

        $this->assertEquals('initiated', $verificationData['status']);
    }

    public function testTriggerOcrServiceForNegativeKeywordsFailure()
    {
        Config::set('services.ocr_service.mock', true);

        Config::set('services.response', 'failure');

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchant = $merchantDetails->merchant;

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);


        $this->mockRazorxTreatment();

        $ocrInput = [
            'website_url' => 'http:razorpay.com'
        ];

        $detailCoreMock->triggerOCRService([
            'owner_id'                                  => $merchantDetails->getId(),
            'platform'                                  => Constant::PG,
            Constant::DOCUMENT_TYPE                     => Constant::SITE_CHECK,
            Constant::DETAILS                           => $ocrInput
        ], Constant::NEGATIVE_KEYWORDS);;

        $bvsValidation = $this->getDbEntity('bvs_validation', [
            'owner_id'        => $merchantDetails->getId(),
            'owner_type'      => 'merchant',
            'artefact_type'   => 'negative_keywords'
        ]);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'negative_keywords'
        ]);

        $this->assertNull($bvsValidation);

        $this->assertNull($verificationData);
    }

    public function testUpdateActivationStatusFromKQUToActivated()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'kyc_qualified_unactivated',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchantDetails->getMerchantId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('activated', $merchantDetailData['activation_status']);
    }

    public function testHasKeyAccess()
    {
        $merchant = Mockery::mock('RZP\Models\Merchant\Entity');

        $merchantId = '10000000000000';

        $merchantDetails = Mockery::mock('RZP\Models\Merchant\Detail\Entity');

        $merchant->shouldReceive('getAttribute')->andReturn($merchantDetails);

        $merchant->shouldReceive('getId')->andReturn($merchantId);

        $merchantDetails->shouldReceive('getWebsite')->andReturn('www.test.com');

        $response = (new DetailCore())->hasWebsite($merchant);

        $this->assertTrue($response);
    }

    //When terminal procurement status is success and upi_terminal_procurement_status_banner key is not present in Redis
    public function testPaymentEnabledCallbackConsumerForUPISuccessNoKeyInRedis()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => true,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'success',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('no_banner', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is success and upi_terminal_procurement_status_banner key is present in Redis
    // with status as Pending
    public function testPaymentEnabledCallbackConsumerForUPISuccessKeyAsPendingInRedis()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $updatedTerminalStatusBannerData = [
            StoreConstants::NAMESPACE   => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => 'pending',
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => true,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'success',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('no_banner', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is success and upi_terminal_procurement_status_banner key is present in Redis
    // with status as Pending seen
    public function testPaymentEnabledCallbackConsumerForUPISuccessKeyAsPendingSeenInRedis()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $updatedTerminalStatusBannerData = [
            StoreConstants::NAMESPACE   => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => 'pending_seen',
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => true,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'success',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('success', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is success and upi_terminal_procurement_status_banner key is present in Redis
    // with status as Pending Ack
    public function testPaymentEnabledCallbackConsumerForUPISuccessKeyAsPendingAckInRedis()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $updatedTerminalStatusBannerData = [
            StoreConstants::NAMESPACE   => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => 'pending_ack',
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => true,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'success',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('success', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is success, and UPI payment method is not enabled
    public function testPaymentEnabledCallbackConsumerForUPISuccessWithPaymentMethodsDisabled()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $updatedTerminalStatusBannerData = [
            StoreConstants::NAMESPACE   => StoreConfigKey::ONBOARDING_NAMESPACE,
            StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER => 'pending_ack',
        ];

        (new StoreCore())->updateMerchantStore($merchantId, $updatedTerminalStatusBannerData);

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => false,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'success',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('pending', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is failed for merchant in AMP
    public function testPaymentEnabledCallbackConsumerForUPIFailureForAMPMerchants()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => false,
            'merchant_activation_status'    => 'activated_mcc_pending',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'failed',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('pending', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is failed for merchant in Activated state
    public function testPaymentEnabledCallbackConsumerForUPIFailureForActivatedMerchants()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => false,
            'merchant_activation_status'    => 'activated',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.online.upi',
                'status'      => 'failed',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('pending', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    //When terminal procurement status is rejected
    public function testPaymentEnabledCallbackConsumerForUPIRejectedDuringManualProcurement()
    {
        $merchantDetail = $this->fixtures->on('test')->create('merchant_detail:valid_fields');

        $merchantId = $merchantDetail->getMerchantId();

        $kafkaEventPayload = [
            'merchant_id'                   => $merchantId,
            'payment_method'                => 'UPI',
            'payment_method_enabled'        => false,
            'merchant_activation_status'    => 'activated',
            'mir'                           => [
                'instrument'  => 'pg.upi.onboarding.offline.upi',
                'status'      => 'rejected',
            ]
        ];

        (new KafkaMessageProcessor)->process('merchant-payments-enabled-callback', $kafkaEventPayload, 'test');

        $data = (new StoreCore())->fetchValuesFromStore(
            $merchantId,
            StoreConfigKey::ONBOARDING_NAMESPACE,
            [StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER],
            StoreConstants::PUBLIC);

        $this->assertEquals('rejected', $data[StoreConfigKey::UPI_TERMINAL_PROCUREMENT_STATUS_BANNER]);
    }

    public function testGenerateLeadScoreForMerchant()
    {

        $core = new DetailCore();

        $merchant = $this->fixtures->create('merchant');

        $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
            //'company_pan'               => 'AAAPA1234J',
            'gstin'                     => '29ABCDE1234L1Z1',
            'business_website'          => 'www.test.com'
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id'               => $merchant->getId(),
            'plugin_details'            => [
                [
                    'website'                   => "www.test.com",
                    'merchant_selected_plugin'  => "shopify",
                    'suggested_plugin'          => "whmcs",
                    'ecommerce_plugin'          => true
                ]
            ],
        ]);

        //BVS Mocking
        $bvsResponse = 'success';

        $this->mockBvsService($bvsResponse);

        Config::set('services.bvs.sync.flow', true);

        //SimilarWeb Mocking
        Config::set('applications.similarweb.mock', true);

        //WhatCMS mocking not required. We have directly set plugin_details in merchant_business_details.

        //Mocking PGOS for Clearbit
        $pgosProxyController = Mockery::mock('RZP\Http\Controllers\MerchantOnboardingProxyController');

        $expectedClearbitResponse = [
            "score"                     => 40,
            "estimated_annual_revenue"  => "$500M-$1B",
            "traffic_rank"              => "very_high",
            "crunchbase"                => true,
            "twitter_followers"         => 24847,
            "linkedin"                  => true
        ];

        $pgosProxyController->shouldReceive('handlePGOSProxyRequests')->andReturn($expectedClearbitResponse);

        $leadScore = $core->generateLeadScoreForMerchant($merchant->getId(), true, true);

        $this->assertTrue($leadScore > 0);
    }

    public function testGetApplicableActivationStatusMccAdditionalDocSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'others',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchantDetails->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsAbsentSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchant->getId());

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsPresentSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->createWebsitePolicyAndNegativeKeywordFixtures($merchant->getId());

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'app_urls' => [
                'playstoreurl' => 'https://play.google.com/store/apps/details?id=com.whatsapp',
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteNegativeKeywordFailSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusNegativeKeywordFailSplitzKqu()
    {

        // This testcase was added as a part of-
        // Do not activate when MCC is present in the list (exclusion method)

        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'aviation',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '4511',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusNegativeKeywordFailOthersSplitzKqu()
    {

        // This testcase was added as a part of-
        // Do not activate when MCC is present in the list (exclusion method)

        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'others',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '4511',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'failed'
        ]);

        // negative keyword check has failed => under review
        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusActivatedSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'education',
                'subcategory'       => 'college',
                'predicted_mcc'     => 8220,
                'confidence_score'  => 0.83
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->createSignatoryVerified($merchant->getId());

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsitePolicyFailSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $detailCoreMock->expects($this->once())
            ->method('updateActivationStatus')
            ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'initiated',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'initiated',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'social',
                'subcategory'       => 'matchmaking',
                'predicted_mcc'     => 7273,
                'confidence_score'  => 0.99
            ]
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'validation_unit'   => 'identifier',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'success'
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $detailCoreMock->saveMerchantDetails($input, $merchant);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'website_policy'
        ]);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        // here we expect website policy to fail because the result stored in metadata does not contain all links i.e. privacy, terms, contact_us, refund, shipping.
        $this->assertEquals('failed', $verificationData['status']);

        // website policy check has failed => under review
        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusMccFailSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $detailCoreMock->expects($this->once())
            ->method('updateActivationStatus')
            ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantId,
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified',
            'metadata'             =>  [
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.overseasindianmatrimony.com/contact-us'
                        ],
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'policy_details_file' => 'file_Lkkrv1tgDkZgd9'
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'failed',
            'metadata'             => [
                'error_code'    => 'EXTERNAL_VENDOR_ERROR',
                'error_reason'  => "Google NLP Couldn't classify",
                'status'        => 'failed'
            ]
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'validation_unit'   => 'identifier',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'success'
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->createSignatoryVerified($merchant->getId());

        $this->mockSplitzTreatment($input, $output);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $detailCoreMock->saveMerchantDetails($input, $merchant);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'website_policy'
        ]);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusMccCategFailSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::WEBSITE_POLICY,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aZ',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier'  => 'number',
            'status'               => 'failed',
            'metadata'             => [
                'status'            => 'completed',
                'category'          => 'education',
                'subcategory'       => 'college',
                'predicted_mcc'     => 8220,
                'confidence_score'  => 0.83
            ]
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testOCRPassedActivatedSplitzKqu()
    {
        $this->markTestSkipped('Unknown error related to ASV, skipping as it blocking hotfixes. This will be fixed later');

        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $input = [
            "experiment_id" => "LQzMXMbNCUramd",
            "id"            => $merchant->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchantDetails = $this->fixtures->create('merchant_detail:valid_fields', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'bank_details_doc_verification_status' =>  null,
            Entity::CIN_VERIFICATION_STATUS        => 'verified',
        ]);

        $bvsValidationData = [
            'validation_id'     => 'LGjQP2ZQxa02ms',
            'artefact_type'     => 'mcc_categorisation_website',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ];

        $this->fixtures->create('bvs_validation', $bvsValidationData);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'mcc_categorisation_website',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05Qx',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'metadata'             => [
                'policy_details_file' => 'file_LSAa41ZugJ1BPj',
                'terms' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/termsofuse'
                        ],
                        'confidence_score' => 0.5775,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'refund' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/refundpolicy'
                        ],
                        'confidence_score' => 0.6185,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'privacy' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/privacypolicy'
                        ],
                        'confidence_score' => 0.70671977996826,
                        'relevant_details' => [
                            'note' => 'Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,'
                        ],
                        'validation_result' => true
                    ]
                ],
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/contactus'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ],
                'shipping' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/shipping'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ]
            ]
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02at',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'website_policy',
            'artefact_identifier'  => 'number',
            'metadata'             => [
                'policy_details_file' => 'file_LSAa41ZugJ1BPj',
                'terms' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/termsofuse'
                        ],
                        'confidence_score' => 0.5775,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'refund' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/refundpolicy'
                        ],
                        'confidence_score' => 0.6185,
                        'relevant_details' => [
                        ],
                        'validation_result' => true
                    ]
                ],
                'privacy' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/privacypolicy'
                        ],
                        'confidence_score' => 0.70671977996826,
                        'relevant_details' => [
                            'note' => 'Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,'
                        ],
                        'validation_result' => true
                    ]
                ],
                'contact_us' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/contactus'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ],
                'shipping' => [
                    'analysis_result' => [
                        'links_found' => [
                            'https://www.sukhdev.org/shipping'
                        ],
                        'relevant_details' => [
                            '9987394065',
                            'sukhdevonline@gmail.com'
                        ],
                        'validation_result' => true
                    ]
                ]
            ]
        ]);

        $kafkaEventPayload = [
            'data' => [
                'id'              => 'LGjQP2ZQxa02ms',
                'status'          => 'completed',
                'category_result' => [
                    'website_categorisation' => [
                        'category'         => 'ecommerce',
                        'subcategory'      => 'arts_and_collectibles',
                        'predicted_mcc'    => 5971,
                        'confidence_score' => 0.91,
                        'status'           => 'completed'
                    ]
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-mcc-notification-events', $kafkaEventPayload, 'test');

        $kafkaEventPayload = [
            'data' => [
                "website_verification_id" => "L61kGPVWKT05Qx",
                "status" => "completed",
                "verification_result" => [
                    "terms" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/termsofuse"],
                            "confidence_score" => 0.5775,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "refund" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/refundpolicy"],
                            "confidence_score" => 0.6185,
                            "relevant_details" => [],
                            "validation_result" => true,
                        ],
                    ],
                    "privacy" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/privacypolicy"],
                            "confidence_score" => 0.7067197799682617,
                            "relevant_details" => [
                                "note" =>
                                    "Privacy Policy is majorly about Third Party Sharing/Collection, International and Specific Audiences, User Choice/Control, Practice not covered, Privacy contact information, Privacy Policy includes the following attributes Named third party, Unnamed third party, Does, Receive/Shared with, Aggregated or anonymized, Identifiable, User with account, Opt-out via contacting company, First party use,",
                            ],
                            "validation_result" => true,
                        ],
                    ],
                    "contact_us" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/contactus"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                    "shipping" => [
                        "analysis_result" => [
                            "links_found" => ["https://www.sukhdev.org/shipping"],
                            "relevant_details" => ["9987394065", "sukhdevonline@gmail.com"],
                            "validation_result" => true,
                        ],
                    ],
                ],
            ]
        ];

        (new KafkaMessageProcessor)->process('pg-website-verification-notification-events', $kafkaEventPayload, 'test');

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02am',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $kafkaEventPayload = [
            "data" =>[
                "id" => "L61kGPVWKT05QT",
                "status" => "success",
                "document_details" => [
                    "result" => [
                        "prohibited" => [
                            "drugs" => [
                                "Phrases" => [
                                    "cannabis" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "financial services" => [
                                "Phrases" => [
                                    "investment" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "miscellaneous" => [
                                "Phrases" => [
                                    "cash" => 0,
                                    "cigarette" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "pharma" => [
                                "Phrases" => [
                                    "alcohol" => 0,
                                    "cannabinoid" => 0,
                                    "codeine" => 0,
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "tobacco products" => [
                                "Phrases" => [
                                    "tobacco" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ],
                            "travel" => [
                                "Phrases" => [
                                    "booking" => 0,
                                    "travel" => 0
                                ],
                                "total_count" => 0,
                                "unique_count" => 0
                            ]
                        ],
                        "required" => [
                            "policy disclosure" => [
                                "Phrases" => [
                                    "cancellations" => 1,
                                    "claims" => 11,
                                    "contact us" => 1,
                                    "delivery" => 46,
                                    "payment" => 4,
                                    "payments" => 2,
                                    "privacy" => 5,
                                    "privacy policy" => 8,
                                    "refund" => 6,
                                    "refund policy" => 4,
                                    "refunds" => 1,
                                    "return" => 3,
                                    "return policy" => 2,
                                    "returns" => 3,
                                    "terms of service" => 1
                                ],
                                "total_count" => 98,
                                "unique_count" => 15
                            ],
                        ],
                    ],
                    "website_url" => "https://www.hempstrol.com"
                ]
            ]
        ];

        (new KafkaMessageProcessor)->process('api-bvs-kyc-document-result-events', $kafkaEventPayload, 'test');

        $this->createSignatoryVerified($merchant->getId());

        Queue::assertPushed(UpdateMerchantContext::class);

        (new UpdateMerchantContext(Mode::LIVE, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $newBvsValidationData = $this->getDbEntity('bvs_validation',
            ['owner_id'        => $merchantDetails->getId(),
                'owner_type'      => 'merchant',
                'artefact_type'   => 'mcc_categorisation_website']);

        $verificationData = $this->getDbEntity('merchant_verification_detail',
            ['merchant_id'          => $merchantDetails->getId(),
                'artefact_identifier'  => 'number',
                'artefact_type'        => 'mcc_categorisation_website']);

        $merchantData = $this->getDbEntity('merchant',
            ['id' => $merchantDetails->getId()]);

        $merchantDetailsData =  $this->getDbEntity('merchant_detail',
            ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals('success', $newBvsValidationData['validation_status']);

        $this->assertEquals('verified', $verificationData['status']);

        $this->assertEquals('5971', $merchantData['category']);

        $this->assertEquals('ecommerce', $merchantData['category2']);

        $this->assertEquals('ecommerce', $merchantDetailsData['business_category']);

        $this->assertEquals('arts_and_collectibles', $merchantDetailsData['business_subcategory']);

        $this->assertEquals('kyc_qualified_unactivated', $merchantDetailsData['activation_status']);
    }
}
