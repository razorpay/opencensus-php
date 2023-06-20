<?php

namespace Unit\Models\Merchant\Email;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\Email;
use Rzp\Accounts\Merchant\V1\MerchantEmailResponse;
use Rzp\Accounts\Merchant\V1\MerchantEmailResponseByMerchantId;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;
use RZP\Models\Merchant\Email\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

class RepositoryTest extends TestCase
{

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

        // Test Case 1: SaveRoute false - Splitz is on - Request should go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->exactly(1))->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 2: SaveRoute true - Splitz is on - Request should not go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 3: SaveRoute false - Splitz is on - Request should go to account service - Merchant Email is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 4: SaveRoute false - Splitz is off - Request should not go to account service

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 5: SaveRoute false - Splitz is off - Request  should not  go to account service - Merchant Email not Found

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()),
            self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 6: SaveRoute false - Splitz call fails - Request should not go to account service.

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("some error occurred while calling splitz"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $expectedMerchantEmails = (new MerchantEmailEntity())->newCollection([$merchantEmailEntity1, $merchantEmailEntity2]);
        $gotMerchantEmails = $repo->getEmailByMerchantId("CzmiBzNQPErfdT");
        self::assertEquals(self::convertEntitiesToAssociativeArrayBasedOnId($expectedMerchantEmails->toArray()), self::convertEntitiesToAssociativeArrayBasedOnId($gotMerchantEmails->toArray()));

        // Test Case 7: SaveRoute false - Splitz is on - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 1: SaveRoute false - Splitz is on - Request should go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 2: SaveRoute true - Splitz is on - Request should not go to account service - Merchant Email is Found

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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


        // Test Case 3:  SaveRoute true - Splitz is on - Request should go to account service - Merchant Email is Not Found
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 4: SaveRoute false - Splitz is off - Request should not go to account service

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdT");
        self::assertEquals($merchantEmailEntity1->toArray(), $gotMerchantEmail->toArray());

        // Test Case 5: SaveRoute false - Splitz is off - Request  should not  go to account service - Merchant Email not Found

        $splitzOutput = $this->sampleSpltizOutput;
        $splitzOutput["response"]["variant"]["variables"][0]["value"] = "false";
        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($splitzOutput);
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdK");
        self::assertEquals(null, $gotMerchantEmail);

        // Test Case 6: SaveRoute false - Splitz call fails - Request should not go to account service.

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willThrowException(new \Exception("some error occurred while calling splitz"));
        $this->app[Constant::SPLITZ_SERVICE] = $splitzMock;
        $merchantEmailMockClient = $this->getMockClient();
        $merchantEmailMockClient->expects($this->exactly(0))->method("getByMerchantId")->with($this->any())->willReturn([$merchantEmailResponse, null]);
        $merchantEmail->getAsvSdkClient()->setEmail($merchantEmailMockClient);

        $asvRouterMock = $this->getAsvRouteMock(['isExclusionFlowOrFailure']);
        $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);

        $repo = new Repository();
        $repo->asvRouter = $asvRouterMock;
        $gotMerchantEmail = $repo->getEmailByType("support", "CzmiBzNQPErfdT");
        self::assertEquals($merchantEmailEntity1->toArray(), $gotMerchantEmail->toArray());


        // Test Case 7: SaveRoute false - Splitz is on - Request Failed From account service - Should Be Routed to DB

        $splitzMock = $this->createSplitzMock();
        $splitzMock->expects($this->any())->method('evaluateRequest')->willReturn($this->sampleSpltizOutput);
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

        // Test Case 1 - SaveRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals(["id" => $merchantEmailEntity1->getId()], $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R", ["id"]));

        // Test Case 5 - SaveRoute false - Select by multiple Ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([$merchantEmailEntity1->toArray(), $merchantEmailEntity2->toArray()]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"]));

        // Test Case 5 - SaveRoute false - Select by multiple Ids, filter by fields - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $this->assertEquals($this->convertEntitiesToAssociativeArrayBasedOnId([["id" => "CzmiCwTPCL3t2R"], ["id" => "CzmiD0rBAGOort"]]), $this->getOutputForDbCalls($repo, ["CzmiCwTPCL3t2R", "CzmiD0rBAGOort"], ["id"]));

        $merchantEmailResponse = (new MerchantEmailResponse())->setEmail($merchantEmailProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setMerchantEmailMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $merchantEmailResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

        // Test Case 7 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setMerchantEmailMockClientWithIdAndResponse("CzmiCwTPCL3t2R", $merchantEmailResponse, null, "getById", 2);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->assertEquals($merchantEmailEntity1->toArray(), $this->getOutputForDbCalls($repo, "CzmiCwTPCL3t2R"));

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
}
