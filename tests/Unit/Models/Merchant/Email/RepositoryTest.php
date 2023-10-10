<?php

namespace Unit\Models\Merchant\Email;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\Email;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use Rzp\Accounts\Merchant\V1\MerchantDocumentSaveRequest;
use Rzp\Accounts\Merchant\V1\MerchantEmailResponse;
use Rzp\Accounts\Merchant\V1\MerchantEmailResponseByMerchantId;
use Rzp\Accounts\Merchant\V1\MerchantEmailSaveRequest;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use RZP\Models\Merchant\Email\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

class RepositoryTest extends TestCase
{

    private $splitzResponse = [
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
    ];

    private $merchantEmailEntityJson1 = ' {
            "id": "CzmiCwTPCL3t2R",
            "type": "support",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469734,
            "updated_at": 1564469734
        }';

    private $merchantEmailEntityJson2 = '{
            "id": "CzmiD0rBAGOort",
            "type": "refund",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469733,
            "updated_at": 1564469733
        }';

    private $merchantEmailEntityJson3 = ' {
            "id": "CzmiD1TWLy9PKw",
            "type": "partner_dummy",
            "email": "support@rtll.com",
            "phone": "9999999999",
            "policy": "24x7 support",
            "url": "https://rtll.com/support/",
            "merchant_id": "CzmiBzNQPErfdT",
            "verified": 0,
            "created_at": 1564469732,
            "updated_at": 1564469732
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


    public function testGetEmailByMerchantId()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_merchant_email_read_by_merchant_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson1);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson2);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson3);


        $merchantEmail = new MerchantEmail();

        // prepare expected data //
        $merchantEmailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);
        $merchantEmailEntity2 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson2);


        // prepare mocks //
        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);
        $merchantEmailProto2 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson2);
        $merchantEmailProto3 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson3);


        $merchantEmailResponse = new MerchantEmailResponseByMerchantId();
        $merchantEmailResponse->setEmails([$merchantEmailProto1, $merchantEmailProto2, $merchantEmailProto3]);

        // Test Case 1: ExclusionFlow false - Splitz should never be called - Request should  go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 2: ExclusionFlow true - Splitz should never be called - Request should not go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(true);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 3: ExclusionFlow false - Splitz should never be called - Request should go to account service - Merchant Email is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([new MerchantEmailResponseByMerchantId(), null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 4: ExclusionFlow false -  Splitz should never be called - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([null, new GrpcError(\Grpc\STATUS_ABORTED, "new")]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));
    }

    public function testGetEmailByTypeAndMerchantId()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_merchant_email_read_by_type_and_merchant_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson1);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson2);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson3);

        $merchantEmail = new MerchantEmail();

        $merchantEmailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);

        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);
        $merchantEmailProto2 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson2);
        $merchantEmailProto3 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson3);

        $merchantEmailResponse = new MerchantEmailResponseByMerchantId();
        $merchantEmailResponse->setEmails([$merchantEmailProto1, $merchantEmailProto2, $merchantEmailProto3]);

        // Test Case 1: ExclusionFlow false - Splitz should never be called - Request should go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdT");
        self::assertEquals($merchantEmailEntity1->toArray(), $gotMerchantEmail->toArray());

        // Test Case 2: ExclusionFlow true - Splitz should never be called - Request should not go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(true);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdT");
        self::assertEquals($merchantEmailEntity1->toArray(), $gotMerchantEmail->toArray());


        // Test Case 3:  ExclusionFlow false - Splitz should never be called - Request should go to account service - Merchant Email is Not Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdK", $merchantEmail->getDefaultRequestMetaData())->willReturn([new MerchantEmailResponseByMerchantId(), null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdK");
        self::assertEquals(null, $gotMerchantEmail);

        // Test Case 4: ExclusionFlow false - Splitz should never be called - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->never())->method('evaluateRequest');
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(1))->method("getByMerchantId")->with("CzmiBzNQPErfdT", $merchantEmail->getDefaultRequestMetaData())->willReturn([null, new GrpcError(\Grpc\STATUS_ABORTED, "new")]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdT");
        self::assertEquals($merchantEmailEntity1->toArray(), $gotMerchantEmail->toArray());
    }

    public function testEmailRepositoryFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_email_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson1);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson2);
        $this->createMerchantEmailInDatabase($this->merchantEmailEntityJson3);

        $merchantEmailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);
        $merchantEmailEntity2 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson2);

        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);

        // Test Case 1 - ExclusionFlow false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals(["id" => $merchantEmailEntity1->getId()], $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R", ["id"]));

        // Test Case 2 - ExclusionFlow false - Select by multiple Ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([$merchantEmailEntity1->toArray(), $merchantEmailEntity2->toArray()]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"]));

        // Test Case 3 - ExclusionFlow false - Select by multiple Ids, filter by fields - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([["id" => "CzmiCwTPCL3t2R"], ["id" => "CzmiD0rBAGOort"]]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"], ["id"]));

        $merchantEmailResponse = (new MerchantEmailResponse())->setEmail($merchantEmailProto1);

        // Test Case 4 - ExclusionFlow false - Splitz should never be called - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 0);
        $this->setMerchantEmailMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $merchantEmailResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 5 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 6 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 7 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

        // Test Case 8 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Not Found"))
        );

    }

    public function testEmailSaveOrFailAsv() {


        /*
         *  Base Setup for the test
         *
         */

        $repo = new Repository();
        $merchantEmail = new MerchantEmail();

        $emailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);
        $emailEntity2 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson2);
        $emailEntity3 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson3);

        $merchantEmailProto1 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson1);
        $merchantEmailProto1->setCreatedAt(0);
        $merchantEmailProto1->setUpdatedAt(0);
        $merchantEmailProto2 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson2);
        $merchantEmailProto2->setCreatedAt(0);
        $merchantEmailProto2->setUpdatedAt(0);
        $merchantEmailProto3 = $this->getMerchantEmailProtoFromJson($this->merchantEmailEntityJson3);
        $merchantEmailProto3->setCreatedAt(0);
        $merchantEmailProto3->setUpdatedAt(0);

        $saveResponse = (new SaveResponse())->setMerchantEmails([new EntitySaveResponse(
                [
                    "id" => "K9UzmvitzJwyS4",
                    "created_at" => 10,
                    "updated_at" => 10,
                    "audit_id" => null
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
        $repo->saveOrFail($emailEntity1);

        /*
        * Test  2: The save or fail ASV should not be reached if Splitz is off.
        */

        // false, false
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["false", "false"], 1);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($emailEntity2);

        // true, false
        $this->setSplitzWithOutputForBulk(["true", "false"], 1);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($emailEntity2);

        // false, true
        $this->setSplitzWithOutputForBulk(["false", "true"], 1);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($emailEntity2);

        /*
        * Test  3: The save or fail ASV should not be reached if Splitz throws exception.
        */
        $this->setSplitzWithOutputForBulk(["false", "true"], 1, true);
        $this->getWriteMockClient()->expects($this->never())->method("save")->willReturn([$saveResponse, null]);
        $repo->saveOrFail($emailEntity2);

        /*
        * Test  4-1: Save Or should work fine if splitz is on, created updated_at should be updated.
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $emailEntity1 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson1);
        $merchantEmailSaveRequest1 = new MerchantEmailSaveRequest();
        $merchantEmailSaveRequest1->setMerchantEmail($merchantEmailProto1);
        $merchantEmailSaveRequest1->setFields(array_keys($emailEntity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantEmailSaveRequests([$merchantEmailSaveRequest1]);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantEmail->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($emailEntity1);
        self::assertEquals(10, $emailEntity1['created_at']);
        self::assertEquals(10, $emailEntity1['updated_at']);

        /*
         * Test  4-2: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $emailEntity2 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson2);
        $merchantEmailSaveRequest2 = new MerchantEmailSaveRequest();
        $merchantEmailSaveRequest2->setMerchantEmail($merchantEmailProto2);
        $merchantEmailSaveRequest2->setFields(array_keys($emailEntity2->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantEmailSaveRequests([$merchantEmailSaveRequest2]);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantEmail->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($emailEntity2);
        self::assertEquals(10, $emailEntity2['created_at']);
        self::assertEquals(10, $emailEntity2['updated_at']);

        /*
         * Test  4-3: Save Or should work fine if splitz is on, created updated_at should be updated.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $emailEntity3 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson3);
        $merchantEmailSaveRequest3 = new MerchantEmailSaveRequest();
        $merchantEmailSaveRequest3->setMerchantEmail($merchantEmailProto3);
        $merchantEmailSaveRequest3->setFields(array_keys($emailEntity3->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantEmailSaveRequests([$merchantEmailSaveRequest3]);
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchantEmail->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($emailEntity3);
        self::assertEquals(10, $emailEntity3['created_at']);
        self::assertEquals(10, $emailEntity3['updated_at']);

        /*
        * Test 5: Save or fail should fail, if Splitz is on, asv throw exception.
        */
        $emailEntity3 = $this->getMerchantEmailEntityFromJson($this->merchantEmailEntityJson3);
        $emailEntity3['created_at'] = 100000;
        $emailEntity3['updated_at'] = 100000;
        $merchantEmailSaveRequest3 = new MerchantEmailSaveRequest();
        $merchantEmailSaveRequest3->setMerchantEmail($merchantEmailProto3);
        $merchantEmailSaveRequest3->setFields(array_keys($emailEntity3->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantEmailSaveRequests([$merchantEmailSaveRequest3]);

        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["true", "true"],1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->
        with($saveRequest)->
        willThrowException(new \RZP\Exception\BaseException("I am ASV Exception.", "ASV_SERVER_ERROR"));
        $merchantEmail->getAsvSdkClient()->setWriteService($writeService);
        try {
            $repo->saveOrFail($emailEntity3);
            self::fail("Exception was expected.");
        } catch (\Exception $e) {
            self::assertEquals(\Illuminate\Database\QueryException::class, get_class($e));
            self::assertEquals("ASV_SERVER_ERROR", $e->getCode());
            self::assertEquals("I am ASV Exception. (SQL: )", $e->getMessage());
            self::assertEquals([], $e->getBindings());
            self::assertEquals("", $e->getSql());

            //created_at, updated_at not changed
            self::assertEquals(100000, $emailEntity3['created_at']);
            self::assertEquals(100000, $emailEntity3['updated_at']);
        }
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
            $this->setMerchantEmailMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
            $repo->findOrFailAsv($id);
        } catch (\Exception $e) {
            return $e;
        }
    }

    private function getExceptionForFindOrFailPublicAsv($repo, $id, $grpcError)
    {
        try {
            $this->setMerchantEmailMockClientWithIdAndResponse($id, null, $grpcError, "getById", 1);
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

    private function setMerchantEmailMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchantEmail = new MerchantEmail();
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly($count))->method($method)->with($id, $merchantEmail->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

    }

    private function convertEntitiesToAssociativeArrayBasedOnId(array $arrays): array
    {
        $result = [];
        foreach ($arrays as $array) {
            $result[$array['id']] = $array;
        }

        return $result;
    }

    private function createMerchantEmailInDatabase($json)
    {
        $this->fixtures->create("merchant_email",
            $this->getMerchantEmailEntityFromJson($json)->toArray(),
        );
    }


    private function getMerchantEmailProtoFromJson(string $json): Email
    {
        $merchantEmailProto = new Email();
        $merchantEmailProto->mergeFromJsonString($json, false);
        return $merchantEmailProto;
    }

    private function getMerchantEmailEntityFromJson(string $json): MerchantEmailEntity
    {
        $merchantEmailArray = json_decode($json, true);
        $merchantEmailEntity = new MerchantEmailEntity();
        $merchantEmailEntity->setRawAttributes($merchantEmailArray);
        return $merchantEmailEntity;
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
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\EmailInterface")
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

    protected function getWriteMockClient() {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\WriteInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

    public function setSplitzWithOutputForBulk(array $output, $count = 1, $exception = false) {
        $response = [];
        for ($i = 0; $i < count($output); $i++) {
            $tempResponse = $this->splitzResponse;
            $tempResponse["variant"]["variables"][0]["value"] = $output[$i];
            $response[$i] = $tempResponse;
        }

        $splitzMock = $this->createSplitzMock(['bulkCallsToSplitz']);

        if ($exception) {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->willThrowException(new \Exception("sample"));
        } else {
            $splitzMock->expects($this->exactly($count))->method('bulkCallsToSplitz')->willReturn($response);
        }

        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        return;
    }
}
