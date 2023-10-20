<?php

namespace Unit\Models\Merchant\Stakeholder;

use Config;
use Google\Protobuf\StringValue;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Account\V1\MerchantStakeholder;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use Rzp\Accounts\Merchant\V1\MerchantDetailSaveRequest;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use Rzp\Accounts\Merchant\V1\Stakeholder;
use Rzp\Accounts\Merchant\V1\StakeholderResponse;
use Rzp\Accounts\Merchant\V1\StakeholderResponseByMerchantId;
use Rzp\Accounts\Merchant\V1\StakeholderSaveRequest;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderWrapper;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Stakeholder\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;

class RepositoryTest extends RepositoryTestHelper
{

    private $stakeholderEntityJson1 = '{
                            "id": "CzmiCwTPCL3t2R",
                            "audit_id": "testtesttest",
                            "merchant_id": "CzmiBzNQPErfdT",
                            "email": "123@gmail.com",
                            "name": "test",
                            "phone_primary": "1234567890",
                            "phone_secondary": "1234567890",
                            "director": 1,
                            "executive": 1,
                            "percentage_ownership": 100,
                            "poi_identification_number": "1234567890",
                            "poi_status": "verified",
                            "poa_status": "verified",
                            "verification_metadata": "{\"test\":\"test\"}",
                            "notes": "{\"test\":\"test\"}",
                            "created_at": "12342",
                            "updated_at": "12342",
                            "deleted_at": null,
                            "pan_doc_status": "verified",
                            "aadhaar_esign_status": "verified",
                            "aadhaar_pin": "1234",
                            "aadhaar_linked": 1,
                            "aadhaar_verification_with_pan_status": "verified",
                            "bvs_probe_id": "1234"
                }';

    private $stakeholderEntityJson2 = '{
                         "id": "CzmiD0rBAGOort",
                        "audit_id": "testtesttest",
                        "merchant_id": "CzmiBzNQPErfdT",
                         "email": "123@ggmail.com",
                        "name": "test",
                        "phone_primary": "12343567890",
                        "phone_secondary": "12534567890",
                        "director": 1,
                        "executive": 1,
                        "percentage_ownership": 100,
                        "poi_identification_number": "1234567890",
                        "poi_status": "vevrified",
                        "poa_status": "vevrified",
                        "verification_metadata": "{\"te3st\":\"test\"}",
                        "notes": "{\"t3est\":\"test\"}",
                        "created_at": "123342",
                        "updated_at": "122342",
                        "deleted_at": null,
                        "pan_doc_status": "verified",
                        "aadhaar_esign_status": "verified",
                        "aadhaar_pin" : "1234",
                        "aadhaar_linked": 1,
                        "aadhaar_verification_with_pan_status": "verified",
                        "bvs_probe_id": "1234"
           }';

    private $stakeholderEntityJson3 = '{
                        "id": "CzmiD1TWLy9PKw",
                        "audit_id": "testtesttest",
                        "merchant_id": "CzmiBzNQPErfdT",
                         "email": "123@gfmail.com",
                        "name": "test",
                        "phone_primary": "123f4567890",
                        "phone_secondary": "12f34567890",
                        "director": 1,
                        "executive": 1,
                        "percentage_ownership": 100,
                        "verification_metadata": null,
                        "poi_identification_number": "1234d567890",
                        "poi_status": "verfified",
                        "poa_status": "veriffied",
                         "notes": "{\"t3est\":\"test\"}",
                        "created_at": "12342",
                        "updated_at": "123442",
                        "deleted_at": null,
                        "pan_doc_status": "veriffied",
                        "aadhaar_esign_status": "vefrified",
                        "aadhaar_pin" : "12w34",
                        "aadhaar_linked": 1,
                        "aadhaar_verification_with_pan_status": "verfified",
                        "bvs_probe_id": "123f4"
     }';

    private $merchantEntityJson1 = '{
        "id": "CzmiBzNQPErfdT",
        "org_id": "100000razorpay",
        "default_refund_speed": "normal",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "country_code": "IN"
     }';

    private $merchantDetailEntityJson1 = '{
        "merchant_id": "CzmiBzNQPErfdT",
        "additional_websites": "[\"https://razorpay.in\"]",
        "steps_finished": "[1,2,3]",
        "kyc_clarification_reasons": "{\"nc_count\": 1, \"additional_details\": [], \"clarification_reasons\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}, \"clarification_reasons_v2\": {\"aadhar_front\": [{\"from\": \"admin\", \"nc_count\": 1, \"created_at\": 1663228017, \"is_current\": true, \"reason_code\": \"illegible_doc\", \"reason_type\": \"predefined\"}]}}",
        "kyc_additional_details": "{\"business_description\": \"description\"}",
        "custom_fields": "{\"tnc\":{\"accepted\":1,\"ip_address\":\"201.189.12.23\",\"time\":1561110415,\"url\":\"https:\\/\\/rtll.com\\/tnc\",\"user_agent\":\"Mozilla\\/5.0 (Macintosh; Intel Mac OS X 10_14_4)\"},\"apps\":[{\"name\":\"Ratnalal Shopping App\",\"links\":{\"android\":\"https:\\/\\/playstore.google.com\\/appId\\/122\",\"ios\":\"https:\\/\\/appstore.com\\/appId\\/122\"}}]}",
        "client_applications": "{\"ios\": [{\"url\": \"appstore.acme.org\", \"name\": \"Acme\"}], \"android\": [{\"url\": \"playstore.acme.org\", \"name\": \"Acme\"}]}",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "fund_addition_va_ids": "{\"fee_credit\": \"va_LIc0SnP6OMuXxH\"}",
        "industry_category_code_type": "iIfMMCYyTFbVSgHjgxBo"
    }';

    private $sampleSpltizOutput = [
        'status_code' => 200,
        'response' => [
            'id' => '10000000000000',
            'project_id' => 'K1ZCHBSn7hbCMN',
            'experiment' => [
                'id' => 'K1ZaAGS9JfAUHj',
                'name' => 'CallSyncDviationAPI',
                'exclusion_group_id' => '',
            ],
            'variant' => [
                'id' => 'K1ZaAHZ7Lnumc6',
                'name' => 'Dummy Enabled',
                'variables' => [
                    [
                        'key' => 'enabled',
                        'value' => 'true',
                    ]
                ],
                'experiment_id' => 'K1ZaAGS9JfAUHj',
                'weight' => 100,
                'is_default' => false
            ],
            'Reason' => 'bucketer',
            'steps' => [
                'sampler',
                'exclusion',
                'audience',
                'assign_bucket'
            ]
        ]
    ];


    public function testGetStakeholderByMerchantId()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_stakeholder_read_by_merchant_id', 'K1ZaAHZ7Lnumc6');

        $this->createStakeholderInDatabase($this->stakeholderEntityJson1);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson2);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson3);


        $stakeholder = new StakeholderWrapper();

        // prepare expected data //
        $stakeholderEntity1 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson1);
        $stakeholderEntity2 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson2);
        $stakeholderEntity3 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson3);


        // prepare mocks //
        $stakeholderProto1 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson1);
        $stakeholderProto2 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson2);
        $stakeholderProto3 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson3);


        $stakeholderResponse = new StakeholderResponseByMerchantId();
        $stakeholderResponse->setStakeholders([$stakeholderProto1, $stakeholderProto2, $stakeholderProto3]);

        // Test Case 1: ExclusionFlow false - Splitz should never be called - Request should go to account service - Merchant Stakeholder is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $stakeholder->getDefaultRequestMetaData())->willReturn([$stakeholderResponse, null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2, $stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        // Test Case 2: ExclusionFlow true - Splitz should never be called - Request should not go to account service - Merchant Stakeholder is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(0))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $stakeholder->getDefaultRequestMetaData())->willReturn([$stakeholderResponse, null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(true);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2, $stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        # Test Case 3: ExclusionFlow false - Splitz should never be called - Request should go to account service - Merchant Stakeholder is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $stakeholder->getDefaultRequestMetaData())->willReturn([new StakeholderResponseByMerchantId(), null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        // Test Case 4: ExclusionFlow false - Splitz should never be called - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $stakeholder->getDefaultRequestMetaData())->willReturn([null, new GrpcError(\Grpc\STATUS_ABORTED, "new")]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2, $stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));
    }

    public function testStakholderRepositoryFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_stakeholder_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createStakeholderInDatabase($this->stakeholderEntityJson1);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson2);
        $this->createStakeholderInDatabase($this->stakeholderEntityJson3);

        $stakeholderEntity1 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson1);
        $stakeholderEntity2 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson2);

        $stakeholderProto1 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson1);

        // Test Case 1 - ExclusionFlow true - Splitz should never be called - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));


        // Test Case 2 - ExclusionFlow false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals(["id" => $stakeholderEntity1->getId()], $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R", ["id"]));

        // Test Case 3 - ExclusionFlow false - Select by multiple Ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([$stakeholderEntity1->toArray(), $stakeholderEntity2->toArray()]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"]));

        // Test Case 4 - ExclusionFlow false - Select by multiple Ids, filter by fields - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([["id" => "CzmiCwTPCL3t2R"], ["id" => "CzmiD0rBAGOort"]]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"], ["id"]));

        $stakeholderResponse = (new StakeholderResponse())->setStakeholder($stakeholderProto1);

        // Test Case 5 - ExclusionFlow false - Splitz should never be called - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 0);
        $this->setStakeholderMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $stakeholderResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 6 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 7 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 8 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

        // Test Case 9 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

    }

    public function testStakeholderSaveOrFailAsv() {


        /*
         *  Base Setup for the test
         *
         */

        $repo = new Repository();
        $stakeholder = new StakeholderWrapper();

        $stakeholderEntity1 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson1);
        $stakeholderEntity2 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson2);
        $stakeholderEntity3 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson3);

        $stakeholderProto1 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson1);
        $stakeholderProto1->setCreatedAt(0);
        $stakeholderProto1->setUpdatedAt(0);
        $stakeholderProto2 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson2);
        $stakeholderProto2->setCreatedAt(0);
        $stakeholderProto2->setUpdatedAt(0);
        $stakeholderProto3 = $this->getStakeholderProtoFromJson($this->stakeholderEntityJson3);
        $stakeholderProto3->setCreatedAt(0);
        $stakeholderProto3->setUpdatedAt(0);

        $saveResponse = (new SaveResponse())->setStakeholders([new EntitySaveResponse(
                [
                    "id" => "K9UzmvitzJwyS4",
                    "created_at" => 10,
                    "updated_at" => 10,
                    "audit_id" => "newtesttesttest"
                ]
            )
            ]
        );

        /*
         * Test 1: The save or fail ASV should not be reached if write is not enabled.
         * Comment this testcase when writes are to be enabled.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = false;
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($stakeholderEntity1);


        /*
        * Test  2: The save or fail ASV should not be reached if Splitz is off.
        */

        // false, false
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["false", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($stakeholderEntity2);


        // true, false
        $this->setSplitzWithOutputForBulk(["true", "false"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($stakeholderEntity2);

        // false, true
        $this->setSplitzWithOutputForBulk(["false", "true"], 1);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($stakeholderEntity2);

        /*
        * Test  3: The save or fail ASV should not be reached if Splitz throws exception.
        */
        $this->setSplitzWithOutputForBulk(["false", "true"], 1, true);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($stakeholderEntity2);

        /*
        * Test  4-1: Save Or should work fine if splitz is on, created updated_at should be updated.
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $stakeholderEntity1 = $this->getStakeholderEntityFromJson($this->stakeholderEntityJson1);
        $stakeholderSaveRequest = new StakeholderSaveRequest();
        $stakeholderSaveRequest->setStakeholder($stakeholderProto1);
        $stakeholderSaveRequest->setFields(array_keys($stakeholderEntity1->getDirty()));
        $saveRequest = (new SaveRequest())->setStakeholderSaveRequests([
            $stakeholderSaveRequest
                ],
        );
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();

        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $stakeholder->getAsvSdkClient()->setWriteService($writeService);

        $stakeholderEntity1->audit_id = "testtesttest";
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($stakeholderEntity1);
        self::assertEquals(10, $stakeholderEntity1['created_at']);
        self::assertEquals(10, $stakeholderEntity1['updated_at']);

        /*
         * Test  4-2: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $stakeholderEntity2->audit_id = "testtesttest";
        $stakeholderProto2->setAuditId((new StringValue())->setValue("testtesttest"));
        $stakeholderSaveRequest->setStakeholder($stakeholderProto2);
        $stakeholderSaveRequest->setFields(array_keys($stakeholderEntity2->getDirty()));
        $saveRequest = (new SaveRequest())->setStakeholderSaveRequests([
            $stakeholderSaveRequest
        ],
        );
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $stakeholder->getAsvSdkClient()->setWriteService($writeService);
        $stakeholderEntity2->audit_id = "testtesttest";
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($stakeholderEntity2);
        self::assertEquals(10, $stakeholderEntity2['created_at']);
        self::assertEquals(10, $stakeholderEntity2['updated_at']);

        /*
         * Test  4-3: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $stakeholderSaveRequest->setStakeholder($stakeholderProto3);
        $stakeholderSaveRequest->setFields(array_keys($stakeholderEntity3->getDirty()));
        $saveRequest = (new SaveRequest())->setStakeholderSaveRequests([
            $stakeholderSaveRequest
        ],
        );
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $stakeholder->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        $repo->saveOrFail($stakeholderEntity3);
        self::assertEquals(10, $stakeholderEntity3['created_at']);
        self::assertEquals(10, $stakeholderEntity3['updated_at']);

        /*
        * Test 5: Save or fail should fail, if Splitz is on, asv throw exception.
        */

        $saveResponse->getStakeholders()[0]->setCreatedAt(15);
        $saveResponse->getStakeholders()[0]->setUpdatedAt(15);
        $stakeholderEntity3->audit_id = "testtesttest";
        $stakeholderProto3->setAuditId((new StringValue())->setValue("testtesttest"));
        $stakeholderSaveRequest->setStakeholder($stakeholderProto3);
        $stakeholderSaveRequest->setFields(array_keys($stakeholderEntity3->getDirty()));
        $saveRequest = (new SaveRequest())->setStakeholderSaveRequests([
            $stakeholderSaveRequest
        ],
        );
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->
        with($saveRequest)->
        willThrowException(new \RZP\Exception\BaseException("I am ASV Exception.", "ASV_SERVER_ERROR"));
        $stakeholder->getAsvSdkClient()->setWriteService($writeService);
        $repo =  new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isWriteFlowOrFailure', 1, true, null);
        try {
            $stakeholderEntity3->audit_id = "testtesttest";
            $repo->saveOrFail($stakeholderEntity3);
            self::fail("Exception was expected.");
        } catch (\Exception $e) {
            self::assertEquals(\Illuminate\Database\QueryException::class, get_class($e));
            self::assertEquals("ASV_SERVER_ERROR", $e->getCode());
            self::assertEquals("I am ASV Exception. (SQL: )", $e->getMessage());
            self::assertEquals([], $e->getBindings());
            self::assertEquals("", $e->getSql());

            //created_at, updated_at not changed
            self::assertEquals(10, $stakeholderEntity3['created_at']);
            self::assertEquals(10, $stakeholderEntity3['updated_at']);
        }
    }

    public function testStakeholderAssociation()
    {
        $entitiesData = [
            [
                "relationName" => "stakeholder",
                "asvEntity" => new StakeholderWrapper(),
                "asvResponseEntity" => new StakeholderResponseByMerchantId(),
                "setterFunctionName" => 'setStakeholder',
                "responseSetterFunctionName" => 'setStakeholders',
                "entityRepo" => new Repository(),
                "entityRepoName" =>  'stakeholder',
                "entityName" => "stakeholder",
                "entityData" => $this->stakeholderEntityJson1,
                "entityClass" => new StakeholderEntity(),
                "entityProtoClass"  => new \Rzp\Accounts\Merchant\V1\Stakeholder(),
                "AssociatedEntityRepo" => new \RZP\Models\Merchant\Detail\Repository(),
                "AssociatedEntityName" => "merchant_detail",
                "AssociatedEntityData" => $this->merchantDetailEntityJson1,
                "AssociatedEntityClass" =>  new MerchantDetailEntity(),
                "mockBuilderInterface" => "Razorpay\Asv\Interfaces\StakeholderInterface",
                "merchant_id" => "CzmiBzNQPErfdT",
                "shouldEntityNeedsToBeCreated" => true,
                "isDependentEntity" => true,
                "dependentEntity" => [
                    [
                        "dependentEntityName" => "merchant",
                        "dependentEntityData" => $this->merchantEntityJson1,
                        "dependentEntityClass" => new MerchantEntity(),

                    ]
                ]
            ]
        ];

        $this->runTestsForImplicitJoin($entitiesData);
    }

    private function getOutputForDbCalls($repo, $id, $columns = null, $connectiontype = null)
    {
        if ($columns === null) {
            $findOrFailValue = $repo->findOrFail($id);
            $findOrFailPublicValue = $repo->findOrFailPublic($id);
        } else {
            $findOrFailValue = $repo->findOrFail($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailPublic($id, $columns, $connectiontype);
        }

        $findOrFailValueArray = $findOrFailValue->toArray();
        $findOrFailPublicValueArray = $findOrFailPublicValue->toArray();


        if (is_array($id) && $columns == null) {
            for ($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValueArray[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValueArray[$i]['audit_id'] = "testtesttest";
            }
        } elseif ($columns == null) {
            $findOrFailValueArray['audit_id'] = "testtesttest";
            $findOrFailPublicValueArray['audit_id'] = "testtesttest";
        }

        if (is_array($id)) {
            $findOrFailValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailValueArray);
            $findOrFailPublicValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailPublicValueArray);
        }

        $this->assertEquals($findOrFailValueArray, $findOrFailPublicValueArray);
        $this->assertEquals($this->getOutputForRawDbCalls($repo, $id, $columns, $connectiontype), $findOrFailPublicValueArray);

        return $findOrFailValueArray;
    }

    private function getOutputForRawDbCalls($repo, $id, $columns = null, $connectiontype = null)
    {
        if ($columns === null) {
            $findOrFailValue = $repo->findOrFailDatabase($id);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id);
        } else {
            $findOrFailValue = $repo->findOrFailDatabase($id, $columns, $connectiontype);
            $findOrFailPublicValue = $repo->findOrFailPublicDatabase($id, $columns, $connectiontype);
        }

        $findOrFailValueArray = $findOrFailValue->toArray();
        $findOrFailPublicValueArray = $findOrFailPublicValue->toArray();

        if (is_array($id) && $columns == null) {
            for ($i = 0; $i < count($findOrFailValue); $i++) {
                $findOrFailValueArray[$i]['audit_id'] = "testtesttest";
                $findOrFailPublicValueArray[$i]['audit_id'] = "testtesttest";
            }
        } elseif ($columns == null) {
            $findOrFailValueArray['audit_id'] = "testtesttest";
            $findOrFailPublicValueArray['audit_id'] = "testtesttest";
        }

        if (is_array($id)) {
            $findOrFailValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailValueArray);
            $findOrFailPublicValueArray = $this->convertEntitiesToAssociativeArrayBasedOnId($findOrFailPublicValueArray);
        }

        $this->assertEquals($findOrFailValueArray, $findOrFailPublicValueArray);
        return $findOrFailValueArray;
    }

    private function getExceptionForFindOrFailAsv($repo, $id, $grpcError)
    {
        try {
            $this->setStakeholderMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
            $repo->findOrFailAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindOrFailPublicAsv($repo, $id, $grpcError)
    {
        try {
            $this->setStakeholderMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
            $repo->findOrFailPublicAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailDatabase($repo, $id)
    {
        try {
            $repo->findOrFailDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindAndFailPublicDatabase($repo, $id)
    {
        try {
            $repo->findOrFailPublicDatabase($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function splitzShouldThrowException($count = 1)
    {
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willThrowException(new \Exception("sample"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitzMock;
    }

    private function setSplitzWithOutput($output, $count = 1)
    {
        $splitz = $this->sampleSpltizOutput;
        $splitz["response"]["variant"]["variables"][0]["value"] = $output;
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly($count))->method('evaluateRequest')->willReturn($splitz);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return $splitz;
    }

    public function getMockAsvRouterInRepository($method, $count, $response, $error)
    {
        $asvRouterMock = $this->getAsvRouteMock([$method]);
        if ($error === null) {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willReturn($response);
        } else {
            $asvRouterMock->expects($this->exactly($count))->method($method)->willThrowException($error);
        }

        return $asvRouterMock;
    }

    private function setStakeholderMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $stakeholder = new StakeholderWrapper();
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly($count))->method($method)->with($id, $stakeholder->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

    }

    private function convertEntitiesToAssociativeArrayBasedOnId(array $arrays): array
    {
        $result = [];
        foreach ($arrays as $array) {
            $array['audit_id'] = "testtesttest";
            $result[$array['id']] = $array;
        }

        return $result;
    }

    private function createStakeholderInDatabase($json)
    {

        $this->fixtures->create("stakeholder",
            $this->getStakeholderEntityFromJson($json)->toArrayWithRawValuesForAccountService()
        );
    }


    private function getStakeholderProtoFromJson(string $json): Stakeholder
    {
        $stakeholderProto = new Stakeholder();
        $stakeholderProto->mergeFromJsonString($json, false);
        return $stakeholderProto;
    }

    private function getStakeholderEntityFromJson(string $json): StakeholderEntity
    {
        $stakeholderArray = json_decode($json, true);
        $stakeholderEntity = new StakeholderEntity();
        $stakeholderEntity->setRawAttributes($stakeholderArray);
        return $stakeholderEntity;
    }

    protected function createSplitzMock(array $methods = ['evaluateRequest'])
    {

        $splitzMock = $this->getMockBuilder(SplitzService::class)
            ->onlyMethods($methods)
            ->getMock();
        $this->app->instance('splitzService', $splitzMock);

        return $splitzMock;
    }

    private function getMockClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\StakeholderInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    private function getAsvRouteMock($methods = [])
    {
        return $this->getMockBuilder(AsvRouter::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }
}
