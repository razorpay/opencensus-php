<?php

namespace Unit\Models\Merchant\Detail;

use App;
use Database\Connection;
use Queue;
use Config;
use Mockery;
use ReflectionClass;
use Carbon\Carbon;
use RZP\Models\Coupon;
use RZP\Constants\Mode;
use Mockery\MockInterface;
use Mockery\Matcher\AnyArgs;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Models\Admin\Permission\Name;
use RZP\Exception\EarlyWorkflowResponse;
use RZP\Models\Admin\Permission\Name as Permission;
use RZP\Models\State\Reason;
use RZP\Models\Merchant\Detail\Entity as DE;
use RZP\Tests\Functional\Fixtures\Entity\Workflow;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RZP\Models\Merchant\BusinessDetail\Entity as BusinessDetailEntity;
use RZP\Services\Stork;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Store;
use RZP\Models\Coupon\Constants;
use RZP\Jobs\PaymentPageProcessor;
use RZP\Jobs\UpdateMerchantContext;
use Illuminate\Support\Facades\Bus;
use RZP\Models\Merchant\Detail\Core;
use RZP\Services\KafkaMessageProcessor;
use RZP\Services\KafkaProducerClient;
use RZP\Models\Merchant\Detail\Entity;
use RZP\Services\Mock\ApachePinotClient;
use RZP\Models\Merchant\Detail\Validator;
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
use RZP\Http\Controllers\MerchantOnboardingProxyController;
use RZP\Models\Merchant\M2MReferral\Status as M2MEntityStatus;
use RZP\Models\Merchant\M2MReferral\Entity as M2MReferralEntity;
use RZP\Models\ClarificationDetail\Service as ClarificationDetailService;
use RZP\Models\Merchant\BvsValidation\Constants as BvsValidationConstants;
use RZP\Models\Merchant\BvsValidation\Entity as BVSEntity;
use RZP\Models\Merchant\Cron\Constants as CronConstants;
use RZP\Models\Merchant\AutoKyc\Bvs\Constant as BVSConstants;
use RZP\Models\Merchant\Cron as CronJobHandler;
use RZP\Models\Merchant\Document;
use RZP\Services\Mock\KafkaProducerClient as KafkaProducerClientMock;
use RZP\Services\Mock\DataLakePresto as DataLakePrestoMock;
use RZP\Models\Feature\Constants as FeatureConstant;

class CoreTest extends TestCase
{
    protected $repo;
    protected $app;
    protected $config;
    protected $merchant;
    protected $pgosProxyController;

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

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->pgosProxyController = Mockery::mock('RZP\Http\Controllers\MerchantOnboardingProxyController');
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

    private function createSignatoryValidationFixture($merchantId)
    {
        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02aT',
            'merchant_id'          => $merchantId,
            'artefact_type'        => 'signatory_validation',
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);
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

    public function testNoWebsiteUpdateMerchantContextActivatedSplitzLive()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

        $this->mockRazorxTreatment();

        $plan = $this->fixtures->create('pricing');
        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            \RZP\Models\Merchant\Entity::PRICING_PLAN_ID => $plan->getPlanId()
        ]);

        $balance = $this->fixtures->create('balance', ['id' => $merchant->getId(), 'merchant_id' => $merchant->getId()]);

        $this->fixtures->create('methods', [
            Entity::MERCHANT_ID => $merchant->getId(),
            'disabled_banks'    => [],
            'banks'             => '[]',
            'netbanking'        => 0,
            'debit_card'        => 0,
            'credit_card'       => 0,
        ]);

        $this->fixtures->edit('pricing', $plan->getId(), ['international' => 1]);


        $input = [
            "experiment_id" => "MCn0j0VEmYCpAb",
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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id"                         => $merchant->getId(),
            "contact_name"                        => "Mohan",
            "business_type"                       => 1,
            "business_name"                       => "Private Limited",
            "business_dba"                        => "DBA",
            "business_international"              => 0,
            "business_registered_address"         => "address",
            "business_registered_state"           => "DL",
            "business_registered_city"            => "Delhi",
            "business_registered_pin"             => 110022,
            "business_operation_address"          => "address",
            "business_operation_state"            => "DL",
            "business_operation_city"             => "Delhi",
            "business_operation_pin"              => 110022,
            "business_category"                   => "ecommerce",
            "business_subcategory"                => "fashion_and_lifestyle",
            "steps_finished"                      => [
            ],
            "activation_progress"                 => 80,
            "locked"                              => 0,
            "activation_status"                   => "under_review",
            "activation_flow"                     => "whitelist",
            "issue_fields"                        => "business_website",
            "submitted"                           => 1,
            "poi_verification_status"             => "verified",
            "personal_pan_doc_verification_status"=> "verified",
            "poa_verification_status"             => "verified",
            "bank_details_verification_status"    => "verified",
            "live_transaction_done"               => 0,
            "additional_websites"                 => [
            ],
            "company_pan_verification_status"     => "verified",
            "gstin_verification_status"           => "verified",
            "cin_verification_status"             => "verified",
            "international_activation_flow"       => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone"           => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH95NyX6wcWbG1",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "cin",
            "artefact_identifier" => "number",
            "status"              => "initiated",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
                "bvs_validation_id"           => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933kTShboSkS",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "signatory_validation",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH987XQBcsGzp8",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status"              => "verified",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH96sJegGOKRdr",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "gstin",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
                "bvs_validation_id"           => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933fs4DyoDny",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "bank_account",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH933g9SNCgxta",
            "metadata"            => [
                "bvs_validation_id"           => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);



        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];


        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();


        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // this merchant is getting applicable for fee based gating hence his activation status is not changing,
        // temp fix

        $this->assertEquals(Status::ACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);

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

    public function testSegmentEventPropertiesForMerchantNCRevampEligiblePhantomOnboarding()
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
            'signup_campaign' => 'phantom_onboarding'
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

    public function testEligibleForMtuPopupShowSignupCampaignPhantomOnboarding()
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
            'signup_campaign' => 'phantom_onboarding'
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

    public function testMalaysiaBusinessRegisteredStateCodeValidationSuccess()
    {
        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'MLK'     // Malaysia state code
        ];

        $merchantDetail = (new DetailEntity)->build($input);

        $this->assertEquals($merchantDetail->getBusinessRegisteredState(), 'MLK');
    }
    public function testMalaysiaBusinessRegisteredStateCodeValidation()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_STATE_CODE);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_STATE => 'Malacca'
        ];

        (new DetailEntity)->build($input);
    }

    public function testIndiaValidBusinessRegisteredPin()
    {
        $input = [
            DetailEntity::BUSINESS_REGISTERED_PIN => '560048',     // India pin code
        ];

        $merchantDetail = (new DetailEntity)->build($input);

        $this->assertEquals($merchantDetail->getBusinessRegisteredPin(), '560048');
    }

    public function testIndiaInvalidBusinessRegisteredPin()
    {
        $this->expectException(BadRequestValidationFailureException::class);
        $this->expectExceptionMessage(PublicErrorDescription::BAD_REQUEST_INVALID_COUNTRY_PIN);

        $input = [
            DetailEntity::BUSINESS_REGISTERED_PIN => '123',     // India pin code
        ];

        (new DetailEntity)->build($input);
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
    public function testUpdatePosActivationStatusForRejectedToUnderReviewMerchants()
    {
        $MerchantOnboardingProxyControllerMock = $this->mock(MerchantOnboardingProxyController::class, function (MockInterface $mock) {
            $mock->shouldReceive('shouldMerchantOnboardViaPGOS')
                 ->andReturn(true);
            $mock->shouldReceive('handlePGOSProxyRequests')
                 ->withArgs(function ($operation, $request, $additionalArgs) {
                     return $operation === 'merchant_fetch_pos_activation_flow';
                 })->andReturn(["pos_activation_flow"=>'whitelist']);
            $mock->shouldReceive('handlePGOSProxyRequests')
                 ->withArgs(function ($operation, $request, $additionalArgs) {
                     return $operation === 'merchant_pgos_fetch_activation_status';
                 })->andReturn(["pos_activation_status"=>'rejected']);
        });

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

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
            DetailConstant::POS_ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }
    public function testActivatedWorkflowCreationInUpdatePosActivationStatus()
    {
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

        $workflowServiceMock = \Mockery::mock(\RZP\Services\Workflow\Service::class)->makePartial();

        $workflowServiceMock->shouldReceive('handle')
                            ->once()
                            ->andThrow(new EarlyWorkflowResponse(200,null,null,[]));

        $this->app->instance('workflow', $workflowServiceMock);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
                 ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                 ->withArgs(function ($operation, $request, $additionalArgs) {
                     return $operation === 'merchant_fetch_pos_activation_flow';
                 })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                ->withArgs(function ($operation, $request, $additionalArgs) {
                    return $operation === 'merchant_pgos_fetch_activation_status';
                })->andReturn(["pos_activation_status"=>'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                 ->withArgs(function ($operation, $request, $additionalArgs) {
                     return $operation === 'merchant_pos_fetch_all_order';
                 })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                 ->times(0)
                 ->withArgs(function ($operation, $request, $additionalArgs) {
                     return $operation === 'merchant_pgos_update_activation_status';
                 })->andReturn(["pos_activation_status"=>'activated']);

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);


        $activationStatusData = [
            DetailConstant::POS_ACTIVATION_STATUS => Status::KYC_QUALIFIED_STB,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }

    public function testHandleRiskWorkFlowCreationErrors()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'services',
            'business_subcategory'      => 'ad_and_marketing',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchantDetailCore = new DetailCore();
        $workflowServiceMock = \Mockery::mock(\RZP\Services\Workflow\Service::class)->makePartial();

        $workflowServiceMock->shouldReceive('handle')
                            ->once()
                            ->andThrow(new BadRequestValidationFailureException("hi",null,null,[]));

        $this->app->instance('workflow', $workflowServiceMock);

        // Create a ReflectionClass object to inspect the DetailCore class
        $reflection = new ReflectionClass($merchantDetailCore);

        // Get a reference to the protected method 'isSubCategoryExcluded'
        $method = $reflection->getMethod('triggerWorkflowFlowForImpersonatedMerchant');

        // Allow access to the protected method by setting it to be accessible
        $method->setAccessible(true);

        // Call the protected method 'isSubCategoryExcluded' and store the result
        $result = $method->invoke($merchantDetailCore, $merchantDetails->merchant, $merchantDetails, "100000razorpay");

        $this->assertEquals(null, $result);

    }
    public function testRejectedWorkflowCreationInUpdatePosActivationStatus()
    {

        $workflowServiceMock = \Mockery::mock(\RZP\Services\Workflow\Service::class)->makePartial();

        $workflowServiceMock->shouldReceive('handle')
                            ->once()
                            ->andThrow(new EarlyWorkflowResponse(200, null, null, []));

        $this->app->instance('workflow', $workflowServiceMock);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
                                              ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_fetch_pos_activation_flow';
                                              })->andReturn(["pos_activation_flow" => 'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_fetch_activation_status';
                                              })->andReturn(["pos_activation_status" => 'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pos_fetch_all_order';
                                              })->andReturn(["order_list" => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->times(0)
                                              ->withArgs(function($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_update_activation_status';
                                              })->andReturn(["pos_activation_status" => 'activated']);

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

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
            DetailConstant::POS_ACTIVATION_STATUS => Status::REJECTED,
            Entity::ACTIVATION_STATUS             => Status::REJECTED,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }
    public function testRejectedWorkflowApprovalInUpdatePosActivationStatus()
    {
        Mail::fake();

        $workflowServiceMock = \Mockery::mock(\RZP\Services\Workflow\Service::class)->makePartial();

        $workflowServiceMock->shouldReceive('handle')
                            ->once()
                            ->andReturn();

        $this->app->instance('workflow', $workflowServiceMock);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
                                              ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_fetch_pos_activation_flow';
                                              })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_fetch_activation_status';
                                              })->andReturn(["pos_activation_status"=>'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pos_fetch_all_order';
                                              })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->times(1)
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_update_activation_status';
                                              })->andReturn(["pos_activation_status"=>'rejected']);

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

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
            DetailConstant::POS_ACTIVATION_STATUS => Status::REJECTED,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }
    public function testActivatedWorkflowApprovalInUpdatePosActivationStatus()
    {
        Mail::fake();

        $workflowServiceMock = \Mockery::mock(\RZP\Services\Workflow\Service::class)->makePartial();

        $workflowServiceMock->shouldReceive('handle')
                            ->once()
                            ->andReturn();

        $this->app->instance('workflow', $workflowServiceMock);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
                                              ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_fetch_pos_activation_flow';
                                              })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_fetch_activation_status';
                                              })->andReturn(["pos_activation_status"=>'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pos_fetch_all_order';
                                              })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->times(1)
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_update_activation_status';
                                              })->andReturn(["pos_activation_status"=>'activated']);

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);
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
            DetailConstant::POS_ACTIVATION_STATUS => Status::KYC_QUALIFIED_STB,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

       (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
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

    public function testUpdateActivationStatusToUnderReviewMerchantsActivationFormLockedThroughMDS()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone','shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(false);

        $mockBA = $this->getMockBuilder(BasicAuth::class)
                       ->setConstructorArgs([$this->app])
                       ->setMethods(['getAdmin'])
                       ->getMock();

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

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

        $org = $this->fixtures->create('org');

        $mockBA->expects($this->any())
                       ->method('getAdmin')
                       ->willReturn($merchantDetails->merchant);

        $this->app->instance('basicauth', $mockBA);
        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId($org->getId());

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->updateActivationStatus($merchantDetails->merchant->getId(), $activationStatusData);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('under_review', $response['activation_status']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $merchantDetailData['locked']);

    }

    public function testUpdateActivationStatusToUnderReviewMerchantsActivationFormLockedThroughMDSWithMutex()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone','shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(true);

        $mockBA = $this->getMockBuilder(BasicAuth::class)
                       ->setConstructorArgs([$this->app])
                       ->setMethods(['getAdmin'])
                       ->getMock();

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

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

        $org = $this->fixtures->create('org');

        $mockBA->expects($this->any())
               ->method('getAdmin')
               ->willReturn($merchantDetails->merchant);

        $this->app->instance('basicauth', $mockBA);
        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId($org->getId());

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->updateActivationStatus($merchantDetails->merchant->getId(), $activationStatusData);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('under_review', $response['activation_status']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $merchantDetailData['locked']);

    }

    public function testUpdateActivationStatusToUnderReviewMerchantsActivationFormLockedUsingInternalMerchantActivationStatus()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone','shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(false);

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

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

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $activationStatusData["workflow_maker_id"] = $admin->getId();

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->updateActivationStatusInternal($merchantDetails->merchant->getId(), $activationStatusData);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('under_review', $response['activation_status']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $merchantDetailData['locked']);

    }

    public function testUpdateActivationStatusToUnderReviewMerchantsActivationFormLockedUsingInternalMerchantActivationStatusWithMutex()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone','shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(true);

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

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

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);
        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);
        $this->app['workflow']->setWorkflowMaker($admin);

        $activationStatusData["workflow_maker_id"] = $admin->getId();

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->updateActivationStatusInternal($merchantDetails->merchant->getId(), $activationStatusData);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('under_review', $response['activation_status']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(true, $merchantDetailData['locked']);

    }

    public function testEditMerchantDetailsWithoutMutex()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(false);

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

        $merchantDetails      = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $input = [
            Entity::BUSINESS_DESCRIPTION => "Testing",
        ];

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->editMerchantDetails($merchantDetails->merchant->getId(), $input);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('Testing', $response['business_description']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('Testing', $merchantDetailData['business_description']);

    }

    public function testEditMerchantDetailsWithMutex()
    {
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['shouldApplyMutexOnMerchantEntitiesUpdate'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('shouldApplyMutexOnMerchantEntitiesUpdate')
                       ->willReturn(true);

        $detailService = new MDS();
        $detailService->replaceCoreForMocking($detailCoreMock);

        $merchantDetails      = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => null
        ]);

        $input = [
            Entity::BUSINESS_DESCRIPTION => "Testing",
        ];

        $this->assertEquals(false, $merchantDetails->islocked());

        $response = $detailService->editMerchantDetails($merchantDetails->merchant->getId(), $input);

        $this->assertNotEmpty($response);

        $this->assertIsArray($response);

        $this->assertArrayHasKey('activation_status', $response);
        $this->assertArrayHasKey('contact_name', $response);
        $this->assertArrayHasKey('locked', $response);
        $this->assertArrayHasKey('contact_email', $response);

        $this->assertEquals('Testing', $response['business_description']);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('Testing', $merchantDetailData['business_description']);

    }

    // Below test case is to check that the merchant(registered) should not go from nc to amp
    // if he has been in Nc already

    public function testGetApplicableActivationStatusForRegisteredMerchantInNeedsClarification()
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
        ]);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

    public function testGetApplicableActivationStatusForRegisteredMerchantInNeedsClarificationPhantomOnboarding()
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
        ]);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        (new MerchantCore())->appendTag($merchant, 'random_tag');

        $this->mockRazorxTreatment();

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testApplicableActivationStatus()
    {
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

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsent()
    {
        Mail::fake();

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsAbsent()
    {
        Mail::fake();

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

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

    // This test case verifies the inclusion of certain greylisted merchants into automation activation
    public function testGetApplicableActivationStatusWebsiteActivatedForGreylistedMerchants()
    {
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'transport',
            'business_subcategory'      => 'cruise_lines',
            'activation_flow'           => 'greylist',
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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

        //Experiment for greylisted merchant inclusion
        $input = [
            "experiment_id" => "NST3LYqGIRTkv6",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->assertEquals(Status::ACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    // This test case verifies the exclusion of greylisted merchants from activation because additional doc is needed as per bmc
    public function testGetApplicableActivationStatusWebsiteForGreylistedMerchantsWithAddDoc()
    {
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'transport',
            'business_subcategory'      => 'cruise_lines',
            'activation_flow'           => 'greylist',
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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(false);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

        //Experiment for greylisted merchant inclusion
        $input = [
            "experiment_id" => "NST3LYqGIRTkv6",
            "id"            => $merchant->getId(),
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
    }

    // This test case verifies the exclusion of greylisted merchants from activation because sub-category is a part of hard exclusion
    public function testGetApplicableActivationStatusWebsiteForGreylistedMerchantsToUR()
    {
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'government',
            'business_subcategory'      => 'central',
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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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
            "experiment_id" => "MCn0j0VEmYCpAb",
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

        //Experiment for sub-category exclusion
        $input = [
            "experiment_id" => "NSTUKuivyBidUf",
            "id"            => $merchant->getId(),
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
    }

    // This test verifies that certain grey listed sub-categories should not be activated after bmc phase 2 experiment is enabled
    public function testGetApplicableActivationStatusWebsiteURForGreylistedMerchants()
    {
        Mail::fake();

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'services',
            'business_subcategory'      => 'alimony_and_child_support',
            'activation_flow'           => 'greylist',
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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

        //Experiment for greylisted merchant inclusion
        $input = [
            "experiment_id" => "NST3LYqGIRTkv6",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteActivatedPhantomOnboarding()
    {
        Mail::fake();

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus', 'isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

    public function testGetApplicableActivationStatusWebsiteActivatedPilotPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category'             => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'pilot',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $businessDetail = $this->getDbLastEntity('merchant_business_detail');

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $businessDetail['metadata']['activation_status']);
    }

    public function testActivatedMccPendingActivationStatusCOI()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

    public function testFetchVerificationErrorCodesForPGOSMatchErrorDescription()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::AADHAAR,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::PROOF,
            BVSEntity::VALIDATION_STATUS => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE    => 'AADHAAR_FRONT_INVALID',
            BVSEntity::ERROR_DESCRIPTION => 'input document is not a valid AadhaarFrontBottom document',
        ]);

        $user = $this->fixtures->create('user', ['email' => 'old@razorpay.com']);

        $merchantUser = $this->fixtures->create('user_device_detail', [
            'merchant_id'         => $fixtures['merchant_detail']->getMerchantId(),
            'user_id'             => $user->getId(),
            'signup_campaign'     => 'easy_onboarding',
            'metadata'            => ['service'=>'pgos']
        ]);

        $input = [
            "experiment_id" => "O4mkX116qZcqdQ",
            "id"            => $fixtures['merchant_detail']->getMerchantId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [Entity::POA_VERIFICATION_STATUS => 'AADHAAR_FRONT_INVALID'];
        $this->assertEquals($error_codes, $expectedOutput);
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

    public function testValidateBusinessSubcategoryForCategoryForEasyOnboarding()
    {
        $merchantId = '1T4hRFHFx4SPDK';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_subcategory' => "fashion_and_lifestyle",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
        ]);

        $categoryData = [
            DE::BUSINESS_CATEGORY    => "ecommerce",
            DE::BUSINESS_SUBCATEGORY => "fashion_and_lifestyle",
        ];

        $validator = new Validator($merchantDetail);

        try {
            $validator->validateBusinessSubcategoryForCategory($categoryData);
            $this->assertTrue(true);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertStringContainsString(Validator::INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY, $e->getMessage());
            $this->assertTrue(false);
        }
    }

    public function testOldOnboardingInvalidBusinessSubcategory()
    {
        $merchantId = '1T4hRFHFx4UiST';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_subcategory' => "fashion_and_lifestyle",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'old_onboarding',
        ]);
        // If category is not `others` and subcategory is not valid
        $categoryData = [
            DE::BUSINESS_CATEGORY    => "ecommerce",
            DE::BUSINESS_SUBCATEGORY => "coaching",
        ];

        $validator = new Validator($merchantDetail);

        try {
            $validator->validateBusinessSubcategoryForCategory($categoryData);
            $this->assertTrue(false);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertStringContainsString(Validator::INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY, $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testCategoryAndSubcategoryNullForOldOnboarding()
    {
        $merchantId = '1T4hRFHFx4UiPK';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_subcategory' => "fashion_and_lifestyle",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'old_onboarding',
        ]);
       // If category and subcategory are not set
        $categoryData = [
            DE::BUSINESS_CATEGORY    => null,
            DE::BUSINESS_SUBCATEGORY => null,
        ];

        $validator = new Validator($merchantDetail);

        try {
            $validator->validateBusinessSubcategoryForCategory($categoryData);
            $this->assertTrue(true);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertStringContainsString(Validator::INVALID_BUSINESS_SUBCATEGORY_FOR_CATEGORY, $e->getMessage());
            $this->assertTrue(false);
        }
    }

    public function testValidateIfCategoryIsNullForOldOnboarding()
    {
        $merchantId = '1T4hRFHFx4SPDK';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_subcategory' => "fashion_and_lifestyle",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'old_onboarding',
        ]);

        $categoryData = [
            DE::BUSINESS_CATEGORY    => null,
            DE::BUSINESS_SUBCATEGORY => "fashion_and_lifestyle",
        ];

        $validator = new Validator($merchantDetail);

        try {
            $validator->validateBusinessSubcategoryForCategory($categoryData);
            $this->assertTrue(false);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertStringContainsString(Validator::BUSINESS_CATEGORY_MISSING_FOR_SUBCATEGORY, $e->getMessage());
            $this->assertTrue(true);
        }
    }

    public function testOldOnboardingCategoryAndSubcategoryEmptyValidation()
    {
        $merchantId = '1T4hRFHFx4SFTU';

        $merchantAttributes = [
            'id' => $merchantId,
        ];

        $merchant = $this->fixtures->create('merchant', $merchantAttributes);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id' => $merchantId,
            'business_subcategory' => "fashion_and_lifestyle",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantId);

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantId,
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'old_onboarding',
        ]);

        $categoryData = [
            DE::BUSINESS_CATEGORY    => "",
            DE::BUSINESS_SUBCATEGORY => "",
        ];

        $validator = new Validator($merchantDetail);

        try {
            $validator->validateBusinessSubcategoryForCategory($categoryData);
            $this->assertTrue(true);
        } catch (BadRequestValidationFailureException $e) {
            $this->assertStringContainsString(Validator::BUSINESS_CATEGORY_MISSING_FOR_SUBCATEGORY, $e->getMessage());
            $this->assertTrue(false);
        }
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

    public function testFetchVerificationErrorCodesInactiveGstinAndGstinPresent()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::GSTIN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS    => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE           => 'RULE_EXECUTION_FAILED',
            BVSEntity::ERROR_DESCRIPTION    => 'inactive_gstin'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $merchantDetail->setAttribute(Entity::GSTIN, '01AADCB1234M1ZX');
        $merchantDetail->save();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [Entity::GSTIN_VERIFICATION_STATUS => 'INACTIVE_GSTIN'];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testFetchVerificationErrorCodesInactiveGstinAndGstinAbsent()
    {
        // when records are found and error description is matched and validation status is failed
        $core = new DetailCore();
        $this->createAndFetchMocks();
        $fixtures = $this->createAndFetchFixtures([
        ],[],[
            BVSConstants::ARTEFACT_TYPE     => BVSConstants::GSTIN,
            BVSConstants::VALIDATION_UNIT   => BvsValidationConstants::IDENTIFIER,
            BVSEntity::VALIDATION_STATUS    => BvsValidationConstants::FAILED,
            BVSEntity::ERROR_CODE           => 'RULE_EXECUTION_FAILED',
            BVSEntity::ERROR_DESCRIPTION    => 'inactive_gstin'
        ]);
        $merchantDetail = $fixtures['merchant_detail'];
        $merchantId = $merchantDetail->getMerchantId();
        $merchantDetail->setAttribute(Entity::GSTIN, '');
        $merchantDetail->save();
        $error_codes = $core->fetchVerificationErrorCodes($merchantDetail->merchant);
        $expectedOutput = [];
        $this->assertEquals($error_codes, $expectedOutput);
    }

    public function testActivatedMccPendingActivationStatusTrust()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        // TODO Phantom Onboarding add an equivalent test for phantom_onboarding
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

        $detailCoreMock->triggerOCRService($ocrInput, 'mcc_categorisation', $merchant);

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

        $detailCoreMock->triggerOCRService($ocrInput, 'mcc_categorisation', $merchant);

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

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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
            'poa_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'company_pan_doc_verification_status'  => 'verified',
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

        $merchantUserlive = $this->fixtures->on('live')->user->createUserForMerchant($merchantDetails->getMerchantId());

        $this->fixtures->on('live')->create('user_device_detail', [
            'merchant_id'     =>$merchantDetails->getMerchantId(),
            'user_id'         => $merchantUserlive->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $merchantUser = $this->fixtures->on('test')->user->createUserForMerchant($merchantDetails->getMerchantId());

        $this->fixtures->on('test')->create('user_device_detail', [
            'merchant_id'     =>$merchantDetails->getMerchantId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

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

    public function testOCRPassedActivatedPilotPhantomOnboarding()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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
            'poa_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://www.sukhdev.org',
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'company_pan_doc_verification_status'  => 'verified',
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

        $merchantUserlive = $this->fixtures->on('live')->user->createUserForMerchant($merchantDetails->getMerchantId());

        $this->fixtures->on('live')->create('user_device_detail', [
            'merchant_id'     =>$merchantDetails->getMerchantId(),
            'user_id'         => $merchantUserlive->getId(),
            'signup_campaign' => 'phantom_onboarding'
        ]);

        $merchantUser = $this->fixtures->on('test')->user->createUserForMerchant($merchantDetails->getMerchantId());

        $this->fixtures->on('test')->create('user_device_detail', [
            'merchant_id'     =>$merchantDetails->getMerchantId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

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
        $this->markTestSkipped('Unknown error , skipping as it blocking compliance issues');

        Mail::fake();

        $this->mockRazorxTreatment();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
                $this->assertTrue(in_array($eventName, ["CARD Wrapper Requested"], true));
            }));

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
        $this->markTestSkipped('Unknown error , skipping as it blocking compliance issues');

        Mail::fake();

        $this->mockRazorxTreatment();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $this->assertEquals(false, $methods['upi']);
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

        $detailCoreMock->triggerOCRService($ocrInput, 'website_policy', $merchant);

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

        $detailCoreMock->triggerOCRService($ocrInput, 'website_policy', $merchant);

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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $actionState = $this->getDbLastEntity('action_state', 'live');

        $agent = $actionState['updated_by'];

        $this->assertEquals('system', $agent);

        $this->assertEquals('kyc_qualified_unactivated', $merchantDetailData['activation_status']);
    }

    public function testUpdateActivationStatusFromURToKQUByAdmin()
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
            'company_pan'               => 'AAACA1234J',
            'company_cin'               => 'U67190TN2014PTC096978',
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

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $actionState = $this->getDbLastEntity('action_state', 'live');

        $agent = $actionState['updated_by'];

        $this->assertEquals('admin', $agent);

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
        ], Constant::NEGATIVE_KEYWORDS, $merchant);;

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
        ], Constant::NEGATIVE_KEYWORDS, $merchant);;

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

        $actionState = $this->getDbLastEntity('action_state', 'live');

        $statusChangedBy = $actionState['updated_by'];

        $this->assertEquals('activated', $merchantDetailData['activation_status']);

        $this->assertEquals('system', $statusChangedBy);
    }

    public function testUpdateActivationStatusFromEDDPendingToActivated()
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
            'activation_status'         => 'edd_pending',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchantUser = $this->fixtures->connection('live')->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->connection('live')->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'assisted_onboarding',
            'metadata' => [
                'service'       => 'pgos',
                'workflow_type' => 'modular_onboarding',
                "workflow_details" => [
                    "pg_onboarding_workflow_type" =>"MODULAR_ONBOARDING"
                ]
            ]
        ]);

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

        $actionState = $this->getDbLastEntity('action_state', 'live');

        $statusChangedBy = $actionState['updated_by'];

        $this->assertEquals('activated', $merchantDetailData['activation_status']);

        $this->assertEquals('system', $statusChangedBy);
    }

    public function testUpdateActivationStatusFromKQUToActivatedByAdmin()
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
            'company_pan'               => 'AAACA1234J',
            'company_cin'               => 'U67190TN2014PTC096978',
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

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $actionState = $this->getDbLastEntity('action_state', 'live');

        $statusChangedBy =  $actionState['updated_by'];

        $this->assertEquals('activated', $merchantDetailData['activation_status']);

        $this->assertEquals('admin', $statusChangedBy);
    }

    public function testUpdateActivationStatusFromKQUToActivatedOthersCategory()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->getMock();

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'business_category'         => 'others',
            'business_subcategory'      => 'others',
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

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('"Others" is not allowed in Category or Sub-category.');

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }

    public function testHasKeyAccess()
    {
        $merchant = Mockery::mock('RZP\Models\Merchant\Entity');

        $merchantId = '10000000000000';

        $merchantDetails = Mockery::mock('RZP\Models\Merchant\Detail\Entity');

        $merchant->shouldReceive('getAttribute')->andReturn($merchantDetails);

        $merchant->shouldReceive('getId')->andReturn($merchantId);

        $merchantDetails->shouldReceive('getWebsite')->andReturn('www.test.com');

        $response = (new DetailCore())->hasBusinessWebsiteOrAppUrls($merchant);

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

    public function testSubmitMerchantInternalByOnboardngType()
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

        //Mocking PGOS for Clearbit
        $pgosProxyController = Mockery::mock('RZP\Http\Controllers\MerchantOnboardingProxyController');

        $expectedPosActivationStatus = [
            "pos_activation_status"                     => "under_review",
        ];

        $inputPayload =  [
            "onboarding_type" => "pgAndPos"
        ];

        $pgosProxyController->shouldReceive('handlePGOSProxyRequests')->andReturn($expectedPosActivationStatus);

        $merchant = $core->submitMerchantInternalByOnboardingType($inputPayload, $merchant);

        $this->assertTrue($merchant != null);
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

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzKqu()
    {

        // Here the merchant signatory is not present hence the merchant will be in AMP

        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzKquSignatoryVerified()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->createSignatoryValidationFixture($merchantDetails->getId());

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzKquSignatoryVerifiedPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
        ]);

        $this->createSignatoryValidationFixture($merchantDetails->getId());

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzLiveSignatoryVerified()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->createSignatoryValidationFixture($merchantDetails->getId());

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::ACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAbsentSplitzLiveSignatoryVerifiedPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
        ]);

        $this->createSignatoryValidationFixture($merchantDetails->getId());

        $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'live',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->assertEquals(Status::ACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusWebsiteAppURLsAbsentSplitzKqu()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            'category' => '5945',
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

    public function testGetApplicableActivationStatusActivatedSplitzKquWithHostedPolicies()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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
            'category' => '5945',
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02as',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier' => 'number',
            'status'              => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02aT',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::WEBSITE_POLICY,
            'artefact_identifier' => 'number',
            'status'              => 'failed',
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping"            => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score"  => 0.6079,
                        "relevant_details"  => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $attributes = [
            'id' => 'LGjQP2ZQxa02as',
            'merchant_id' => $merchant->getId(),
            'status' => 'submitted',
            "shipping_period" => "3-5 days",
            "refund_request_period" => "3-5 days",
            "refund_process_period" => "3-5 days",
            "additional_data" => [
                "support_contact_number" => "9980004017",
                "support_email" => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"
                ],
                "about_us" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/about_us"
                ]
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $attributes);
        $this->fixtures->on(Connection::ASV_WRITER)->create('merchant_website', $attributes);
        $this->fixtures->on('test')->create('merchant_website', $attributes);


        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02aZ',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier' => 'number',
            'status'              => 'verified',
            'metadata'            => [
                'status'           => 'completed',
                'category'         => 'education',
                'subcategory'      => 'college',
                'predicted_mcc'    => 8220,
                'confidence_score' => 0.83
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

    public function testUpdateActivationStatusSplitzKquWithKLA()
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
            'company_pan'              => "AAACA1234J"
        ]);

        $merchant = $this->fixtures->edit('merchant', $merchantDetails->getId(), [
            'category' => '5945',
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'metadata'    => [
                'key_less_activation_enable' => true
            ]
        ]);
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02as',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier' => 'number',
            'status'              => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02aT',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::WEBSITE_POLICY,
            'artefact_identifier' => 'number',
            'status'              => 'failed'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                  => 'LGjQP2ZQxa02aZ',
            'merchant_id'         => $merchantDetails->getMerchantId(),
            'artefact_type'       => Constant::MCC_CATEGORISATION_WEBSITE,
            'artefact_identifier' => 'number',
            'status'              => 'verified',
            'metadata'            => [
                'status'           => 'completed',
                'category'         => 'education',
                'subcategory'      => 'college',
                'predicted_mcc'    => 8220,
                'confidence_score' => 0.83
            ]
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

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $this->createSignatoryVerified($merchant->getId());

        $this->mockSplitzTreatment($input, $output);
        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetailData['activation_status']);

    }

    public function testGetApplicableActivationStatusActivatedSplitzKquPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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
            ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus', 'isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->exactly(2))
            ->method('canSubmit')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

    public function testOCRPassedActivationBlockKQUPhantomOnboarding()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_status" => "under_review",
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
                "clarification_reasons" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "verified",
            "gstin_verification_status" => "verified",
            "cin_verification_status" => "verified",
            "international_activation_flow" => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [
                "result" => [
                    "required" => [
                        "policy disclosure" => [
                            "phrases" => [
                                "Payment" => 1,
                                "Returns" => 1,
                                "Contact us" => 1,
                                "privacy policy" => 1
                            ],
                            "total_count" => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGVEldWInOq",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGw1N8TTDPPCz5",
            "metadata" => [
                "status" => "completed",
                "category" => "ecommerce",
                "subcategory" => "women_clothing",
                "predicted_mcc" => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGHX1Vf0bK2",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "website_policy",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH98mqZfN59Wx8",
            "metadata" => [
                "terms" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/terms-conditions"
                        ],
                        "confidence_score" => 0.5651,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "refund" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score" => 0.5465,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score" => 0.9853,
                        "relevant_details" => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score" => 0.6079,
                        "relevant_details" => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details" => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH95NyX6wcWbG1",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "cin",
            "artefact_identifier" => "number",
            "status" => "initiated",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
                "bvs_validation_id" => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'mcc_categorisation_website'
        ]);

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $businessDetail['metadata']['activation_status']);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }
    public function testOCRPassedActivationBlockKQU()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_status" => "under_review",
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
                "clarification_reasons" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "verified",
            "gstin_verification_status" => "verified",
            "cin_verification_status" => "verified",
            "international_activation_flow" => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [
                "result" => [
                    "required" => [
                        "policy disclosure" => [
                            "phrases" => [
                                "Payment" => 1,
                                "Returns" => 1,
                                "Contact us" => 1,
                                "privacy policy" => 1
                            ],
                            "total_count" => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGVEldWInOq",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGw1N8TTDPPCz5",
            "metadata" => [
                "status" => "completed",
                "category" => "ecommerce",
                "subcategory" => "women_clothing",
                "predicted_mcc" => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGHX1Vf0bK2",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "website_policy",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH98mqZfN59Wx8",
            "metadata" => [
                "terms" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/terms-conditions"
                        ],
                        "confidence_score" => 0.5651,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "refund" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score" => 0.5465,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score" => 0.9853,
                        "relevant_details" => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score" => 0.6079,
                        "relevant_details" => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details" => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH95NyX6wcWbG1",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "cin",
            "artefact_identifier" => "number",
            "status" => "initiated",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
                "bvs_validation_id" => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
            ->shouldReceive('evaluateRequest')
            ->times(0)
            ->with($input)
            ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'mcc_categorisation_website'
        ]);

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $businessDetail['metadata']['activation_status']);

        // because of the mock this merchant was becoming eligible for fee based gating hence the activation status
        // turned out to be under review , temp change.

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }

    public function testUpdatePolicyPageWhenMerchantMovedToKQU()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce',
            "has_key_access" => true
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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id"                         => $merchant->getId(),
            "contact_name"                        => "Mohan",
            "business_type"                       => 4,
            "business_name"                       => "Private Limited",
            "business_dba"                        => "DBA",
            "business_website"                    => "https://www.ilovesarees.com/",
            "business_international"              => 0,
            "business_registered_address"         => "address",
            "business_registered_state"           => "DL",
            "business_registered_city"            => "Delhi",
            "business_registered_pin"             => 110022,
            "business_operation_address"          => "address",
            "business_operation_state"            => "DL",
            "business_operation_city"             => "Delhi",
            "business_operation_pin"              => 110022,
            "business_category"                   => "ecommerce",
            "business_subcategory"                => "fashion_and_lifestyle",
            "steps_finished"                      => [
            ],
            "activation_progress"                 => 80,
            "locked"                              => 0,
            "activation_status"                   => "under_review",
            "activation_flow"                     => "whitelist",
            "issue_fields"                        => "business_website",
            "submitted"                           => 1,
            "poi_verification_status"             => "verified",
            "poa_verification_status"             => "verified",
            "bank_details_verification_status"    => "verified",
            "kyc_clarification_reasons"           => [
                "nc_count"                 => 1,
                "additional_details"       => [
                ],
                "clarification_reasons"    => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done"               => 0,
            "additional_websites"                 => [
            ],
            "company_pan_verification_status"     => "verified",
            "gstin_verification_status"           => "verified",
            "cin_verification_status"             => "verified",
            "international_activation_flow"       => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone"           => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGahWhUb2Ew",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "negative_keywords",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGlLFiUREeLueC",
            "metadata"            => [
                "result"      => [
                    "required"   => [
                        "policy disclosure" => [
                            "phrases"      => [
                                "Payment"        => 1,
                                "Returns"        => 1,
                                "Contact us"     => 1,
                                "privacy policy" => 1
                            ],
                            "total_count"  => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGVEldWInOq",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGw1N8TTDPPCz5",
            "metadata"            => [
                "status"           => "completed",
                "category"         => "ecommerce",
                "subcategory"      => "women_clothing",
                "predicted_mcc"    => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGHX1Vf0bK2",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "website_policy",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH98mqZfN59Wx8",
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => false
                    ]
                ],
                "shipping"            => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score"  => 0.6079,
                        "relevant_details"  => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH95NyX6wcWbG1",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "cin",
            "artefact_identifier" => "number",
            "status"              => "initiated",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
                "bvs_validation_id"           => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933kTShboSkS",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "signatory_validation",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH987XQBcsGzp8",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status"              => "verified",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH96sJegGOKRdr",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "gstin",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
                "bvs_validation_id"           => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933fs4DyoDny",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "bank_account",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH933g9SNCgxta",
            "metadata"            => [
                "bvs_validation_id"           => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);



        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];


        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $attributes = [
            'id' => 'LGjQP2ZQxa02as',
            'merchant_id' => $merchant->getId(),
            'status' => 'submitted',
            "shipping_period" => "3-5 days",
            "refund_request_period" => "3-5 days",
            "refund_process_period" => "3-5 days",
            "additional_data" => [
                "support_contact_number" => "9980004017",
                "support_email" => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"
                ],
                "about_us" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/about_us"
                ],
                "privacy" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/privacy"
                ],
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $attributes);
        $this->fixtures->on(Connection::ASV_WRITER)->create('merchant_website', $attributes);
        $this->fixtures->on('test')->create('merchant_website', $attributes);

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'         => $merchantDetails->getId(),
            'artefact_identifier' => 'number',
            'artefact_type'       => 'mcc_categorisation_website'
        ]);
        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // this merchant is getting applicable for fee based gating hence his activation status is not changing,
        // temp fix

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);

        $this->assertEquals([
                                'terms'        =>  ['url' => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"],
                                'refund'       =>  ['url' => "https://ilovesarees.com/pages/returns"],
                                'cancellation' =>  ['url' => "https://ilovesarees.com/pages/returns"],
                                'privacy'      =>  ['url' => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/privacy"],
                                'contact_us'   =>  ['url' => "https://ilovesarees.com/pages/contact-us"],
                                'shipping'     =>  ['url' => "https://ilovesarees.com/policies/shipping-policy"],
                            ], $merchantWebsiteDetail['admin_website_details']['website']['https://www.ilovesarees.com/']);
    }


    public function testUpdatePolicyPageWhenMerchantMovedToKQUAtL2()
    {
        Queue::fake();
        $this->app['rzp.mode'] = 'live';

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id"                         => $merchant->getId(),
            "contact_name"                        => "Mohan",
            "business_type"                       => 4,
            "business_name"                       => "Private Limited",
            "business_dba"                        => "DBA",
            "business_website"                    => "https://www.ilovesarees.com/",
            "business_international"              => 0,
            "business_registered_address"         => "address",
            "business_registered_state"           => "DL",
            "business_registered_city"            => "Delhi",
            "business_registered_pin"             => 110022,
            "business_operation_address"          => "address",
            "business_operation_state"            => "DL",
            "business_operation_city"             => "Delhi",
            "business_operation_pin"              => 110022,
            "business_category"                   => "ecommerce",
            "business_subcategory"                => "fashion_and_lifestyle",
            "steps_finished"                      => [
            ],
            "activation_progress"                 => 80,
            "locked"                              => 0,
            "activation_status"                   => "under_review",
            "activation_flow"                     => "whitelist",
            "issue_fields"                        => "business_website",
            "submitted"                           => 1,
            "poi_verification_status"             => "verified",
            "poa_verification_status"             => "verified",
            "bank_details_verification_status"    => "verified",
            "kyc_clarification_reasons"           => [
                "nc_count"                 => 1,
                "additional_details"       => [
                ],
                "clarification_reasons"    => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done"               => 0,
            "additional_websites"                 => [
            ],
            "company_pan_verification_status"     => "verified",
            "gstin_verification_status"           => "verified",
            "cin_verification_status"             => "verified",
            "international_activation_flow"       => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone"           => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGahWhUb2Ew",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "negative_keywords",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGlLFiUREeLueC",
            "metadata"            => [
                "result"      => [
                    "required"   => [
                        "policy disclosure" => [
                            "phrases"      => [
                                "Payment"        => 1,
                                "Returns"        => 1,
                                "Contact us"     => 1,
                                "privacy policy" => 1
                            ],
                            "total_count"  => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGVEldWInOq",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGw1N8TTDPPCz5",
            "metadata"            => [
                "status"           => "completed",
                "category"         => "ecommerce",
                "subcategory"      => "women_clothing",
                "predicted_mcc"    => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGHX1Vf0bK2",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "website_policy",
            "artefact_identifier" => "number",
            "status"              => "failed  ",
            "audit_id"            => "MH98mqZfN59Wx8",
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => false
                    ]
                ],
                "shipping"            => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score"  => 0.6079,
                        "relevant_details"  => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "terms"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://www.ilovesarees.com/terms"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH95NyX6wcWbG1",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "cin",
            "artefact_identifier" => "number",
            "status"              => "initiated",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
                "bvs_validation_id"           => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933kTShboSkS",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "signatory_validation",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH987XQBcsGzp8",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status"              => "verified",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH96sJegGOKRdr",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "gstin",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
                "bvs_validation_id"           => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933fs4DyoDny",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "bank_account",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH933g9SNCgxta",
            "metadata"            => [
                "bvs_validation_id"           => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $websiteAttributes = [
            'id'                       => 'LGjQP2ZQxa02as',
            'merchant_id'              => $merchant->getId(),
            'status'                   => 'submitted',
            "shipping_period"          => "3-5 days",
            "refund_request_period"    => "3-5 days",
            "refund_process_period"    => "3-5 days",
            "additional_data"          => [
                "support_contact_number" => "9980004017",
                "support_email"          => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "about_us" => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/about_us"
                ],
                "privacy"  => [
                    "section_status" => 3,
                    "status"         => "submitted",
                    "published_url"  => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/privacy"
                ],
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $websiteAttributes);
        $this->fixtures->on(Connection::ASV_WRITER)->create('merchant_website', $websiteAttributes);
        $this->fixtures->on('test')->create('merchant_website', $websiteAttributes);

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'         => $merchantDetails->getId(),
            'artefact_identifier' => 'number',
            'artefact_type'       => 'mcc_categorisation_website'
        ]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['blockMerchantActivations', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('blockMerchantActivations')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('canSubmit')
                       ->willReturn(true);

        $detailCoreMock->saveMerchantDetails(['activation_form_milestone' => 'L2'], $merchant);

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        // this merchant is getting applicable for fee based gating hence his activation status is not changing,
        // temp fix

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);

        $this->assertEquals([
                                'terms' => ['url' => "https://www.ilovesarees.com/terms"],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         'refund' => ['url' => "https://ilovesarees.com/pages/returns"],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                         'cancellation' => ['url' => "https://ilovesarees.com/pages/returns"],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               'privacy' => ['url' => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/privacy"],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                          'contact_us' => ['url' => "https://ilovesarees.com/pages/contact-us"],
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                   'shipping' => ['url' => "https://ilovesarees.com/policies/shipping-policy"],
                            ], $merchantWebsiteDetail['admin_website_details']['website']['https://www.ilovesarees.com/']);
    }


    public function testSegmentAdditionalPropsOnL2()
    {
        Queue::fake();
        $this->app['rzp.mode'] = 'live';

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $segmentMock = $this->getMockBuilder(SegmentAnalyticsClient::class)
                            ->setMethods(['pushTrackEvent'])
                            ->getMock();

        $defaultVerificationDetailAttributes = [
            'merchant_id'         => $merchant->getId(),
            'artefact_type'       => Constant::TRUST_SOCIETY_NGO_BUSINESS_CERTIFICATE,
            'status'              => 'verified',
            'artefact_identifier' => 'doc',
        ];

        $verificationDetail = $this->fixtures->create(
            'merchant_verification_detail',
         $defaultVerificationDetailAttributes);

        $this->app->instance('segment-analytics', $segmentMock);

        $segmentMock->expects($this->atLeastOnce())
                    ->method('pushTrackEvent');

        $this->mockSplitzTreatment($input, $output);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id"                         => $merchant->getId(),
            "contact_name"                        => "Mohan",
            "business_type"                       => 4,
            "business_name"                       => "Private Limited",
            "business_dba"                        => "DBA",
            "business_website"                    => "https://www.ilovesarees.com/",
            "business_international"              => 0,
            "business_registered_address"         => "address",
            "business_registered_state"           => "DL",
            "business_registered_city"            => "Delhi",
            "business_registered_pin"             => 110022,
            "business_operation_address"          => "address",
            "business_operation_state"            => "DL",
            "business_operation_city"             => "Delhi",
            "business_operation_pin"              => 110022,
            "business_category"                   => "ecommerce",
            "business_subcategory"                => "fashion_and_lifestyle",
            "steps_finished"                      => [
            ],
            "activation_progress"                 => 80,
            "locked"                              => 0,
            "activation_status"                   => "under_review",
            "activation_flow"                     => "whitelist",
            "issue_fields"                        => "business_website",
            "submitted"                           => 1,
            "poi_verification_status"             => "verified",
            "poa_verification_status"             => "verified",
            "bank_details_verification_status"    => "verified",
            "kyc_clarification_reasons"           => [
                "nc_count"                 => 1,
                "additional_details"       => [
                ],
                "clarification_reasons"    => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done"               => 0,
            "additional_websites"                 => [
            ],
            "company_pan_verification_status"     => "verified",
            "gstin_verification_status"           => "verified",
            "cin_verification_status"             => "verified",
            "international_activation_flow"       => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone"           => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGahWhUb2Ew",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "negative_keywords",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGlLFiUREeLueC",
            "metadata"            => [
                "result"      => [
                    "required"   => [
                        "policy disclosure" => [
                            "phrases"      => [
                                "Payment"        => 1,
                                "Returns"        => 1,
                                "Contact us"     => 1,
                                "privacy policy" => 1
                            ],
                            "total_count"  => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGVEldWInOq",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGw1N8TTDPPCz5",
            "metadata"            => [
                "status"           => "completed",
                "category"         => "ecommerce",
                "subcategory"      => "women_clothing",
                "predicted_mcc"    => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGHX1Vf0bK2",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "website_policy",
            "artefact_identifier" => "number",
            "status"              => "failed  ",
            "audit_id"            => "MH98mqZfN59Wx8",
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => false
                    ]
                ],
                "shipping"            => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score"  => 0.6079,
                        "relevant_details"  => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH95NyX6wcWbG1",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "cin",
            "artefact_identifier" => "number",
            "status"              => "initiated",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
                "bvs_validation_id"           => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);
        $stakeholder = $this->fixtures->create('stakeholder', [
            'merchant_id'                          => $merchant->getId()
        ]);
        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933kTShboSkS",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "signatory_validation",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH987XQBcsGzp8",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status"              => "verified",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH96sJegGOKRdr",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "gstin",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
                "bvs_validation_id"           => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933fs4DyoDny",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "bank_account",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH933g9SNCgxta",
            "metadata"            => [
                "bvs_validation_id"           => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $attributes = [
            'id' => 'LGjQP2ZQxa02as',
            'merchant_id' => $merchant->getId(),
            'status' => 'submitted',
            "shipping_period" => "3-5 days",
            "refund_request_period" => "3-5 days",
            "refund_process_period" => "3-5 days",
            "additional_data" => [
                "support_contact_number" => "9980004017",
                "support_email" => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"
                ],
                "about_us" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/about_us"
                ],
                "privacy" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/privacy"
                ],
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $attributes);
        $this->fixtures->on(COnnection::ASV_WRITER)->create('merchant_website', $attributes);
        $this->fixtures->on('test')->create('merchant_website', $attributes);

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'         => $merchantDetails->getId(),
            'artefact_identifier' => 'number',
            'artefact_type'       => 'mcc_categorisation_website'
        ]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['blockMerchantActivations', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('blockMerchantActivations')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('canSubmit')
                       ->willReturn(true);

        $detailCoreMock->saveMerchantDetails(['activation_form_milestone' => 'L2'], $merchant);

        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }


    public function testActivateMerchantWithHostedPolicies()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

        $this->mockRazorxTreatment();
        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['fetchMerchantGatingDetails'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('fetchMerchantGatingDetails')
                       ->willReturn(null);

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id"                         => $merchant->getId(),
            "contact_name"                        => "Mohan",
            "business_type"                       => 4,
            "business_name"                       => "Private Limited",
            "business_dba"                        => "DBA",
            "business_website"                    => "https://www.ilovesarees.com/",
            "business_international"              => 0,
            "business_registered_address"         => "address",
            "business_registered_state"           => "DL",
            "business_registered_city"            => "Delhi",
            "business_registered_pin"             => 110022,
            "business_operation_address"          => "address",
            "business_operation_state"            => "DL",
            "business_operation_city"             => "Delhi",
            "business_operation_pin"              => 110022,
            "business_category"                   => "ecommerce",
            "business_subcategory"                => "fashion_and_lifestyle",
            "steps_finished"                      => [
            ],
            "activation_progress"                 => 80,
            "locked"                              => 0,
            "activation_status"                   => "under_review",
            "activation_flow"                     => "whitelist",
            "issue_fields"                        => "business_website",
            "submitted"                           => 1,
            "poi_verification_status"             => "verified",
            "poa_verification_status"             => "verified",
            "bank_details_verification_status"    => "verified",
            "kyc_clarification_reasons"           => [
                "nc_count"                 => 1,
                "additional_details"       => [
                ],
                "clarification_reasons"    => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from"        => "admin",
                            "nc_count"    => 1,
                            "is_current"  => true,
                            "field_value" => "https://www.ilovesarees.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done"               => 0,
            "additional_websites"                 => [
            ],
            "company_pan_verification_status"     => "verified",
            "gstin_verification_status"           => "verified",
            "cin_verification_status"             => "verified",
            "international_activation_flow"       => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone"           => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGahWhUb2Ew",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "negative_keywords",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGlLFiUREeLueC",
            "metadata"            => [
                "result"      => [
                    "required"   => [
                        "policy disclosure" => [
                            "phrases"      => [
                                "Payment"        => 1,
                                "Returns"        => 1,
                                "Contact us"     => 1,
                                "privacy policy" => 1
                            ],
                            "total_count"  => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGVEldWInOq",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MGw1N8TTDPPCz5",
            "metadata"            => [
                "status"           => "completed",
                "category"         => "ecommerce",
                "subcategory"      => "women_clothing",
                "predicted_mcc"    => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGHX1Vf0bK2",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "website_policy",
            "artefact_identifier" => "number",
            "status"              => "failed",
            "audit_id"            => "MH98mqZfN59Wx8",
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping"            => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score"  => 0.6079,
                        "relevant_details"  => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us"          => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details"  => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH95NyX6wcWbG1",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "cin",
            "artefact_identifier" => "number",
            "status"              => "initiated",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
                "bvs_validation_id"           => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933kTShboSkS",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "signatory_validation",
            "artefact_identifier" => "number",
            "status"              => "verified",
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH987XQBcsGzp8",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status"              => "verified",
            "audit_id"            => "MGvjUr37Z52dur",
            "metadata"            => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH96sJegGOKRdr",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "gstin",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH96sMk4xoPIZB",
            "metadata"            => [
                "bvs_validation_id"           => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH933fs4DyoDny",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "bank_account",
            "artefact_identifier" => "number",
            "status"              => null,
            "audit_id"            => "MH933g9SNCgxta",
            "metadata"            => [
                "bvs_validation_id"           => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $attributes = [
            'id' => 'LGjQP2ZQxa02as',
            'merchant_id' => $merchant->getId(),
            'status' => 'submitted',
            "shipping_period" => "3-5 days",
            "refund_request_period" => "3-5 days",
            "refund_process_period" => "3-5 days",
            "additional_data" => [
                "support_contact_number" => "9980004017",
                "support_email" => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"
                ],
                "about_us" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/about_us"
                ]
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $attributes);
        $this->fixtures->on(Connection::ASV_WRITER)->create('merchant_website', $attributes);
        $this->fixtures->on('test')->create('merchant_website', $attributes);


        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);



        $websitePolicy = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'         => $merchantDetails->getId(),
            'artefact_identifier' => 'number',
            'artefact_type'       => 'website_policy'
        ]);

        $websiteDetails = $this->getDbEntity('merchant_website', [
            'merchant_id' => $merchantDetails->getId()
        ]);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $verificationData      = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'         => $merchantDetails->getId(),
            'artefact_identifier' => 'number',
            'artefact_type'       => 'mcc_categorisation_website'
        ]);
        $merchantWebsiteDetail = $this->getDbLastEntity('merchant_website');

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);
        $this->assertEquals(1,$merchantWebsiteDetail['grace_period']);

    }

    public function testGetApplicableActivationStatusForRealTimeVerifiedPolicies()
    {
        Mail::fake();
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $this->app['basicauth']->setMerchant($merchant);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => Constant::NEGATIVE_KEYWORDS,
            'artefact_identifier'  => 'number',
            'status'               => 'verified'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id"                  => "MH8gGHX1Vf0bK2",
            "merchant_id"         => $merchant->getId(),
            "artefact_type"       => "website_policy",
            "artefact_identifier" => "number",
            "status"              => "failed",
            "audit_id"            => "MH98mqZfN59Wx8",
            "metadata"            => [
                "refund"              => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score"  => 0.5465,
                        "relevant_details"  => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy"             => [
                    "analysis_result" => [
                        "links_found"       => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score"  => 0.9853,
                        "relevant_details"  => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ]
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

        /*we don't have all urls in merchant_website fixture, once updation is done ,
         then we have to check expected urls are updated in merchant_website entity
        */
        $attributes = [
            'id' => 'LGjQP2ZQxa02as',
            'merchant_id' => $merchant->getId(),
            'status' => 'submitted',
            "shipping_period" => "3-5 days",
            "refund_request_period" => "3-5 days",
            "refund_process_period" => "3-5 days",
            "additional_data" => [
                "support_contact_number" => "9980004017",
                "support_email" => "kakarla.vasanthi@razorpay.com"
            ],
            "merchant_website_details" => [
                "terms" => [
                    "section_status" => 3,
                    "status" => "submitted",
                    "published_url" => "https://sme-dashboard.dev.razorpay.in/policy/LXMbyTLTPeFIwO/terms"
                ],
                "shipping" => [
                    "section_status" => 1,
                    "website"        => [
                        "https://google.com" => [
                            "url" => "https://google.com/shipping",
                            "system_approved" => true,
                        ]
                    ]
                ],
                "contact_us" => [
                    "section_status" => 1,
                    "website"        => [
                        "https://google.com" => [
                            "url" => "https://google.com/contact_us",
                            "system_approved" => true,
                        ]
                    ]
                ]
            ]
        ];
        $this->fixtures->on('live')->create('merchant_website', $attributes);
        $this->fixtures->on(Connection::ASV_WRITER)->create('merchant_website', $attributes);
        $this->fixtures->on('test')->create('merchant_website', $attributes);

        $this->assertEquals(Status::ACTIVATED, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testOCRPassedActivationBlockUnderReview()
    {
        $this->changeEnvToNonTest();

        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce',
            'partner_type' => 'aggregator'
        ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_status" => "under_review",
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
                "clarification_reasons" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "verified",
            "gstin_verification_status" => "verified",
            "cin_verification_status" => "verified",
            "international_activation_flow" => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone" => "L2",
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [
                "result" => [
                    "required" => [
                        "policy disclosure" => [
                            "phrases" => [
                                "Payment" => 1,
                                "Returns" => 1,
                                "Contact us" => 1,
                                "privacy policy" => 1
                            ],
                            "total_count" => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGVEldWInOq",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGw1N8TTDPPCz5",
            "metadata" => [
                "status" => "completed",
                "category" => "ecommerce",
                "subcategory" => "women_clothing",
                "predicted_mcc" => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGHX1Vf0bK2",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "website_policy",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH98mqZfN59Wx8",
            "metadata" => [
                "terms" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/terms-conditions"
                        ],
                        "confidence_score" => 0.5651,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "refund" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score" => 0.5465,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score" => 0.9853,
                        "relevant_details" => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score" => 0.6079,
                        "relevant_details" => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details" => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH95NyX6wcWbG1",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "cin",
            "artefact_identifier" => "number",
            "status" => "initiated",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
                "bvs_validation_id" => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $splitzInput = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $splitzOutput = [
            "response" => []
        ];

        $this->mockSplitzTreatment($splitzInput, $splitzOutput);

        $this->createSignatoryVerified($merchant->getId());

        (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'mcc_categorisation_website'
        ]);

        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }

    public function testL2SubmitActivationsBlockedUnderReview()
    {
        $this->app['rzp.mode'] = 'live';

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['blockMerchantActivations', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('blockMerchantActivations')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantId = "MNwBOeoE15OF9B";

        $merchantDetailData = [
            "merchant_id"                      => $merchantId,
            "contact_name"                     => "what",
            "contact_email"                    => null,
            "business_type"                    => 11,
            "business_name"                    => "businessname",
            "business_dba"                     => "businessname",
            "business_international"           => 0,
            "business_registered_address"      => "place",
            "business_registered_state"        => "CT",
            "business_registered_city"         => "Kanker",
            "business_registered_pin"          => 494334,
            "business_category"                => "others",
            "business_subcategory"             => "others",
            "business_model"                   => "we sell product people invest on our product and we",
            "activation_progress"              => 60,
            "locked"                           => 0,
            "activation_flow"                  => "whitelist",
            "submitted"                        => 0,
            "poi_verification_status"          => "verified",
            "poa_verification_status"          => "verified",
            "bank_details_verification_status" => "verified",
            "live_transaction_done"            => 0,
            "audit_id"                         => "MNwHzZaTS2JrIE",
            "activation_form_milestone"        => "L1",
        ];

        Mail::fake();

        $merchant = $this->fixtures->create('merchant', ['id' => $merchantId]);

        $this->fixtures->create('merchant_detail', $merchantDetailData);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $detailCoreMock->saveMerchantDetails(['activation_form_milestone' => 'L2'], $merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals('under_review', $merchantDetails->getActivationStatus());
    }

    public function testL2SubmitActivationsBlockedAmpNotKQU()
    {
        $this->app['rzp.mode'] = 'live';

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['blockMerchantActivations', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('blockMerchantActivations')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantId = "MNwBOeoE15OF9B";

        $merchantDetailData = [
            "merchant_id"                      => $merchantId,
            "contact_name"                     => "what",
            "contact_email"                    => null,
            "business_type"                    => 11,
            "business_name"                    => "businessname",
            "business_dba"                     => "businessname",
            "business_international"           => 0,
            "business_registered_address"      => "place",
            "business_registered_state"        => "CT",
            "business_registered_city"         => "Kanker",
            "business_registered_pin"          => 494334,
            "business_category"                => "others",
            "business_subcategory"             => "others",
            "business_model"                   => "we sell product people invest on our product and we",
            "activation_progress"              => 60,
            "locked"                           => 0,
            "activation_flow"                  => "whitelist",
            "submitted"                        => 0,
            "poi_verification_status"          => "verified",
            "poa_verification_status"          => "verified",
            "bank_details_verification_status" => "verified",
            "live_transaction_done"            => 0,
            "audit_id"                         => "MNwHzZaTS2JrIE",
            "activation_form_milestone"        => "L1",
        ];

        Mail::fake();

        $merchant = $this->fixtures->create('merchant', ['id' => $merchantId]);

        $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailData);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $detailCoreMock->saveMerchantDetails(['activation_form_milestone' => 'L2'], $merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals('under_review', $merchantDetails->getActivationStatus());
    }

    public function testL2SubmitActivationsBlockedNotAmpNotKQU()
    {
        $this->app['rzp.mode'] = 'live';

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['blockMerchantActivations', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('blockMerchantActivations')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantId = "MNwBOeoE15OF9B";

        $merchantDetailData = [
            "merchant_id"                      => $merchantId,
            "contact_name"                     => "what",
            "contact_email"                    => null,
            "business_type"                    => 11,
            "business_name"                    => "businessname",
            "business_dba"                     => "businessname",
            "business_international"           => 0,
            "business_registered_address"      => "place",
            "business_registered_state"        => "CT",
            "business_registered_city"         => "Kanker",
            "business_registered_pin"          => 494334,
            "business_category"                => "others",
            "business_subcategory"             => "others",
            "business_model"                   => "we sell product people invest on our product and we",
            "activation_progress"              => 60,
            "locked"                           => 0,
            "activation_flow"                  => "whitelist",
            "submitted"                        => 0,
            "poi_verification_status"          => "verified",
            "poa_verification_status"          => "verified",
            "bank_details_verification_status" => "verified",
            "live_transaction_done"            => 0,
            "audit_id"                         => "MNwHzZaTS2JrIE",
            "activation_form_milestone"        => "L1",
        ];

        Mail::fake();

        $merchant = $this->fixtures->create('merchant', ['id' => $merchantId]);

        $this->fixtures->create('merchant_detail:valid_fields', $merchantDetailData);

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'kqu',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $detailCoreMock->saveMerchantDetails(['activation_form_milestone' => 'L2'], $merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals('under_review', $merchantDetails->getActivationStatus());
    }

    //If merchant is not easy merchant, automation activation logic will not apply
    public function testGetApplicableActivationStatusFoNonEasyMerchant()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'not_easy_onboarding'
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

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    //Merchant is a part of certain subcategories for which automation activation logic won't apply
    public function testGetApplicableActivationStatusForExcludedSubcategories()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'education',
            'business_subcategory'      => 'college',
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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
                'category'          => 'financial_services',
                'subcategory'       => 'accounting',
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

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusForExcludedSubcategoriesPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'education',
            'business_subcategory'      => 'college',
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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
                'category'          => 'financial_services',
                'subcategory'       => 'accounting',
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

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    //If additional doc is asked from merchants, merchant will not be moved to KQU/Activated
    public function testGetApplicableActivationStatusIfMerchantNotEligibleFOrAutomation()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
            ->willReturn(false);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testGetApplicableActivationStatusIfMerchantNotEligibleFOrAutomationPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(false);

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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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

        $this->assertEquals(Status::ACTIVATED_MCC_PENDING, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    //If additional doc is asked from merchant and is verified, merchant will moved to KQU/Activated
    public function testGetApplicableActivationStatusIfAdditionalDocRequiredVerified()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->setMethods(['isEligibleForAutomationActivation'])
            ->getMock();

        $detailCoreMock->expects($this->any())
            ->method('isAutoKycDone')
            ->willReturn(true);

        $detailCoreMock->expects($this->any())
            ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
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

    public function testGetApplicableActivationStatusIfAdditionalDocRequiredVerifiedPhantomOnboarding()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
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

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
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

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantEligibleSplitzOn()
    {
        Mail::fake();

        Config::set('pgos.proxy.request.mock', true);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->never())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(true, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->assertEquals(null, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityFetchGatingDetailsInvoice()
    {
        Config::set('pgos.proxy.request.mock', true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $response = (new DetailCore())->fetchMerchantGatingDetailsForInvoicing($merchantDetails->merchant);

        $this->assertEquals(null, $response);
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantInNeedsClarification()
    {

        $this->app['basicauth']->setModeAndDbConnection(Mode::TEST);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);
        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'submitted'                 => true,
            'activation_status'         => 'needs_clarification'
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $merchantId = $merchantDetails->getId();

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);
        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->ba->proxyAuth($merchantDetails->getId());

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals(null, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantEligibleSplitzOnWebsiteMerchant()
    {

        Config::set('pgos.proxy.request.mock', true);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'business_website'          => 'www.google.com',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->never())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $inputNoWebsite = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($inputNoWebsite, $output);

        $inputWebsite = [
            "experiment_id" => "NFTSZXG1J3mryZ",
            "id"            => $merchantId
        ];

        $this->mockSplitzTreatment($inputWebsite, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(true, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->assertEquals(null, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantEligibleSplitzOffWebsiteMerchant()
    {
        $this->app['rzp.mode'] = 'test';

        $feeBasedGatingResponse = [
            "fee_based_gating" => [
                "is_eligible"    => false,
                "order_id"       => DetailConstant::DEFAULT_ORDER_ID,
                "payment_status" => "captured",
                "invoice_sent"   => false
            ]
        ];

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'fetchMerchantGatingDetails'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        $detailCoreMock->expects($this->once())
                       ->method('fetchMerchantGatingDetails')
                       ->willReturn($feeBasedGatingResponse);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'business_website'          => 'www.google.com',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $merchantId = $merchantDetails->getId();

        $inputNoWebsite = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $outputNoWebsite = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($inputNoWebsite, $outputNoWebsite);

        $inputWebsite = [
            "experiment_id" => "NFTSZXG1J3mryZ",
            "id"            => $merchantId
        ];

        $outputWebsite = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($inputWebsite, $outputWebsite);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(false, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantEligibleSplitzOnPhantomOnboarding()
    {
        Mail::fake();

        Config::set('pgos.proxy.request.mock', true);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantDetails->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'phantom_onboarding'
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->once())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(false, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));

        $this->assertEquals(null, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantEligibleSplitzOff()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->once())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(false, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitMerchantBusinessTypeNotEligible()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->once())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(false, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testFeeBasedGatingEligibilityWhileL2SubmitWebsiteMerchant()
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone', 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(false);

        $detailCoreMock->expects($this->exactly(2))
                       ->method('canSubmit')
                       ->willReturn(true);

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        // update activation status will not be called
        $detailCoreMock->expects($this->once())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $merchantId = $merchantDetails->getId();

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantId
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'category'             => '5945',
        ]);

        $input = [
            'activation_form_milestone' => 'L2'
        ];

        $response = $detailCoreMock->saveMerchantDetails($input, $merchant);

        $this->app['basicauth']->setModeAndDbConnection(Mode::LIVE);

        $this->ba->proxyAuth($merchantDetails->getId());

        $this->assertEquals(false, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals(Status::UNDER_REVIEW, $detailCoreMock->getApplicableActivationStatus($merchantDetails));
    }

    public function testFeeBasedGatingEligibilityWhileAsyncMerchantContextUpdate()
    {
        // this tests the async call to update Merchant context
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

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
            'merchant_id'                          => $merchant->getId(),
            'business_type'                        => 2,
            'business_category'                    => 'ecommerce',
            'business_subcategory'                 => 'baby_products',
            'activation_flow'                      => 'whitelist',
            'activation_form_milestone'            => 'L2',
            'poi_verification_status'              => 'verified',
            'promoter_pan'                         => 'AAAPA1234J',
            'activation_status'                    => null,
            'submitted'                            => true,
            'bank_details_verification_status'     => 'verified',
            'gstin_verification_status'            => 'verified',
            'company_pan_verification_status'      => 'verified',
            'bank_details_doc_verification_status' => null,
            Entity::CIN_VERIFICATION_STATUS        => 'verified',
        ]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods([ 'canSubmit', 'updateActivationStatus'])
                               ->getMock();

        $detailCoreMock->expects($this->never())
                       ->method('updateActivationStatus')
                       ->willReturn($merchantDetails);

        $this->createSignatoryVerified($merchant->getId());

        (new UpdateMerchantContext(Mode::LIVE, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();

        $merchantDetailsData = $this->getDbEntity('merchant_detail',
                                                  ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals(null, $merchantDetailsData['activation_status']);

        // activation status of the merchant should not change when merchant is eligible for fee based gating
    }

    public function testFeeBasedGatingEligibilityActivationStatusUpdateToUnderReview()
    {
        Mail::fake();

        Config::set('pgos.proxy.request.mock', true);

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        (new DetailCore())->updateActivationStatus($merchantDetails->merchant,$activationStatusData, $merchantDetails->merchant);

        $this->assertEquals(null, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityFetchGatingDetailsSplitzWebsiteOn()
    {
        Config::set('pgos.proxy.request.mock', true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $response = (new DetailCore())->fetchMerchantGatingDetails($merchantDetails->merchant);

        $this->assertNotNull($response);
    }

    public function testFeeBasedGatingEligibilityFetchGatingDetailsSplitzNoWebsiteOn()
    {
        Config::set('pgos.proxy.request.mock', true);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $response = (new DetailCore())->fetchMerchantGatingDetails($merchantDetails->merchant);

        $this->assertNotNull($response);
    }

    public function testFeeBasedGatingEligibilityFetchGatingDetailsSplitzWebsiteOff()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantDetails->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'true',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "NFTSZXG1J3mryZ",
            "id"            => $merchantDetails->getId()
        ];

        $outputWebsite = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $outputWebsite);

        $response = (new DetailCore())->fetchMerchantGatingDetails($merchantDetails->merchant);

        $this->assertEquals(null, $response);
    }

    public function testFeeBasedGatingEligibilityFetchGatingDetailsSplitzNoWebsiteOff()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id' => $merchantDetails->getId(),
            'user_id' => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantDetails->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $response = (new DetailCore())->fetchMerchantGatingDetails($merchantDetails->merchant);

        $this->assertEquals(null, $response);
    }

    public function testFeeBasedGatingEligibilityActivationStatusUpdateToUnderReviewWebsiteMerchant()
    {
        $this->app['rzp.mode'] = 'test';

        $feeBasedGatingResponse = [
            "fee_based_gating" => [
                "is_eligible"    => false,
                "order_id"       => DetailConstant::DEFAULT_ORDER_ID,
                "payment_status" => "captured",
                "invoice_sent"   => false
            ]
        ];

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['fetchMerchantGatingDetails'])
                               ->getMock();

        $detailCoreMock->expects($this->once())
                       ->method('fetchMerchantGatingDetails')
                       ->willReturn($feeBasedGatingResponse);

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData, $merchantDetails->merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityActivationStatusUpdateToUnderReviewSplitzOff()
    {
        $this->app['rzp.mode'] = 'test';

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_website'          => 'www.google.com',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $input = [
            "experiment_id" => "MSoDYcZPGfjERB",
            "id"            => $merchantDetails->getId()
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'false',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        (new DetailCore())->updateActivationStatus($merchantDetails->merchant,$activationStatusData, $merchantDetails->merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetails->getActivationStatus());
    }

    public function testFeeBasedGatingEligibilityActivationStatusUpdateToUnderReviewNoWebsiteMerchant()
    {
        $this->app['rzp.mode'] = 'test';

        $feeBasedGatingResponse = [
            "fee_based_gating" => [
                "is_eligible"    => false,
                "order_id"       => DetailConstant::DEFAULT_ORDER_ID,
                "payment_status" => "captured",
                "invoice_sent"   => false
            ]
        ];

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['fetchMerchantGatingDetails'])
                               ->getMock();

        $detailCoreMock->expects($this->once())
                       ->method('fetchMerchantGatingDetails')
                       ->willReturn($feeBasedGatingResponse);

        // partnership business type
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'others',
            'business_subcategory'      => null,
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L1',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
        ]);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::UNDER_REVIEW,
        ];

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant,$activationStatusData, $merchantDetails->merchant);

        $merchantDetails = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantDetails->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetails->getActivationStatus());
    }

    public function testisSubCategoryExcludedForAd_And_Marketing_SubcategoryWithRegistedBusinessType()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'services',
            'business_subcategory'      => 'ad_and_marketing',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchantDetailCore = new DetailCore();

        // Create a ReflectionClass object to inspect the DetailCore class
        $reflection = new ReflectionClass($merchantDetailCore);

        // Get a reference to the protected method 'isSubCategoryExcluded'
        $method = $reflection->getMethod('isSubCategoryExcluded');

        // Allow access to the protected method by setting it to be accessible.
        $method->setAccessible(true);

       // Call the protected method 'isSubCategoryExcluded' and store the result
        $result = $method->invoke($merchantDetailCore, $merchantDetails->merchant, $merchantDetails->getBusinessSubcategory(), $merchantDetails->getBusinessType());

        $this->assertEquals(true, $result);

    }

    public function testisSubCategoryExcludedForAd_And_Marketing_SubcategoryWithNonRegistedBusinessType()
    {

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 2,
            'business_category'         => 'services',
            'business_subcategory'      => 'ad_and_marketing',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'under_review',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchantDetailCore = new DetailCore();

        // Create a ReflectionClass object to inspect the DetailCore class
        $reflection = new ReflectionClass($merchantDetailCore);

        // Get a reference to the protected method 'isSubCategoryExcluded'
        $method = $reflection->getMethod('isSubCategoryExcluded');

        // Allow access to the protected method by setting it to be accessible
        $method->setAccessible(true);

        // Call the protected method 'isSubCategoryExcluded' and store the result
        $result = $method->invoke($merchantDetailCore, $merchantDetails->merchant, $merchantDetails->getBusinessSubcategory(), $merchantDetails->getBusinessType());

        $this->assertEquals(true, $result);

    }

    //activate merchant with expirydate for documents should get activated/KQU
    public function testAMerchantActivateWithDocumentsWithExpiryDate()
    {
        $this->ba->adminAuth();
        Mail::fake();
        Config::set('pgos.proxy.request.mock', true);

        $mid = 'KiyM01yZQeU3rD';

        $merchant = $this->fixtures->create('merchant', [
            'id'            => $mid,
            'website'       => null,
            'name'          => null,
            'email'         => null,
            'billing_label' => null,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'      => $mid,
            'contact_email'    => null,
            'promoter_pan'     => 'ABCPE1234E',
            'business_type'    => 11,
            'business_website' => null]);

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $mid,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input = [
            "experiment_id" => "MVNSQzGiHM965H",
            "id"            => $mid,
        ];

        $output = [
            "response" => [
                "variant" => [

                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $detailCoreMock->updateActivationStatus($merchantDetail->merchant, $activationStatusData, $admin);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $mid)->toArray();

        $this->assertEquals('kyc_qualified_unactivated', $merchantDetailData['activation_status']);
    }


    //activate merchant without expirydate for documents should throw error
    public function testAMerchantActivateWithoutDocumentsWithExpiryDate()
    {
        $this->ba->adminAuth();
        Mail::fake();
        $mid = 'MVZCwZJqCLWNkr';
        Config::set('pgos.proxy.request.mock', true);

        $merchant = $this->fixtures->create('merchant', [
            'id'            => $mid,
            'website'       => null,
            'name'          => null,
            'email'         => null,
            'billing_label' => null,
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'merchant_id'      => $mid,
            'contact_email'    => null,
            'business_website' => null]);

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->setMethods(['checkLicenseExpiryValidationForMerchantDocuments'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $mid,
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
            Entity::ACTIVATION_STATUS => Status::KYC_QUALIFIED_UNACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('checkLicenseExpiryValidationForMerchantDocuments')
                       ->willReturnCallback(function() {
                           throw new BadRequestValidationFailureException('License expiry date required for activation.');
                       });
        try
        {

            $response = $detailCoreMock->updateActivationStatus($merchantDetail->merchant, $activationStatusData, $admin);

            $this->assertNull($response);

        }
        catch (\Exception $e)
        {

            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);
            $this->assertStringContainsString('License expiry date required for activation.', $e->getMessage());

        }
    }


    public function testActivationFetchInternalPGOSFromMerchantDetailsGetCall()
    {

        // testing the response regardless it is website or no website merchant

        Config::set('pgos.proxy.request.mock', true);

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
            'business_website'          => null
        ]);

        $this->fixtures->create('bvs_validation', [
            'validation_id'     => 'L61kGPVWKT05QT',
            'artefact_type'     => 'website_policy',
            'owner_id'          => $merchantDetails->getMerchantId(),
            'validation_status' => 'captured'
        ]);

        $this->fixtures->on('live')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        $this->fixtures->on('test')->create('merchant_verification_detail', [
            'id'                   => 'LGjQP2ZQxa02as',
            'merchant_id'          => $merchantDetails->getMerchantId(),
            'artefact_type'        => 'negative_keywords',
            'artefact_identifier'  => 'number',
        ]);

        // sending the request to kafka , because of which merchant was being set in the current context

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

        $response = (new MDS())->fetchMerchantDetails();

        $this->assertEquals(true, $response['fee_based_gating']['is_eligible']);

        $this->assertEquals('order_MblejZXmYhvaqK', $response['fee_based_gating']['order_id']);

        $this->assertEquals('authorized', $response['fee_based_gating']['payment_status']);

        $this->assertEquals(false, $response['fee_based_gating']['invoice_sent']);

        $this->assertEquals(['subcat_desc_1', 'subcat_desc_2', 'subcat_desc_3', 'subcat_desc_4'], $response['suggested_business_subcategories']);

        $this->assertEquals(false, $response['disable_try_again_others_m3']);
    }

    public function testIsMerchantApplicableForWebsiteSections_BankingPlusPrimary()
    {

        $merchant = $this->fixtures->create('merchant', [
            'business_banking'             => true,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('merchant_user', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'product'             => 'primary',
        ]);

        $res = (new WebsiteService())->isMerchantApplicableForWebsiteSections($merchant);

        $this->assertEquals(true, $res);

    }

    //activate merchant with business type as per details should get activated/KQU
    public function testAMerchantActivateWithBusinessType()
    {
        $this->ba->adminAuth();
        Mail::fake();
        Config::set('pgos.proxy.request.mock', true);

        $mid = 'KiyM01yZQeU3rD';

        $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'business_type'             => 11,
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

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchantDetail->getMerchantId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input1 = [
            "experiment_id" => "NlqlZNbm3BZHs3",
            "id"            => $merchantDetail->getMerchantId(),
        ];

        $output1 = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input1, $output1);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        $detailCoreMock->updateActivationStatus($merchantDetail->merchant, $activationStatusData, $admin);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetail->getMerchantId())->toArray();

        $this->assertEquals('activated', $merchantDetailData['activation_status']);
    }


    // should throw error when promoter pan is missing
    public function testAMerchantActivateWithBusinessTypeWithoutPersonalPan()
    {
        $this->ba->adminAuth();
        Mail::fake();
        Config::set('pgos.proxy.request.mock', true);

        $mid = 'KiyM01yZQeU3rD';

        $merchant = $this->fixtures->create('merchant', [
            'id'               => $mid,
            'business_banking' => 1
        ]);

        $merchantDetail = $this->fixtures->create('merchant_detail', [
            'business_type'             => 4,
            'merchant_id'               => $mid,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'company_pan'               => 'AAAPA1234J',
            'activation_status'         => 'kyc_qualified_unactivated',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $this->fixtures->create('stakeholder', ['name' => 'stakeholder name', 'percentage_ownership' => 90, 'merchant_id' => $mid]);

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
                               ->setMethods(['isAutoKycDone'])
                               ->setMethods(['isEligibleForAutomationActivation'])
                               ->getMock();

        $detailCoreMock->expects($this->any())
                       ->method('isAutoKycDone')
                       ->willReturn(true);

        $detailCoreMock->expects($this->any())
                       ->method('isEligibleForAutomationActivation')
                       ->willReturn(true);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $mid]);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $mid,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $input1 = [
            "experiment_id" => "NlqlZNbm3BZHs3",
            "id"            => $merchantDetail->getMerchantId(),
        ];

        $output1 = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input1, $output1);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['workflow']->setWorkflowMaker($admin);

        $basicAuthMock = Mockery::mock('RZP\Http\BasicAuth\BasicAuth')->makePartial();

        $this->app->instance('basicauth', $basicAuthMock);

        $basicAuthMock
            ->shouldReceive('getOrgId')
            ->andReturn(OrgEntity::RAZORPAY_ORG_ID);

        $basicAuthMock
            ->shouldReceive('isAdminAuth')
            ->andReturn(true);

        try
        {

            $response = $detailCoreMock->updateActivationStatus($merchantDetail->merchant, $activationStatusData, $admin);

            $this->assertNull($response);

        }
        catch (\Exception $e)
        {

            $this->assertExceptionClass($e, BadRequestValidationFailureException::class);
            $this->assertStringContainsString('Personal PAN should not be blank.', $e->getMessage());

        }
    }

    public function testIsMerchantApplicableForWebsiteSections_BankingNotPrimary()
    {

        $merchant = $this->fixtures->create('merchant', [
            'business_banking'             => true,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('merchant_user', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'product'             => 'banking',
        ]);

        $res = (new WebsiteService())->isMerchantApplicableForWebsiteSections($merchant);

        $this->assertEquals(false, $res);

    }

    public function testshouldMerchantOnboardViaPGOS_GoogleOAuthMerchant_Success()
    {

        $merchant = $this->fixtures->create('merchant', [
            'signup_via_email'             => true,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('user_device_detail', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'signup_campaign'     => 'easy_onboarding',
            'metadata'            => ['service'=>'pgos']
        ]);

        $res = (new MerchantOnboardingProxyController())->shouldMerchantOnboardViaPGOS($merchant->getId());

        $this->assertEquals(true, $res);

    }

    public function testshouldMerchantOnboardViaPGOS_GoogleOAuthMerchant_Failure1()
    {

        $merchant = $this->fixtures->create('merchant', [
            'signup_via_email'             => true,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('user_device_detail', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'signup_campaign'     => 'easy_onboarding',
            'metadata'            => ['service'=>'api']
        ]);

        $res = (new MerchantOnboardingProxyController())->shouldMerchantOnboardViaPGOS($merchant->getId());

        $this->assertEquals(false, $res);

    }

    public function testshouldMerchantOnboardViaPGOS_GoogleOAuthMerchant_Failure2()
    {

        $merchant = $this->fixtures->create('merchant', [
            'signup_via_email'             => true,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('user_device_detail', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'signup_campaign'     => 'xyz_onboarding',
            'metadata'            => ['service'=>'pgos']
        ]);

        $res = (new MerchantOnboardingProxyController())->shouldMerchantOnboardViaPGOS($merchant->getId());

        $this->assertEquals(true, $res);

    }

    public function testshouldMerchantOnboardViaPGOS_GoogleOAuthMerchant_Failure3()
    {

        $merchant = $this->fixtures->create('merchant', [
            'signup_via_email'             => false,
        ]);

        $user = $this->fixtures->create('user', []);

        $merchantUser = $this->fixtures->create('user_device_detail', [
            'merchant_id'         => $merchant->getId(),
            'user_id'             => $user->getId(),
            'signup_campaign'     => 'easy_onboarding',
            'metadata'            => ['service'=>'pgos']
        ]);

        $res = (new MerchantOnboardingProxyController())->shouldMerchantOnboardViaPGOS($merchant->getId());

        $this->assertEquals(true, $res);

    }

    /**
     * @group web_update
     */
    public function testGetWebsiteVersion()
    {
        $core = new Core();

        $input = [DetailConstant::API_VERSION => DetailConstant::WEBSITE_VERSION_V2];
        $this->assertTrue($core->getWebsiteVersion($input));
        $input = [];
        $this->assertFalse($core->getWebsiteVersion($input));
        $input = [DetailConstant::API_VERSION => DetailConstant::WEBSITE_VERSION_V1];
        $this->assertFalse($core->getWebsiteVersion($input));
    }

    public function getValidateBusinessWebsitesCheckRulesWithVersionData(): array
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CoreTestData.php';
        $this->loadTestData();
        return $this->testData['testValidateBusinessWebsitesCheckRulesWithVersion'];
    }

    public function getValidateBusinessWebsitesV2CheckRulesWithVersionData(): array
    {
        $this->testDataFilePath = __DIR__ . '/helpers/CoreTestData.php';
        $this->loadTestData();
        return $this->testData['testValidateBusinessWebsitesV2CheckRulesWithVersion'];
    }

    /**
     * @group web_update
     * @dataProvider getValidateBusinessWebsitesCheckRulesWithVersionData
     */
    public function testValidateBusinessWebsitesCheckRulesWithVersion($data, $exceptionClass=null, $exceptionMessage=null)
    {
        $validator = new Validator();

        if (! empty ($exceptionClass))
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $data = $data + [
                DetailConstant::BUSINESS_WEBSITE_MAIN_PAGE          => 'https://razorpay.com',
                DetailConstant::BUSINESS_WEBSITE_ABOUT_US           => 'https://razorpay.com/docs',
                DetailConstant::BUSINESS_WEBSITE_CONTACT_US         => 'https://razorpay.com/docs',
                DetailConstant::BUSINESS_WEBSITE_PRICING_DETAILS    => 'https://razorpay.com/docs',
                DetailConstant::BUSINESS_WEBSITE_PRIVACY_POLICY     => 'https://razorpay.com/docs',
                DetailConstant::BUSINESS_WEBSITE_TNC                => 'https://razorpay.com/docs',
                DetailConstant::BUSINESS_WEBSITE_REFUND_POLICY      => 'https://razorpay.com/docs',
            ];

        $this->assertNull($validator->validateInput("business_websites_check", $data));
    }

    /**
     * @group web_update
     * @dataProvider getValidateBusinessWebsitesV2CheckRulesWithVersionData
     */
    public function testValidateBusinessWebsitesV2CheckRulesWithVersion($data, $exceptionClass=null,
    $exceptionMessage=null)
    {
        $validator = new Validator();

        if (! empty ($exceptionClass))
        {
            $this->expectException($exceptionClass);
        }

        if (empty($exceptionMessage) === false)
        {
            $this->expectExceptionMessage($exceptionMessage);
        }

        $this->assertNull($validator->validateInput("business_websites_v2_check", $data));
    }

    /**
     * @group web_update
     */
    public function testPostBusinessWebsiteBVSValidation()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();
        $mockedCore->shouldReceive("validateMCC")->andReturn("randomRequestId");
        $mockedCore->shouldReceive("validateIndividualLink")->andReturn("individualLinkRequestId");
        $mockedCore->shouldReceive("dispatchOCRValidationJob")->andReturn(null);
        $mockedCore->shouldReceive("saveMerchantWebsiteAutomatedOcrCheckDataInCache")->andReturn(null);

        $mockedMerchant = \Mockery::mock('RZP\Models\Merchant\Entity')->makePartial();
        $mockedMerchant->shouldAllowMockingProtectedMethods();
        $mockedMerchant->shouldReceive("isFeatureEnabled")->andReturn(true);
        $this->assignValueThroughReflection($mockedCore, $mockedMerchant, 'merchant');
        $op = $mockedCore->postBusinessWebsiteBVSValidation(DetailConstant::URL_TYPE_WEBSITE, []);
        $this->assertNotEmpty($op);
        $this->assertTrue($op["bvs_validation"]);
        $this->assertEquals($op["mccRequestId"], "randomRequestId");
        $this->assertEquals($op["individualLinkRequestId"], "individualLinkRequestId");
    }

    /**
     * @group web_update
     */
    public function testPostBusinessWebsiteBVSValidationDispatchJobFailure()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();
        $mockedCore->shouldReceive("validateMCC")->andReturn("randomRequestId");
        $mockedCore->shouldReceive("validateIndividualLink")->andReturn("individualLinkRequestId");
        $mockedCore->shouldReceive("addOrRemoveMerchantFeatures")->andReturn(null);

        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('error')->andReturn(null);
        $this->assignValueThroughReflection($mockedCore, $traceMock, 'trace');

        $mockedMerchant = \Mockery::mock('RZP\Models\Merchant\Entity')->makePartial();
        $mockedMerchant->shouldAllowMockingProtectedMethods();
        $mockedMerchant->shouldReceive("getId")->andReturn("TESTMID");
        $this->assignValueThroughReflection($mockedCore, $mockedMerchant, 'merchant');

        $mockedCore->shouldReceive("dispatchOCRValidationJob")->andThrow(new  BadRequestException(
            ErrorCode::BAD_REQUEST_ERROR,
            null,
            [],
            "failed to push to queue"
        ));

        $this->expectException(BadRequestException::class);
        $op = $mockedCore->postBusinessWebsiteBVSValidation(DetailConstant::URL_TYPE_WEBSITE, []);
        $this->assertEmpty($op);
    }

    /**
     * @group web_update
     */
    public function testvalidateMCC()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $mockedMccCatClient = \Mockery::mock('RZP\Models\Merchant\AutoKyc\OcrService\MccCategorisationClient')->makePartial();
        $mockedMccCatClient->shouldAllowMockingProtectedMethods();
        $mockedMccCatClient->shouldReceive('createCategorisationJob')->andReturn([
            "id"        => "SOMEVALIDATIONID",
            "status"    => "completed",
        ]);

        $mockedCore->shouldReceive("getMccCategorisationClient")->andReturn($mockedMccCatClient);

        $op = $mockedCore->validateMCC([]);
        $this->assertEquals("SOMEVALIDATIONID", $op);
    }

    /**
     * @group web_update
     */
    public function testvalidateMCCClientError()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $mockedMccCatClient = \Mockery::mock('RZP\Models\Merchant\AutoKyc\OcrService\MccCategorisationClient')->makePartial();
        $mockedMccCatClient->shouldAllowMockingProtectedMethods();
        $mockedMccCatClient->shouldReceive('createCategorisationJob')->andReturn(["id" => null]);

        $mockedCore->shouldReceive("getMccCategorisationClient")->andReturn($mockedMccCatClient);

        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('error')->andReturn(null);
        $this->assignValueThroughReflection($mockedCore, $traceMock, 'trace');

        $this->expectException(BadRequestException::class);

        $op = $mockedCore->validateMCC([]);
        $this->assertNull($op);
    }

    /**
     * @group web_update
     */
    public function testvalidateIndividualLink()
    {
        $input = [
            DetailConstant::BUSINESS_WEBSITE_MAIN_PAGE         => 'https://razorpay.com',
            DetailConstant::BUSINESS_WEBSITE_CONTACT_US        => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_PRIVACY_POLICY    => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_TNC               => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_REFUND_POLICY     => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_SHIPPING_POLICY   => 'https://razorpay.com/docs',
            DetailConstant::URL_TYPE                           => DetailConstant::URL_TYPE_WEBSITE,
            DetailConstant::API_VERSION                        => DetailConstant::WEBSITE_VERSION_V1,
            DetailConstant::BUSINESS_WEBSITE_USERNAME          => "SOMEUSERNAME",
            DetailConstant::BUSINESS_WEBSITE_PASSWORD          => "SOMEPASSWORD",
        ];

        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $mockedClient = \Mockery::mock('RZP\Models\Merchant\AutoKyc\OcrService\ProcessIndividualLinkVerification\WebsiteIndividualLinkClient')->makePartial();
        $mockedClient->shouldAllowMockingProtectedMethods();

        $mockedClient->shouldReceive('createWebsiteVerificationJob')->andReturn([
            "website_verification_id"   => "SOMEID",
            "status"                    => "completed"
        ]);

        $mockedCore->shouldReceive("getWebsiteIndividualLinkClient")->andReturn($mockedClient);

        $op = $mockedCore->validateIndividualLink($input);

        $this->assertEquals("SOMEID", $op);
    }

    /**
     * @group web_update
     */
    public function testvalidateIndividualLinkError()
    {
        $input = [
            DetailConstant::BUSINESS_WEBSITE_MAIN_PAGE         => 'https://razorpay.com',
            DetailConstant::BUSINESS_WEBSITE_CONTACT_US        => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_PRIVACY_POLICY    => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_TNC               => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_REFUND_POLICY     => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_SHIPPING_POLICY   => 'https://razorpay.com/docs',
            DetailConstant::URL_TYPE                           => DetailConstant::URL_TYPE_WEBSITE,
            DetailConstant::API_VERSION                        => DetailConstant::WEBSITE_VERSION_V1,
            DetailConstant::BUSINESS_WEBSITE_USERNAME          => "SOMEUSERNAME",
            DetailConstant::BUSINESS_WEBSITE_PASSWORD          => "SOMEPASSWORD",
        ];

        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $mockedClient = \Mockery::mock('RZP\Models\Merchant\AutoKyc\OcrService\ProcessIndividualLinkVerification\WebsiteIndividualLinkClient')->makePartial();
        $mockedClient->shouldAllowMockingProtectedMethods();

        $mockedClient->shouldReceive('createWebsiteVerificationJob')->andReturn([]);

        $mockedCore->shouldReceive("getWebsiteIndividualLinkClient")->andReturn($mockedClient);

        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('error')->andReturn(null);
        $this->assignValueThroughReflection($mockedCore, $traceMock, 'trace');

        $this->expectException(BadRequestException::class);

        $op = $mockedCore->validateIndividualLink($input);

        $this->assertEquals("SOMEID", $op);
    }

    /**
     * @group web_update
     */
    public function testpostSaveBusinessWebsiteV1()
    {
        $input = [
            DetailConstant::BUSINESS_WEBSITE_MAIN_PAGE         => 'https://razorpay.com',
            DetailConstant::BUSINESS_WEBSITE_CONTACT_US        => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_PRIVACY_POLICY    => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_TNC               => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_REFUND_POLICY     => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_SHIPPING_POLICY   => 'https://razorpay.com/docs',
            DetailConstant::URL_TYPE                           => DetailConstant::URL_TYPE_WEBSITE,
            DetailConstant::API_VERSION                        => DetailConstant::WEBSITE_VERSION_V1,
            DetailConstant::BUSINESS_WEBSITE_USERNAME          => "SOMEUSERNAME",
            DetailConstant::BUSINESS_WEBSITE_PASSWORD          => "SOMEPASSWORD",
        ];

        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('info')->andReturn(null);
        $this->assignValueThroughReflection($mockedCore, $traceMock, 'trace');
        $mockedCore->shouldReceive('validatePostSaveBusinessWebsiteRequest')->andReturn(null);
        $mockedCore->shouldReceive('postBusinessWebsiteViaWorkflow')->andReturn("RAMDOMID");

        $op = $mockedCore->postSaveBusinessWebsite(DetailConstant::URL_TYPE_WEBSITE, $input);

        $this->assertEquals("RAMDOMID", $op);
    }

    /**
     * @group web_update
     */
    public function testpostSaveBusinessWebsiteV2()
    {
        $input = [
            DetailConstant::BUSINESS_WEBSITE_MAIN_PAGE         => 'https://razorpay.com',
            DetailConstant::BUSINESS_WEBSITE_CONTACT_US        => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_PRIVACY_POLICY    => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_TNC               => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_REFUND_POLICY     => 'https://razorpay.com/docs',
            DetailConstant::BUSINESS_WEBSITE_SHIPPING_POLICY   => 'https://razorpay.com/docs',
            DetailConstant::URL_TYPE                           => DetailConstant::URL_TYPE_WEBSITE,
            DetailConstant::API_VERSION                        => DetailConstant::WEBSITE_VERSION_V2,
            DetailConstant::BUSINESS_WEBSITE_USERNAME          => "SOMEUSERNAME",
            DetailConstant::BUSINESS_WEBSITE_PASSWORD          => "SOMEPASSWORD",
        ];

        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $traceMock = \Mockery::mock('\Razorpay\Trace\Logger')->makePartial();
        $traceMock->shouldReceive('info')->andReturn(null);
        $this->assignValueThroughReflection($mockedCore, $traceMock, 'trace');
        $mockedCore->shouldReceive('validatePostSaveBusinessWebsiteRequest')->andReturn(null);
        $mockedCore->shouldReceive('postBusinessWebsiteBVSValidation')->andReturn("RAMDOMID");

        $op = $mockedCore->postSaveBusinessWebsite(DetailConstant::URL_TYPE_WEBSITE, $input);

        $this->assertEquals("RAMDOMID", $op);
    }

    protected function assignValueThroughReflection($core, $merchant, $key): void
    {
        $reflector = new \ReflectionClass($core);
        $property = $reflector->getProperty($key);
        $property->setAccessible( true );
        $property->setValue($core, $merchant);
    }

    public function testPGOSMerchantPatchDetails()
    {
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

        $merchantId = $merchantDetails->getId();

        $input = [
            "business_category"             => "ecommerce",
            "business_subcategory"          => "gifting",
            "international_activation_flow" => "whitelist",
            "business_name"                 => "Laksh",
            "reset_methods"                 => true
        ];

        $splitzInput = [
            "experiment_id" => "M6rMvIakjZTm68",
            "id"            => $merchantId,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->pgosProxyController->shouldReceive('handlePGOSProxyRequests')->andReturn();

        return $this->getSplitzMock()
                    ->shouldReceive('evaluateRequest')
                    ->with($splitzInput)
                    ->andReturn($output);

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'id'           => $merchantId,
            'country_code' => 'IN',
            'signup_via_email' => false
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantId,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
            'metadata'        => [
                'service' => 'pgos'
            ]
        ]);

        try
        {
            (new DetailCore())->patchMerchantDetails($merchant, $input);
        }
        catch (\Exception $e)
        {
           $this->assertNotNull($e);
        }
    }

    public function testPGOSMerchantPatchDetailsWhenMerchantActivated()
    {
        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'business_type'             => 3,
            'business_category'         => 'ecommerce',
            'business_subcategory'      => 'baby_products',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => 'activated',
            'submitted'                 => true,
            'business_website'          => 'https://google.com',
        ]);

        $merchantId = $merchantDetails->getId();

        $input = [
            "business_category"             => "ecommerce",
            "business_subcategory"          => "gifting",
            "international_activation_flow" => "whitelist",
            "business_name"                 => "Laksh",
            "reset_methods"                 => true
        ];


        $this->pgosProxyController->shouldNotReceive('handlePGOSProxyRequests');

        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'id'           => $merchantId,
            'country_code' => 'IN',
            'activated' => true
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantId,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
            'metadata'        => [
                'service' => 'pgos'
            ]
        ]);


        (new DetailCore())->patchMerchantDetails($merchant, $input);


        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals($merchantDetail->getBusinessSubcategory(), 'gifting');


    }

    public function testGetUpdatedPosClarificationResponse()
    {

        $input = [
            "clarification_details" => [
                "pos_nc_count" => 1,
                "shop_front"   => [
                    "comments" => [
                        [
                            "nc_count"     => 1,
                            "created_at"   => 12132312,
                            "message_from" => 'merchant',
                            "comment_data" => [
                                "text" => "some comment",
                                "type" => "some type"
                            ]
                        ],
                        [
                            "nc_count"     => 1,
                            "created_at"   => 12132311,
                            "message_from" => 'admin',
                            "comment_data" => [
                                "text" => "admin some comment"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = (new DetailCore())->getUpdatedPosClarificationResponse($input);

        $expectedResponse = [
            "clarification_reasons" => [
                'pos_nc_count' => 1,
                'shop_front'   => [
                    ['from'        => 'merchant',
                     'nc_count'    => 1,
                     'is_current'  => true,
                     'created_at'  => 12132312,
                     'reason_code' => 'some comment',
                     'reason_type' => 'some type'],
                    ['from'        => 'admin',
                     'nc_count'    => 1,
                     'is_current'  => true,
                     'created_at'  => 12132311,
                     'reason_code' => 'admin some comment']
                ]
            ]
        ];

        $this->assertArraySubset($expectedResponse,$response);

    }

    // In fee based gating flow, when mechant done payment and call comes to api form pgos, eligible activation status is kyc but input has under_review so here merchant moves new eligible activation status
    public function testSubmitMerchantInternalFeeBasedFlowKYC()
    {
        Queue::fake();

        Config::set('pgos.proxy.request.mock', true);

        Config::set('pgos.proxy.request.response', true);

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

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
                "clarification_reasons" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ],
                "clarification_reasons_v2" => [
                    "business_website" => [
                        [
                            "from" => "admin",
                            "nc_count" => 1,
                            "is_current" => true,
                            "field_value" => "https://www.hempstrol.com/",
                            "reason_code" => "code",
                            "reason_type" => "custom"
                        ]
                    ]
                ]
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "verified",
            "gstin_verification_status" => "verified",
            "cin_verification_status" => "verified",
            "international_activation_flow" => "whitelist",
            "company_pan_doc_verification_status" => "verified",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [
                "result" => [
                    "required" => [
                        "policy disclosure" => [
                            "phrases" => [
                                "Payment" => 1,
                                "Returns" => 1,
                                "Contact us" => 1,
                                "privacy policy" => 1
                            ],
                            "total_count" => 4,
                            "unique_count" => 4
                        ]
                    ],
                    "prohibited" => [
                    ]
                ],
                "website_url" => "https://www.ilovesarees.com/"
            ],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGVEldWInOq",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "mcc_categorisation_website",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MGw1N8TTDPPCz5",
            "metadata" => [
                "status" => "completed",
                "category" => "ecommerce",
                "subcategory" => "women_clothing",
                "predicted_mcc" => 5621,
                "confidence_score" => 0.94
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGHX1Vf0bK2",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "website_policy",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH98mqZfN59Wx8",
            "metadata" => [
                "terms" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/terms-conditions"
                        ],
                        "confidence_score" => 0.5651,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "refund" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/returns"
                        ],
                        "confidence_score" => 0.5465,
                        "relevant_details" => [
                        ],
                        "validation_result" => true
                    ]
                ],
                "privacy" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesares.myshopify.com/pages/privacy-policy"
                        ],
                        "confidence_score" => 0.9853,
                        "relevant_details" => [
                            "note" => "Privacy Policy is majorly about First Party Collection/Use, Third Party Sharing/Collection, Data Security, Introductory/Generic, Practice not covered. Privacy Policy includes the following attributes Does, Explicit, Implicit, Collect on website, Unspecified, Identifiable, Aggregated or anonymized, Contact, Cookies and tracking elements, Basic service/feature, Additional service/feature, Marketing, Analytics/Research, Personalization/Customization, Service operation and security, Unspecified, User with account, Opt-in, Dont use service/feature, Opt-out via contacting company, Browser/device privacy controls, Collection, First party use, Unnamed third party, Named third party, Receive/Shared with, Track on first party website/app, Secure data transfer"
                        ],
                        "validation_result" => true
                    ]
                ],
                "shipping" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/policies/shipping-policy"
                        ],
                        "confidence_score" => 0.6079,
                        "relevant_details" => [
                            "5 ",
                            "7 ",
                            "10 "
                        ],
                        "validation_result" => true
                    ]
                ],
                "contact_us" => [
                    "analysis_result" => [
                        "links_found" => [
                            "https://ilovesarees.com/pages/contact-us"
                        ],
                        "relevant_details" => [
                            "9043222190"
                        ],
                        "validation_result" => true
                    ]
                ],
                "policy_details_file" => "file_MH8jjmKC3s9G3a"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH95NyX6wcWbG1",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "cin",
            "artefact_identifier" => "number",
            "status" => "initiated",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
                "bvs_validation_id" => "MH95Nv3tZnT44P",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $this->mockAllSplitzTreatment($output);

        $this->app->instance("rzp.mode", Mode::LIVE);

       // (new UpdateMerchantContext(Mode::TEST, $merchantDetails->getId(), 'L61kGPVWKT05QT'))->handle();
        $response = (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
                                                                            'merchant_id'             => $merchantDetails->getId(),
                                                                            'activation_status'       => 'under_review',
                                                                            'fee_based_gating_flow'   => true,
                                                                            'action'                  => 'UPDATE_ACTIVATION_STATUS'
                                                                        ]);

        $verificationData = $this->getDbEntity('merchant_verification_detail', [
            'merchant_id'          => $merchantDetails->getId(),
            'artefact_identifier'  => 'number',
            'artefact_type'        => 'mcc_categorisation_website'
        ]);

        $this->assertNotEmpty($response);

        $this->assertIsObject($response);

        $this->assertNotEmpty($response->getActivationStatus());
        $this->assertNotEmpty($response->getContactName());
        $this->assertNotEmpty($response->getBusinessType());
        $this->assertNotEmpty($response->getId());


        $this->assertEquals('verified', $verificationData['status']);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $businessDetail['metadata']['activation_status']);

        $this->assertEquals(Status::KYC_QUALIFIED_UNACTIVATED, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }


    // In fee based gating flow, when mechant done payment and call comes to api form pgos, eligible activation status is under_review but input has under_review so here merchant moves UR state
    public function testSubmitMerchantInternalFeeBasedFlowUR()
    {
        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);


        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "intiated",
            "gstin_verification_status" => "failed",
            "international_activation_flow" => "whitelist",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "failed",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $this->app->instance("rzp.mode", Mode::TEST);

        $response = (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'merchant_id'             => $merchantDetails->getId(),
            'activation_status'       => 'under_review',
            'fee_based_gating_flow'   => true,
            'action'                  => 'UPDATE_ACTIVATION_STATUS'
        ]);

        $this->assertNotEmpty($response);

        $this->assertIsObject($response);

        $this->assertNotEmpty($response->getActivationStatus());
        $this->assertNotEmpty($response->getContactName());
        $this->assertNotEmpty($response->getBusinessType());
        $this->assertNotEmpty($response->getId());

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }

    // moving merchant to ur without fee based flow
    public function testSubmitMerchantInternalWithoutFeeBasedFlowUR()
    {
        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);


        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "intiated",
            "gstin_verification_status" => "failed",
            "international_activation_flow" => "whitelist",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "failed",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $this->app->instance("rzp.mode", Mode::TEST);

        $response = (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'merchant_id'             => $merchantDetails->getId(),
            'activation_status'       => 'under_review',
            'action'                  => 'UPDATE_ACTIVATION_STATUS'
        ]);

        $this->assertNotEmpty($response);

        $this->assertIsObject($response);

        $this->assertNotEmpty($response->getActivationStatus());
        $this->assertNotEmpty($response->getContactName());
        $this->assertNotEmpty($response->getBusinessType());
        $this->assertNotEmpty($response->getId());

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        $businessDetail = $this->getDbEntity('merchant_business_detail', ['merchant_id' => $merchant->getId()]);

        $this->assertEquals(Status::UNDER_REVIEW, $merchantDetail[Entity::ACTIVATION_STATUS]);
    }

    public function testSalesAssistedSubmitWebsiteMerchantUR()
    {
        Queue::fake();

        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);


        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "intiated",
            "gstin_verification_status" => "failed",
            "international_activation_flow" => "whitelist",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH8gGahWhUb2Ew",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "negative_keywords",
            "artefact_identifier" => "number",
            "status" => "failed",
            "audit_id" => "MGlLFiUREeLueC",
            "metadata" => [],
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933kTShboSkS",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "signatory_validation",
            "artefact_identifier" => "number",
            "status" => "verified",
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH987XQBcsGzp8",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "certificate_of_incorporation",
            "artefact_identifier" => "doc",
            "status" => "verified",
            "audit_id" => "MGvjUr37Z52dur",
            "metadata" => [
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH96sJegGOKRdr",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "gstin",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH96sMk4xoPIZB",
            "metadata" => [
                "bvs_validation_id" => "MH96qkWCFYMRh5",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->fixtures->create('merchant_verification_detail', [
            "id" => "MH933fs4DyoDny",
            "merchant_id" => $merchant->getId(),
            "artefact_type" => "bank_account",
            "artefact_identifier" => "number",
            "status" => null,
            "audit_id" => "MH933g9SNCgxta",
            "metadata" => [
                "bvs_validation_id" => "MH932IVI0TvVOs",
                "signatory_validation_status" => "verified"
            ]
        ]);

        $this->createSignatoryVerified($merchant->getId());

        // block_merchant_activations experiment id
        $input = [
            "experiment_id" => "KxkO63MKPtxKy9",
            "id"            => $merchant->getId(),
        ];

        $output = [
            "response" => []
        ];

        // for regular merchants, there should be no call to block_merchant_activations experiment
        $this->getSplitzMock()
             ->shouldReceive('evaluateRequest')
             ->times(0)
             ->with($input)
             ->andReturn($output);

        $this->app->instance("rzp.mode", Mode::TEST);
        Config::set('pgos.proxy.request.mock', true);

        $this->app->instance("rzp.mode", Mode::TEST);
        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
                                              ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_fetch_pos_activation_flow';
                                              })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_fetch_activation_status';
                                              })->andReturn(["pos_activation_status"=>""]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pos_fetch_all_order';
                                              })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
                                              ->withArgs(function ($operation, $request, $additionalArgs) {
                                                  return $operation === 'merchant_pgos_update_activation_status';
                                              })->andReturn(["success"=>true]);

        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

        (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'merchant_id'             => $merchantDetails->getId(),
            'action'                  => 'SALES_ASSISTED_FORM_SUBMISSION'
        ]);

        $merchantDetail = $this->getDbLastEntity('merchant_detail');

        //$this->assertEquals(Status::UNDER_REVIEW, $merchantDetail[Entity::ACTIVATION_STATUS]);

    }


    public function testAddRejectionReasonsInUpdatePosActivationStatus()
    {
        Mail::fake();

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
            ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_fetch_pos_activation_flow';
            })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_fetch_activation_status';
            })->andReturn(["pos_activation_status"=>'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pos_fetch_all_order';
            })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->times(1)
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_update_activation_status';
            })->andReturn(["pos_activation_status"=>'rejected']);


        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);
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
            DetailConstant::POS_ACTIVATION_STATUS => Status::REJECTED,
            Entity::REJECTION_REASONS => [[
                Reason\Entity::REASON_CODE => 'reject_on_risk_remarks',
                Reason\Entity::REASON_CATEGORY => 'risk_related_rejections'
            ]]
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant, false);
    }

    public function testAddNullRejectionReasonsInUpdatePosActivationStatus()
    {
        Mail::fake();

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('shouldMerchantOnboardViaPGOS')
            ->andReturn(true);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_fetch_pos_activation_flow';
            })->andReturn(["pos_activation_flow"=>'whitelist']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_fetch_activation_status';
            })->andReturn(["pos_activation_status"=>'under_review']);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pos_fetch_all_order';
            })->andReturn(["order_list"     => []]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->times(1)
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_update_activation_status';
            })->andReturn([
                "code"                   => "invalid_argument",
                "msg"                    => "validation_failure: Rejection Reason Not Present",
                "downstream_status_code" => 400,
                "meta"                   => ["description" => "something bad happened", "field"=> ""]
            ]);


        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);
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
            DetailConstant::POS_ACTIVATION_STATUS => Status::REJECTED,
            Entity::REJECTION_REASONS => null,
            Entity::REJECTION_OPTION => null
        ];

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setMerchant($merchantDetails->merchant);

        (new DetailCore())->updatePosActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant, false);
    }


    // moving merchant to ur without fee based flow
    public function testPosV1CaseCreation()
    {
        $this->mockRazorxTreatment();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);


        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "intiated",
            "gstin_verification_status" => "failed",
            "international_activation_flow" => "whitelist",
            "activation_form_milestone" => "L2",
        ]);
        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());


        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'assisted_onboarding',
            'metadata'        => [
                'service' => 'pgos'
            ]
        ]);

        $eventData =  (new DetailCore)->pushKafkaEventOnPOSActivationFormSubmit($merchant, "pos_activation_form_submission_kafka_event");
        $this->assertArraySubset([
                                     "entity_id"                  => $merchant->getId(),
                                     "entity_name"                => "merchant",
                                     "event_type"                  => "pos_activation_form_submission_kafka_event"],$eventData);

        $deviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole( $merchant->getId());
        //$this->assertEquals('easy_onboarding', $deviceDetail->getSignupCampaign());

        $eventData =  (new DetailCore)->pushKafkaEventOnPOSActivationFormSubmit($merchant, "pos_v2_activation_form_submission_kafka_event");
        $this->assertArraySubset([
                                     "entity_id"                  => $merchant->getId(),
                                     "entity_name"                => "merchant",
                                     "event_type"                  => "pos_v2_activation_form_submission_kafka_event"],$eventData);

        $deviceDetail = $this->repo->user_device_detail->fetchByMerchantIdAndUserRole( $merchant->getId());
        //$this->assertEquals('assisted_onboarding', $deviceDetail->getSignupCampaign());
    }



    public function testPGOSMerchantSaveMerchantResponseToClarifications()
    {
        Config::set('pgos.proxy.request.mock', true);

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

        $merchantId = $merchantDetails->getId();

        $input = [
            'poi_verification_status'                => 'verified',
            'poa_verification_status'                => 'verified',
            'bank_details_verification_status'       => 'verified',
            'gstin_verification_status'              => 'not_matched',
            'shop_establishment_verification_status' => 'incorrect_details',
            'onboarding_type'                        => null,
            'submit'                                 => 0
        ];

        // Create a mock object for the NeedsClarificationProxyController class
        $pgosProxyController = Mockery::mock('RZP\Http\Controllers\NeedsClarificationProxyController');

// Define the expectations for the handlePGOSProxyRequests method
        $pgosProxyController
            ->shouldReceive('handlePGOSProxyRequests')
            ->andReturn([
                            "code"                   => "internal",
                            "msg"                    => "validation_failure: Processing failed because input does not have all fields",
                            "downstream_status_code" => 500,
                            "meta"                   => ["cause" => "errors.Error"]
                        ]);
        $merchant = $this->fixtures->edit('merchant', $merchantId, [
            'id'           => $merchantId,
            'country_code' => 'IN'
        ]);

        $this->app['basicauth']->setMerchant($merchant);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchantDetails->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchantId,
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding',
            'metadata'        => [
                'service' => 'pgos'
            ]
        ]);

        try
        {
            (new ClarificationDetailService())->saveMerchantResponseToClarifications($input, $merchantId);
        }
        catch (\Exception $e)
        {
            $this->assertNotNull($e);
            $this->assertEquals($e->getError()->getInternalErrorCode(), "SERVER_ERROR_PGOS_PROCESSNG_FAILED");
            $this->assertEquals($e->getError()->getPublicErrorCode(), "SERVER_ERROR");

        }
    }

    public function testHasSocialMediaUrls()
    {

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'website_details' => [
                'social_media_urls' => [
                    [
                        'platform' => 'facebook',
                        'url' => 'https://www.facebook.com/Meta/'
                    ],
                    [
                        'platform' => 'twitter',
                        'url' => 'https://www.twitter.com/_anant_mishra/'
                    ]
                ],
            ],
        ]);

        $response = (new DetailCore())->hasSocialMediaUrls($merchant);

        $this->assertTrue($response);
    }

    public function testHasSocialMediaUrls1()
    {

        $merchant = $this->fixtures->create('merchant', [
            'hold_funds' => false,
            'activated'  => true,
            'activated_at' => now()->timestamp - 24*60*60,
        ]);

        $this->fixtures->create('merchant_business_detail', [
            'merchant_id' => $merchant->getId(),
            'website_details' => [
                'social_media_urls' => [
                ],
            ],
        ]);

        $response = (new DetailCore())->hasSocialMediaUrls($merchant);

        $this->assertFalse($response);
    }

    private function setupForUpdateActivationStatusTest($merchantId)
    {
        Mail::fake();

        $detailCoreMock = $this->getMockBuilder(DetailCore::class)
            ->setMethods(['isAutoKycDone'])
            ->getMock();

        $input = [
            "experiment_id" => "LS64r2cBVZVT5b",
            "id"            => $merchantId,
        ];

        $riskTagsExpInput = [
            "experiment_id" => "NoCu6Q4qi4sIRM",
            "id"            => $merchantId,
        ];

        $output = [
            "response" => [
                "variant" => [
                    "name" => 'variables',
                ]
            ]
        ];

        $riskTagsExpOutput = [
            "response" => [
                "variant" => [
                    "name" => 'enable',
                ]
            ]
        ];

        $this->mockSplitzTreatment($input, $output);

        $this->mockSplitzTreatment($riskTagsExpInput, $riskTagsExpOutput);

        $admin = $this->fixtures->connection('live')->create('admin', [
            'org_id' => OrgEntity::RAZORPAY_ORG_ID,
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        $this->app['basicauth']->setOrgId(OrgEntity::RAZORPAY_ORG_ID);

        $this->app['workflow']->setWorkflowMaker($admin);

        return $detailCoreMock;
    }

    public function testUpdateActivationStatusFromURToActivatedRiskTag()
    {
        $merchant = $this->fixtures->create('merchant', ['business_banking' => 1]);

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

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Merchant cannot be activated when risk tags are assigned to the merchant');

        (new MerchantCore())->addTags($merchantId, [
            'tags'  => ['risk_review_suspend'],
        ], false);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }

    public function testUpdateActivationStatusFromURToActivatedRiskTagLinkedAccount()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001',
                ['parent_id' => '10000000000000'],
            ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
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

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        (new MerchantCore())->addTags($merchantId, [
            'tags'  => ['risk_review_suspend'],
        ], false);

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('activated', $merchantDetailData['activation_status']);
    }

    public function testUpdateActivationStatusFromNullToAMPRiskTags()
    {
        $merchant = $this->fixtures->create('merchant', ['business_banking' => 1]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        (new MerchantCore())->addTags($merchantId, [
            'tags'  => ['risk_review_suspend'],
        ], false);

        $this->expectException(BadRequestValidationFailureException::class);

        $this->expectExceptionMessage('Merchant cannot be activated when risk tags are assigned to the merchant');

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);
    }

    public function testUpdateActivationStatusFromURToActivatedSuccessNoRiskTags()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001',
            ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
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

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('activated', $merchantDetailData['activation_status']);
    }

    public function testUpdateActivationStatusFromNullToActivatedSuccessNoRiskTags()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001',
            ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED,
        ];

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('activated', $merchantDetailData['activation_status']);
    }

    public function testUpdateActivationStatusFromNullToAMPSuccessNoRiskTags()
    {
        $merchant = $this->fixtures->create('merchant:marketplace_account',
            ['id' => '10000000000001',
            ]);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            'merchant_id'               => $merchant->getId(),
            'business_type'             => 4,
            'business_category'         => 'financial_services',
            'business_subcategory'      => 'accounting',
            'activation_flow'           => 'whitelist',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_status'         => null,
            'submitted'                 => true,
            'business_Website'          => null
        ]);

        $merchantId = $merchantDetails->getMerchantId();

        $detailCoreMock = $this->setupForUpdateActivationStatusTest($merchantId);

        $activationStatusData = [
            Entity::ACTIVATION_STATUS => Status::ACTIVATED_MCC_PENDING,
        ];

        $detailCoreMock->updateActivationStatus($merchantDetails->merchant, $activationStatusData, $merchantDetails->merchant);

        $merchantDetailData = $this->getDbEntityById('merchant_detail', $merchantDetails->getMerchantId())->toArray();

        $this->assertEquals('activated_mcc_pending', $merchantDetailData['activation_status']);
    }

    public function testPostCommentsInWorkflow()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();
        $mockedCore->shouldAllowMockingMethod("postEncryptedComment");

        $actionCore = \Mockery::mock('RZP\Models\Workflow\Action\Core');
        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');
        $commentCore = \Mockery::mock('RZP\Models\Comment\Core');
        $commentEntity = \Mockery::mock('RZP\Models\Comment\Entity');

        $mockMorph = \Mockery::mock(MorphTo::class)->makePartial();
        $mockMorph->shouldAllowMockingProtectedMethods();

        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);

        $mockMorph->shouldReceive("associate")->with($actionEntity)->andReturn();
        $mockedCore->shouldReceive("postEncryptedComment")->andReturn();
        $actionCore->shouldReceive("fetchOpenActionOnEntityOperation")->andReturn(collect([$actionEntity]));
        $commentEntity->shouldReceive("entity")->andReturn($mockMorph);
        $commentCore->shouldReceive("create")->andReturn($commentEntity);

        $repoMock->shouldReceive("saveOrFail")->with($commentEntity)->andReturn();
        $mockedCore->shouldReceive("getActionCore")->andReturn($actionCore);
        $mockedCore->shouldReceive("getCommentCore")->andReturn($commentCore);

        $this->assignValueThroughReflection($mockedCore, $repoMock, 'repo');

        $this->assertEmpty($mockedCore->postCommentsInWorkflow(
            "10000000000000",
            "merchant_detail",
            Name::UPDATE_MERCHANT_WEBSITE,
            "some comment",
            []
        ));
    }

    public function testPostEncryptedCommentWebsite()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');
        $commentCore = \Mockery::mock('RZP\Models\Comment\Core');
        $commentEntity = \Mockery::mock('RZP\Models\Comment\Entity');
        $mockMorph = \Mockery::mock(MorphTo::class);
        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);

        $mockMorph->shouldReceive("associate")->with($actionEntity)->andReturn();
        $commentEntity->shouldReceive("entity")->andReturn($mockMorph);
        $commentCore->shouldReceive("create")->andReturn($commentEntity);
        $repoMock->shouldReceive("saveOrFail")->with($commentEntity)->andReturn();
        $mockedCore->shouldReceive("getCommentCore")->andReturn($commentCore);

        $this->assignValueThroughReflection($mockedCore, $repoMock, 'repo');

        $input = [
            DetailConstant::ENCRYPT_DATA_FORMAT_TYPE => DetailConstant::ENCRYPT_DATA_FORMAT_WEBSITE,
            DetailConstant::USERNAME => "USERNAME",
            DetailConstant::PASSWORD => "PASSWORD",
        ];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }

    public function testPostEncryptedCommentApp()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');
        $commentCore = \Mockery::mock('RZP\Models\Comment\Core');
        $commentEntity = \Mockery::mock('RZP\Models\Comment\Entity');
        $mockMorph = \Mockery::mock(MorphTo::class);
        $repoMock = Mockery::mock('\RZP\Base\RepositoryManager', [$this->app]);

        $mockMorph->shouldReceive("associate")->with($actionEntity)->andReturn();
        $commentEntity->shouldReceive("entity")->andReturn($mockMorph);
        $commentCore->shouldReceive("create")->andReturn($commentEntity);
        $repoMock->shouldReceive("saveOrFail")->with($commentEntity)->andReturn();
        $mockedCore->shouldReceive("getCommentCore")->andReturn($commentCore);

        $this->assignValueThroughReflection($mockedCore, $repoMock, 'repo');

        $input = [
            DetailConstant::ENCRYPT_DATA_FORMAT_TYPE => DetailConstant::ENCRYPT_DATA_FORMAT_APP,
            DetailConstant::USERNAME => "USERNAME",
            DetailConstant::PASSWORD => "PASSWORD",
        ];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }

    public function testPostEncryptedCommentInvalidType()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');

        $input = [
            DetailConstant::ENCRYPT_DATA_FORMAT_TYPE => "RANDOM",
            DetailConstant::USERNAME => "USERNAME",
            DetailConstant::PASSWORD => "PASSWORD",
        ];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }


    public function testPostEncryptedCommentInvalidTypeEmptyPassword()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');

        $input = [
            DetailConstant::ENCRYPT_DATA_FORMAT_TYPE => "RANDOM",
            DetailConstant::USERNAME => "USERNAME",
        ];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }

    public function testPostEncryptedCommentInvalidTypeEmptyUsername()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');

        $input = [
            DetailConstant::ENCRYPT_DATA_FORMAT_TYPE => "RANDOM",
        ];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }

    public function testPostEncryptedCommentInvalidTypeEmptyInput()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $actionEntity = \Mockery::mock('RZP\Models\Workflow\Action\Entity');

        $input = [];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [$actionEntity, $input]));
    }

    public function testPostEncryptedCommentInvalidTypeNullWorkflowAction()
    {
        $mockedCore = \Mockery::mock('RZP\Models\Merchant\Detail\Core')->makePartial();
        $mockedCore->shouldAllowMockingProtectedMethods();

        $input = [];

        $this->assertEmpty($this->callProtectedMethod($mockedCore, "postEncryptedComment", [null, $input]));
    }

    private function callProtectedMethod($instance, $method, array $args)
    {
        $class  = new \ReflectionClass(get_class($instance));
        $method = $class->getMethod($method);

        $method->setAccessible(true);

        return $method->invokeArgs($instance, $args);
    }

    protected function assignProtectedVariableThroughReflection($instance, $variableName, $value): mixed
    {
        $reflector = new \ReflectionClass($instance);
        $property = $reflector->getProperty($variableName);
        $property->setAccessible( true );
        $property->setValue($instance, $value);

        return $instance;
    }

    public function testSubcategoryForCategory()
    {
        $merchantId = 'OlyFnGyZQeEKrF';

        $merchant = $this->fixtures->create('merchant', [
            'id'                => $merchantId
        ]);

        $merchantDetails = $this->fixtures->create('merchant_details',[
            'merchant_id'       => $merchantId,
            'business_type'     => 5
        ]);

        $input = [
            "business_category" => "services",
            "business_subcategory" => "pharmacy"
        ];

        $this->pgosProxyController->shouldNotReceive('handlePGOSProxyRequests');

        $this->app['basicauth']->setMerchant($merchant);

        try
        {
            (new DetailCore())->patchMerchantDetails($merchant, $input);
        }
        catch (\Exception $e)
        {
            $this->assertNotNull($e);
        }
    }

    public function testEmptyInputSubcategory()
    {
        $merchantId = 'OlyFnGyZQeEKrF';

        $merchant = $this->fixtures->create('merchant', [
            'id'                => $merchantId
        ]);

        $merchantDetails = $this->fixtures->create('merchant_details',[
            'merchant_id'               => $merchantId,
            'business_type'             => 5,
            'business_category'         => 'it_and_software',
            'business_subcategory'      => 'consulting_and_outsourcing',
            'activation_flow'           => 'whitelist',
            'promoter_pan'              => 'AAAPA1234J',
            'activation_form_milestone' => 'L2',
            'poi_verification_status'   => 'verified',
            'activation_status'         => 'activated',
            'submitted'                 => true
        ]);


        $input = [
            "business_category" => "services"
        ];

        $this->app['basicauth']->setMerchant($merchant);

        $this->pgosProxyController->shouldNotReceive('handlePGOSProxyRequests');

        (new DetailCore())->patchMerchantDetails($merchant, $input);

        $merchantDetail = $this->getDbEntity('merchant_detail', ['merchant_id' => $merchantId]);

        $this->assertEquals($merchantDetail->getBusinessCategory(), 'services');

        $this->assertEquals($merchantDetail->setBusinessSubcategory(), 'consulting_and_outsourcing');

    }
    public function testSubmitMerchantInternalWithActionasActivatePosAndMarkKycVerified()
    {
        Queue::fake();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);


        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "contact_mobile"=>"7355206348",
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_website" => "https://www.hempstrol.com/",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "business_operation_city" => "Delhi",
            "business_operation_pin" => 110022,
            "business_category" => "ecommerce",
            "bank_account_number"=>"1234567890",
            "bank_branch_ifsc"=>"ICIC0000009",
            "bank_account_name"=>"CHIZRINZ INFOWAY PRIVATE LIMITED",
            "business_subcategory" => "fashion_and_lifestyle",
            "steps_finished" => [
            ],
            "activation_progress" => 80,
            "locked" => 0,
            "activation_flow" => "whitelist",
            "issue_fields" => "business_website",
            "submitted" => 1,
            "poi_verification_status" => "verified",
            "poa_verification_status" => "verified",
            "bank_details_verification_status" => "verified",
            "kyc_clarification_reasons" => [
                "nc_count" => 1,
                "additional_details" => [
                ],
            ],
            "live_transaction_done" => 0,
            "additional_websites" => [
            ],
            "company_pan_verification_status" => "intiated",
            "gstin_verification_status" => "failed",
            "international_activation_flow" => "whitelist",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'easy_onboarding'
        ]);

        $this->app->instance("rzp.mode", Mode::LIVE);

        (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'action'                  => 'ACTIVATE_POS_AND_MARK_KYC_VERIFIED'
        ]);

        $bankAccount = $this->getDbLastEntity('bank_account', 'live');

        $balance = $this->getDbLastEntity('balance', 'live');

        $balanceConfig = $this->getDbLastEntity('balance_config', 'live');

        //check if there is a entry in bank account
        $this->assertNotNull($bankAccount);
        $this->assertEquals('ICIC0000009', $bankAccount['ifsc_code']);
        $this->assertEquals('1234567890', $bankAccount['account_number']);
        $this->assertEquals('CHIZRINZ INFOWAY PRIVATE LIMITED', $bankAccount['beneficiary_name']);
        //check if there is a entry in balance
        $this->assertNotNull($balance);
        //check if there is a entry in balance config
        $this->assertNotNull($balanceConfig);
        $this->assertEquals($balance['id'], $balanceConfig['balance_id']);
    }

    public function testSubmitSalesAssistedActivationForm()
    {
        Queue::fake();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_fetch_activation_status';
            })->andReturn(["pos_activation_status"=>""]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->times(1)
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_update_activation_status';
            })->andReturn(["pos_activation_status"=>'under_review']);
        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "contact_mobile"=>"7355206348",
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'assisted_onboarding'
        ]);

        (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'action'                  => 'SALES_ASSISTED_FORM_SUBMISSION'
        ]);

        $eventData =  (new DetailCore)->pushKafkaEventOnPOSActivationFormSubmit($merchant, "pos_v2_activation_form_submission_kafka_event");
        $this->assertArraySubset([
            "entity_id"                  => $merchant->getId(),
            "entity_name"                => "merchant",
            "event_type"                  => "pos_v2_activation_form_submission_kafka_event"],$eventData);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->getId());

        // Asserting if form is locked or not after final submission of sale assisted form
        $this->assertTrue($merchantDetail->isLocked());
    }

    public function testIfFormIsLockedAfterMerchantRespondedToNC()
    {
        Queue::fake();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $kafkaProducerMock = \Mockery::mock('overload:RZP\Services\KafkaProducer'); // 'overload' allows Mockery to mock the instantiation.
        $kafkaProducerMock->shouldReceive('produce')
            ->once()
            ->andReturn(true);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_fetch_activation_status';
            })->andReturn(["pos_activation_status"=>"needs_clarification"]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->times(1)
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_update_activation_status';
            })->andReturn(["pos_activation_status"=>'under_review']);
        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "contact_mobile"=>"7355206348",
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'assisted_onboarding'
        ]);

        (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'submit'                  => 1,
            'onboarding_type'         => "pos"
        ]);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->getId());

        // Asserting form is Locked after Merchant Responded to Nc
        $this->assertTrue($merchantDetail->isLocked());

    }

    public function testSubmitMerchantInternalForPartner()
    {
        Queue::fake();

        $merchant = $this->fixtures->create('merchant', [
            'category'  => '5945',
            'category2' => 'ecommerce'
        ]);

        $kafkaProducerMock = \Mockery::mock('overload:RZP\Services\KafkaProducer'); // 'overload' allows Mockery to mock the instantiation.
        $kafkaProducerMock->shouldReceive('produce')
            ->once()
            ->andReturn(true);

        $MerchantOnboardingProxyControllerMock = \Mockery::mock(MerchantOnboardingProxyController::class)->makePartial();
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_fetch_activation_status';
            })->andReturn(["pos_activation_status"=>"needs_clarification"]);
        $MerchantOnboardingProxyControllerMock->shouldReceive('handlePGOSProxyRequests')
            ->times(1)
            ->withArgs(function ($operation, $request, $additionalArgs) {
                return $operation === 'merchant_pgos_update_activation_status';
            })->andReturn(["pos_activation_status"=>'under_review']);
        $this->app->instance('MerchantOnboardingProxyController', $MerchantOnboardingProxyControllerMock);

        $merchantDetails = $this->fixtures->create('merchant_detail', [
            "merchant_id" => $merchant->getId(),
            "contact_name" => "Mohan",
            "business_type" => 4,
            "contact_mobile"=>"7355206348",
            "business_name" => "Private Limited",
            "business_dba" => "DBA",
            "business_international" => 0,
            "business_registered_address" => "address",
            "business_registered_state" => "DL",
            "business_registered_city" => "Delhi",
            "business_registered_pin" => 110022,
            "business_operation_address" => "address",
            "business_operation_state" => "DL",
            "activation_form_milestone" => "L2",
        ]);

        $merchantUser = $this->fixtures->user->createUserForMerchant($merchant->getId());

        $this->fixtures->create('user_device_detail', [
            'merchant_id'     => $merchant->getId(),
            'user_id'         => $merchantUser->getId(),
            'signup_campaign' => 'partner_assisted_onboarding'
        ]);

        (new MDS())->submitMerchantInternal($merchantDetails->getId(), [
            'submit'                  => 1,
            'onboarding_type'         => "pos"
        ]);

        $merchantDetail = $this->getDbEntityById('merchant_detail', $merchant->getId());

        // Asserting form is Locked after Merchant Responded to Nc
        $this->assertTrue($merchantDetail->isLocked());
    }

}
