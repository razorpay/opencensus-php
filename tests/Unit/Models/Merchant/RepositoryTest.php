<?php

namespace Unit\Models\Merchant;

use Config;
use Google\Protobuf\Int32Value;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use Rzp\Accounts\Merchant\V1\MerchantDocumentSaveRequest;
use Rzp\Accounts\Merchant\V1\MerchantSaveRequest;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument;
use RZP\Tests\Functional;
use Razorpay\Asv\Error\GrpcError;
use RZP\Models\Merchant\Repository;
use Rzp\Accounts\Merchant\V1\MerchantResponse;
use RZP\Models\Merchant\Entity as MerchantEntity;
use Rzp\Accounts\Merchant\V1\Merchant as MerchantProto;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Merchant;
use RZP\Models\Merchant\Detail\Entity as MerchantDetailEntity;
use RZP\Models\Adjustment\Entity as AdjustmentEntity;
use RZP\Models\Transaction\Entity as TransactionEntity;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;
use const Grpc\STATUS_DEADLINE_EXCEEDED;

class RepositoryTest extends RepositoryTestHelper
{
    use Functional\AsvFindOrFailAndFindOrFailPublicTrait;

    private $merchantEntityJson1 = '{
        "id": "CzmiCwTPCL3t2K",
        "org_id": "100000razorpay",
        "name": "consequatur",
        "email": "test@razorpay.com",
        "account_code": null,
        "parent_id": null,
        "legal_entity_id": null,
        "activated": 0,
        "activated_at": 1687262076,
        "archived_at": null,
        "suspended_at": null,
        "live": 0,
        "live_disable_reason": null,
        "hold_funds": 0,
        "hold_funds_reason": null,
        "pricing_plan_id": null,
        "website": "http://mertz.com/est-nam-quos-iste-aliquid-vel-et-est-mollitia",
        "category": "5399",
        "international": 1,
        "product_international": "1111000000",
        "billing_label": "Test Merchant",
        "display_name": null,
        "channel": "axis",
        "transaction_report_email": "test@razorpay.com",
        "fee_bearer": 0,
        "fee_model": 0,
        "fee_credits_threshold": null,
        "amount_credits_threshold": null,
        "refund_credits_threshold": null,
        "refund_source": 0,
        "linked_account_kyc": 0,
        "has_key_access": 0,
        "partner_type": null,
        "brand_color": null,
        "handle": null,
        "activation_source": null,
        "signup_source": null,
        "business_banking": 0,
        "logo_url": null,
        "icon_url": null,
        "invoice_label_field": null,
        "risk_rating": 3,
        "risk_threshold": null,
        "receipt_email_enabled": 1,
        "receipt_email_trigger_event": 1,
        "max_payment_amount": null,
        "max_international_payment_amount": null,
        "auto_refund_delay": null,
        "default_refund_speed": "normal",
        "auto_capture_late_auth": 0,
        "convert_currency": null,
        "category2": null,
        "invoice_code": "123456789011",
        "notes": null,
        "whitelisted_ips_live": null,
        "whitelisted_ips_test": null,
        "whitelisted_domains": null,
        "second_factor_auth": 0,
        "restricted": 0,
        "dashboard_whitelisted_ips_live": null,
        "dashboard_whitelisted_ips_test": null,
        "partnership_url": null,
        "external_id": null,
        "purpose_code": null,
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "signup_via_email": 1,
        "balance_threshold": null,
        "audit_id": "M4Au4oJAxUkdNV",
        "country_code": "IN"
     }';

    private $merchantDetailEntityJson1 = '{
        "merchant_id": "CzmiCwTPCL3t2K",
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

    private $adjustmentEntityJson1 = '{
        "id": "7wmZhMR5L6cAyu",
        "merchant_id": "CzmiCwTPCL3t2K",
        "amount": 100,
        "currency": "INR",
        "channel": "kotak",
        "description": "test",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "transaction_id": "7wmZhNaE8abtYn"
    }';

    /**
     * @throws \Exception
     */
    public function testMerchantRepositoryFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantInDatabase($this->merchantEntityJson1);

        $merchantEntity1      = $this->getMerchantEntityFromJson($this->merchantEntityJson1);
        $merchantEntity1Array = $merchantEntity1->toArray();
        $merchantProto1       = $this->getMerchantProtoFromJson($this->merchantEntityJson1);

        // Test Case 1 - ExcludedRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, true, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 2);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(2);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");


        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantEntityForFindOrFail       = $repo->findOrFail("CzmiCwTPCL3t2K", ["id"]);
        $merchantEntityForFindOrFailPublic = $repo->findOrFailPublic("CzmiCwTPCL3t2K", ["id"]);
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFail->toArray());
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFailPublic->toArray());

        // Test Case 5 - SaveRoute false - array of ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo                                               = new Repository();
        $repo->asvRouter                                    = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 0, false, null);
        $merchantEntityForFindOrFail                        = $repo->findOrFail(["CzmiCwTPCL3t2K"]);
        $merchantEntityForFindOrFailPublic                  = $repo->findOrFailPublic(["CzmiCwTPCL3t2K"]);
        $merchantEntityForFindOrFailArray                   = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantEntity1Array, $merchantEntityForFindOrFail->first()->toArray());
        $merchantEntityForFindOrFailPublicArray             = $this->removeNonExistingKeysFromEntityFetchedFromDB($merchantEntity1Array, $merchantEntityForFindOrFailPublic->first()->toArray());
        $merchantEntityForFindOrFailArray['audit_id']       = $merchantEntity1Array['audit_id'];
        $merchantEntityForFindOrFailPublicArray['audit_id'] = $merchantEntity1Array['audit_id'];
        $this->assertEquals($merchantEntity1Array, $merchantEntityForFindOrFailArray);
        $this->assertEquals($merchantEntity1Array, $merchantEntityForFindOrFailPublicArray);

        $merchantResponse = (new MerchantResponse())->setMerchant($merchantProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantResponse, null, "getById", 2);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 7 - SaveRoute false - Splitz on - Request for findOrFail & findOrFailPublic  should go to account service - Exception occurs fallback to DB
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", null, new GrpcError(STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getById", 2);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");


        $repo = new Repository();

        // Test Case 8 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 9 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 10 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );

        // Test Case 11 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );
    }

    public function testMerchantAssociation()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_find_for_implicit_join', 'K1ZaAHZ7Lnumc6');
        Config::set('applications.asv_v2.splitz_experiment_implicit_join_entity', 'K1ZaAHZ7Lnumc6');

        $entitiesData = [
            [
                "relationName" => "merchant",
                "asvEntity" => new Merchant(),
                "asvResponseEntity" => new MerchantResponse(),
                "setterFunctionName" => 'setMerchant',
                "responseSetterFunctionName" => 'setMerchant',
                "entityRepo" => new Repository(),
                "entityRepoName" => 'merchant',
                "asvMockMethod" => "getById",
                "entityName" => "merchant",
                "entityData" => $this->merchantEntityJson1,
                "entityClass" => new MerchantEntity(),
                "entityProtoClass" => new MerchantProto(),
                "AssociatedEntityRepo" => new \RZP\Models\Merchant\Detail\Repository(),
                "AssociatedEntityName" => "merchant_detail",
                "AssociatedEntityData" => $this->merchantDetailEntityJson1,
                "AssociatedEntityClass" => new MerchantDetailEntity(),
                "mockBuilderInterface" => "Razorpay\Asv\Interfaces\MerchantInterface",
                "merchant_id" => "CzmiCwTPCL3t2K",
                "shouldEntityNeedsToBeCreated" => false,
                "isDependentEntity" => true,
                "dependentEntity" => [
                    [
                        "dependentEntityName" => "merchant",
                        "dependentEntityData" => $this->merchantEntityJson1,
                        "dependentEntityClass" => new MerchantEntity(),

                    ]
                ]
            ],
            [
                "relationName" => "merchant",
                "asvEntity" => new Merchant(),
                "asvResponseEntity" => new MerchantResponse(),
                "setterFunctionName" => 'setMerchant',
                "responseSetterFunctionName" => 'setMerchant',
                "entityRepo" => new Repository(),
                "entityRepoName" => 'merchant',
                "asvMockMethod" => "getById",
                "entityName" => "merchant",
                "entityData" => $this->merchantEntityJson1,
                "entityClass" => new MerchantEntity(),
                "entityProtoClass" => new MerchantProto(),
                "AssociatedEntityRepo" => new \RZP\Models\Adjustment\Repository(),
                "AssociatedEntityName" => "adjustment",
                "AssociatedEntityData" => $this->adjustmentEntityJson1,
                "AssociatedEntityClass" => new AdjustmentEntity(),
                "mockBuilderInterface" => "Razorpay\Asv\Interfaces\MerchantInterface",
                "merchant_id" => "CzmiCwTPCL3t2K",
                "id" => "7wmZhMR5L6cAyu",
                "shouldEntityNeedsToBeCreated" => false,
                "isDependentEntity" => true,
                "dependentEntity" => [
                    [
                        "dependentEntityName" => "transaction",
                        "dependentEntityData" => '{"id" : "7wmZhNaE8abtYn", "merchant_id": "CzmiCwTPCL3t2K"}',
                        "dependentEntityClass" => new TransactionEntity(),

                    ]
                ]
            ],
        ];
        $this->runTestsForImplicitJoin($entitiesData);
    }

    public function testMerchantSaveOrFailAsv()
    {


        /*
         *  Base Setup for the test
         *
         */

        $repo     = new \RZP\Models\Merchant\Repository();
        $merchant = new Merchant();

        $entity1 = $this->getMerchantEntityFromJson($this->merchantEntityJson1);

        $proto1 = $this->getMerchantProtoFromJson($this->merchantEntityJson1);
        $proto1->setCreatedAt(0);
        $proto1->setUpdatedAt(0);
        $proto1->setFreePayoutsConsumed((new Int32Value())->setValue(0));
        $proto1->setSettlementSchedule((new Int32Value())->setValue(3));


        $saveResponse = (new SaveResponse())->setMerchant(new EntitySaveResponse(
                [
                    "id" => "JWNkBHL4Waqqf8",
                    "created_at" => 10,
                    "updated_at" => 10,
                    "audit_id" => "newtesttesttestid",
                ]
            )
        );

        /*
         * Test 1: The save or fail ASV should not be reached if write is not enabled.
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = false;
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        /*
        * Test  2: The save or fail ASV should not be reached if Splitz is off.
        */

        // false, false
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["false", "false"], 1);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        // true, false
        $this->setSplitzWithOutputForBulk(["true", "false"], 1);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);

        // false, true
        $this->setSplitzWithOutputForBulk(["false", "true"], 1);
        $repo->saveOrFail($entity1);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        /*
        * Test  3: The save or fail ASV should not be reached if Splitz throws exception.
        */
        $this->setSplitzWithOutputForBulk(["false", "true"], 1, true);
        $this->getWriteMockClient()->expects($this->never())->method("save");
        $repo->saveOrFail($entity1);
//
        /*
        * Test  4-1: Save Or should work fine if splitz is on, created updated_at should be updated.
         *  Case for create
        */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $merchantSaveRequest3 = new MerchantSaveRequest();
        $entity1 = $this->getMerchantEntityFromJson($this->merchantEntityJson1);
        $merchantSaveRequest3->setMerchant($proto1);
        $merchantSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantSaveRequest($merchantSaveRequest3);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchant->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);
        self::assertEquals("newtesttesttestid", $entity1['audit_id']);

        /*
         * Test  4-2: Save Or should work fine if splitz is on, created updated_at should be updated. This is case of update
         */
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $entity1['audit_id']                        = $proto1->getAuditIdUnwrapped();
        $merchantSaveRequest3 = new MerchantSaveRequest();
        $merchantSaveRequest3->setMerchant($proto1);
        $merchantSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantSaveRequest($merchantSaveRequest3);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchant->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);
        self::assertEquals("newtesttesttestid", $entity1['audit_id']);


        /*
         * Test  4-3: Save operation should not happen if no dirty field present
         */
        $entity1->setRawAttributes($entity1->getAttributes(), true);
        $merchantSaveRequest3 = new MerchantSaveRequest();
        $merchantSaveRequest3->setMerchant($proto1);
        $merchantSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantSaveRequest($merchantSaveRequest3);
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->never())->method("save")->with($saveRequest)->willReturn([$saveResponse, null]);
        $merchant->getAsvSdkClient()->setWriteService($writeService);
        $repo->saveOrFail($entity1);
        self::assertEquals(10, $entity1['created_at']);
        self::assertEquals(10, $entity1['updated_at']);
        self::assertEquals("newtesttesttestid", $entity1['audit_id']);


        /*
        * Test 5: Save or fail should fail, if Splitz is on, asv throw exception.
        */
        $existingAuditId  = $proto1->getAuditIdUnwrapped();
        $entity1['audit_id']  = $existingAuditId;
        $merchantSaveRequest3 = new MerchantSaveRequest();
        $merchantSaveRequest3->setMerchant($proto1);
        $merchantSaveRequest3->setFields(array_keys($entity1->getDirty()));
        $saveRequest = (new SaveRequest())->setMerchantSaveRequest($merchantSaveRequest3);
        WriteEnabledOnAsv::$SAVE_OR_FAIL[Repository::class] = true;
        $this->setSplitzWithOutputForBulk(["true", "true"], 1);
        $writeService = $this->getWriteMockClient();
        $writeService->expects($this->once())->method("save")->with($saveRequest)->
        willThrowException(new \RZP\Exception\BaseException("I am ASV Exception.", "ASV_SERVER_ERROR"));
        $merchant->getAsvSdkClient()->setWriteService($writeService);
        try {
            $repo->saveOrFail($entity1);
            self::fail("Exception was expected.");
        } catch (\Exception $e) {
            self::assertEquals(\Illuminate\Database\QueryException::class, get_class($e));
            self::assertEquals("ASV_SERVER_ERROR", $e->getCode());
            self::assertEquals("I am ASV Exception. (SQL: )", $e->getMessage());
            self::assertEquals([], $e->getBindings());
            self::assertEquals("", $e->getSql());

            //created_at, updated_at not changed
            self::assertEquals(10, $entity1['created_at']);
            self::assertEquals(10, $entity1['updated_at']);
            self::assertEquals($existingAuditId, $entity1['audit_id']);
        }
    }

    public function updateAuditIdAndAssert($entityRepo, $associatedEntity, $entityArray, $relationName, $repoName)
    {
        app('repo')->$repoName    = $entityRepo;
        $entity                   = $associatedEntity->$relationName->toArray();
        $entity['audit_id']       = "M4Au4oJAxUkdNV";
        $entityForFindOrFailArray = $this->removeNonExistingKeysFromEntityFetchedFromDB($entityArray, $entity);
        $this->assertEquals($entityForFindOrFailArray, $entityArray);
    }


    /**
     * @throws \Exception
     */
    protected function callFindOrFailAndFindOrFailPublicAndCompare($repo, $expectedMerchantArray, $merchantId)
    {
        $merchantEntityForFindOrFail                        = $repo->findOrFail($merchantId);
        $merchantEntityForFindOrFailPublic                  = $repo->findOrFailPublic($merchantId);
        $merchantEntityForFindOrFailArray                   = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantArray, $merchantEntityForFindOrFail->toArray());
        $merchantEntityForFindOrFailPublicArray             = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantArray, $merchantEntityForFindOrFailPublic->toArray());
        $merchantEntityForFindOrFailArray['audit_id']       = $expectedMerchantArray['audit_id'];
        $merchantEntityForFindOrFailPublicArray['audit_id'] = $expectedMerchantArray['audit_id'];

        $this->assertEquals($expectedMerchantArray, $merchantEntityForFindOrFailArray);
        $this->assertEquals($expectedMerchantArray, $merchantEntityForFindOrFailPublicArray);
    }


    protected function removeNonExistingKeysFromEntityFetchedFromDB(array $entityArrayExpected, array $entityArrayFromDB): array
    {
        foreach ($entityArrayFromDB as $key => $value) {
            if (array_key_exists($key, $entityArrayExpected) !== true) {
                unset($entityArrayFromDB[$key]);
            }
        }

        return $entityArrayFromDB;
    }

    protected function setEntityMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchant           = new Merchant();
        $merchantMockClient = $this->getMockClient();
        $merchantMockClient->expects($this->exactly($count))->method($method)->with($id, $merchant->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchant->getAsvSdkClient()->setMerchant($merchantMockClient);
    }

    private function createMerchantInDatabase($json)
    {
        $this->fixtures->create("merchant",
            $this->getMerchantEntityFromJson($json)->toArray(),
        );
    }

    private function getMerchantProtoFromJson(string $json): MerchantProto
    {
        $merchantProto = new MerchantProto();
        $merchantProto->mergeFromJsonString($json, false);
        return $merchantProto;
    }

    private function getMerchantEntityFromJson(string $json): MerchantEntity
    {
        $merchantArray  = json_decode($json, true);
        $merchantEntity = new MerchantEntity();
        $merchantEntity->setRawAttributes($merchantArray);
        return $merchantEntity;
    }

    private function getMockClient()
    {
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\MerchantInterface")
            ->enableOriginalConstructor()
            ->getMock();
    }

}
