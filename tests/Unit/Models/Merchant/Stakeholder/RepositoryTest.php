<?php

namespace Unit\Models\Merchant\Stakeholder;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\Stakeholder;
use Rzp\Accounts\Merchant\V1\StakeholderResponse;
use Rzp\Accounts\Merchant\V1\StakeholderResponseByMerchantId;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderWrapper;
use RZP\Models\Merchant\Stakeholder\Entity as StakeholderEntity;
use RZP\Models\Merchant\Stakeholder\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

class RepositoryTest extends TestCase
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

        // Test Case 1: SaveRoute false - Splitz is on - Request should go to account service - Merchant Stakeholder is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly(1))->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 2: SaveRoute true - Splitz is on - Request should not go to account service - Merchant Stakeholder is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        # Test Case 3: SaveRoute false - Splitz is on - Request should go to account service - Merchant Stakeholder is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 4: SaveRoute false - Splitz is off - Request should not go to account service

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$stakeholderResponse, null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2, $stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        // Test Case 5: SaveRoute false - Splitz is off - Request  should not  go to account service - Merchant Stakeholder not Found

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$stakeholderResponse, null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2,$stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        // Test Case 6: SaveRoute false - Splitz call fails - Request should not go to account service.

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("some error occurred while calling splitz"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $stakeholderMockClient = $this->getMockClient();
        $stakeholderMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$stakeholderResponse, null]);
        $stakeholder->getAsvSdkClient()->setStakeholder($stakeholderMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedStakeholders = (new StakeholderEntity())->newCollection([$stakeholderEntity1, $stakeholderEntity2, $stakeholderEntity3]);
        $gotStakeholders = $repo->fetchStakeholders("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedStakeholders->toArray()), self::convertEntitiesToAssociativeArrayBasedOnId($gotStakeholders->toArray()));

        // Test Case 7: SaveRoute false - Splitz is on - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 1 - SaveRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals(["id" => $stakeholderEntity1->getId()], $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R", ["id"]));

        // Test Case 5 - SaveRoute false - Select by multiple Ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([$stakeholderEntity1->toArray(), $stakeholderEntity2->toArray()]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"]));

        // Test Case 5 - SaveRoute false - Select by multiple Ids, filter by fields - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([["id" => "CzmiCwTPCL3t2R"], ["id" => "CzmiD0rBAGOort"]]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"], ["id"]));

        $stakeholderResponse = (new StakeholderResponse())->setStakeholder($stakeholderProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setStakeholderMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $stakeholderResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 7 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setStakeholderMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $stakeholderResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($stakeholderEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 8 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 9 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 10 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

        // Test Case 11 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

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

    private function getMockAsvRouterInRepository($method, $count, $response, $error)
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
