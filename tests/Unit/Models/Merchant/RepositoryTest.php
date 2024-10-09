<?php

namespace Unit\Models\Merchant;

use Config;
use RZP\Exception\DbQueryException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\Account;
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
use function PHPUnit\Framework\assertNotEquals;
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
    public function testMerchantRepositoryFindOrFail()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_read_by_id', 'K1ZaAHZ7Lnumc6');
        $this->createMerchantInDatabase($this->merchantEntityJson1);

        $merchantEntity1      = $this->getMerchantEntityFromJson($this->merchantEntityJson1);
        $merchantEntity1Array = $merchantEntity1->toArray();
        $merchantProto1       = $this->getMerchantProtoFromJson($this->merchantEntityJson1);

        // Test Case 1 - ExcludedRoute true - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, true, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 2 - SaveRoute false - Splitz off - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->setSplitzWithOutput("false", 3);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for findOrFail & findOrFailPublic  should not go to account service
        $this->splitzShouldThrowException(3);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 4 - SaveRoute false - Column Selection - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 3);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
        $merchantEntityForFindOrFail       = $repo->findOrFail("CzmiCwTPCL3t2K", ["id"]);
        $merchantEntityForFindOrFailPublic = $repo->findOrFailPublic("CzmiCwTPCL3t2K", ["id"]);
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFail->toArray());
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFailPublic->toArray());

        // Test Case 5 - SaveRoute false - array of ids - Request for findOrFail & findOrFailPublic should not go to account service
        $this->setSplitzWithOutput("false", 3);
        $repo                                               = new Repository();
        $repo->asvRouter                                    = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
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
        $this->setSplitzWithOutput("true", 3);
        $this->flushCache();
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantResponse, null, "getById", 2);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // now value is cached so get by id should be called only for findOrFailPublic not for findOrFail
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantResponse, null, "getById", 1);
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindOrFailAndFindOrFailPublicAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");


        //assert that error is thrown if we pass random id
        $this->setSplitzWithOutput("true", 4);
        $this->flushCache();
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 4, false, null);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t21", null, new GrpcError(STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getById", 2);
        try {
            $repo->findOrFail("CzmiCwTPCL3t21");
        } catch (DbQueryException $e) {
            $this->assertEquals($e->getCode(), "SERVER_ERROR_DB_QUERY_FAILED");
        }
    }

    public function testMerchantRepositoryFindOrFailErrors()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_read_by_id', 'K1ZaAHZ7Lnumc6');
        $repo = new Repository();

        // Test Case 1 -  Match not found Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 2 -  Match not found Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJwyS6"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJwyS6", new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"))
        );

        // Test Case 3 - Match Invalid Argument Exception from DB and ASV: FindOrFail
        $this->assertEquals(
            $this->getExceptionForFindAndFailDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );

        // Test Case 4 - Match Invalid Argument Exception from DB and ASV: FindOrFailPublic
        $this->assertEquals(
            $this->getExceptionForFindAndFailPublicDatabase($repo, "K9UzmvitzJ"),
            $this->getExceptionForFindOrFailPublicAsv($repo, "getById", "K9UzmvitzJ", new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"))
        );

    }

    public function testMerchantRepositoryFind()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_find', 'K1ZaAHZ7Lnumc6');
        $this->createMerchantInDatabase($this->merchantEntityJson1);

        $merchantEntity1      = $this->getMerchantEntityFromJson($this->merchantEntityJson1);
        $merchantEntity1Array = $merchantEntity1->toArray();
        $merchantProto1       = $this->getMerchantProtoFromJson($this->merchantEntityJson1);

        // Test Case 1 - ExcludedRoute true - Request for find  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $this->callFindAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 2 - SaveRoute false - Splitz off - Request for find  should not go to account service
        $this->setSplitzWithOutput("false", 1);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $this->callFindAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 3 - SaveRoute false - Splitz Exception - Request for find  should not go to account service
        $this->splitzShouldThrowException(1);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $this->callFindAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");

        // Test Case 4 - SaveRoute false - Column Selection - Request for find should not go to account service
        $this->setSplitzWithOutput("false", 3);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 3, false, null);
        $merchantEntityForFind       = $repo->find("CzmiCwTPCL3t2K", ["id"]);
        $merchantEntityForFindOrFail = $repo->findOrFail("CzmiCwTPCL3t2K", ["id"]);
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFind->toArray());
        $this->assertEquals(["id" => $merchantEntity1Array['id']], $merchantEntityForFindOrFail->toArray());

        $merchantResponse = (new MerchantResponse())->setMerchant($merchantProto1);

        // Test Case 6 - SaveRoute false - Splitz on - Request for find  should go to account service
        $this->setSplitzWithOutput("true", 2);
        $this->setEntityMockClientWithIdAndResponse("CzmiCwTPCL3t2K", $merchantResponse, null, "getById", 1);
        $repo            = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 2, false, null);
        $this->callFindAndCompare($repo, $merchantEntity1Array, "CzmiCwTPCL3t2K");
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

    public function verifyMerchantLoadOperation($id, $relations)
    {
        $apiMerchant       = $this->fixtures->create('merchant', ['id' => $id]);
        $apiMerchantDetail = $this->fixtures->create('merchant_detail:associate_merchant', ['merchant_id' => $id]);

        $merchantRepository    = new Repository();
        $fetchedMerchant       = $merchantRepository->findOrFail($id);
        $fetchedMerchantDetail = $fetchedMerchant->merchantDetail;

        $this->fixtures->edit('merchant_detail', $id, [
            'contact_mobile' => '+919991119991',
        ]);
        $fetchedMerchant->load($relations);
        $this->assertNotEquals($fetchedMerchantDetail->getContactMobile(), $fetchedMerchant->merchantDetail->getContactMobile());
        $this->assertEquals('+919991119991', $fetchedMerchant->merchantDetail->getContactMobile());
    }

    public function testMerchantLoadOperation()
    {
        $id = PublicEntity::generateUniqueId();
        $this->verifyMerchantLoadOperation($id, 'merchantDetail');

        $id = PublicEntity::generateUniqueId();
        $this->verifyMerchantLoadOperation($id, ['merchantDetail', 'pricing']);

        $id = PublicEntity::generateUniqueId();
        Config::set('applications.asv_v2.splitz_send_reload_to_asv', $id);
        $this->setSplitzWithOutput("true", 5);
        $this->verifyMerchantLoadOperation($id, ['merchantDetail','pricing']);

        $id = PublicEntity::generateUniqueId();
        Config::set('applications.asv_v2.splitz_send_reload_to_asv', $id);
        $this->setSplitzWithOutput("true", 5);
        $this->verifyMerchantLoadOperation($id, 'merchantDetail');
    }


    public function verifyFindOrFailPublicWithRelations($id, $relations): void
    {
        $apiMerchant       = $this->fixtures->create('merchant', ['id' => $id]);
        $apiMerchantDetail = $this->fixtures->create('merchant_detail:associate_merchant', ['merchant_id' => $id]);

        $merchantRepository    = new Repository();
        $fetchedMerchant       = $merchantRepository->findOrFailPublicWithRelations($id, $relations);
        $fetchedMerchantDetail = $fetchedMerchant->merchantDetail;
        $this->fixtures->edit('merchant_detail', $id, [
            'contact_mobile' => '+919991119991',
        ]);
        $this->flushCache();
        $reFetchedMerchant = $merchantRepository->findOrFailPublicWithRelations($id, $relations);
        $this->assertNotEquals($fetchedMerchantDetail->getContactMobile(), $reFetchedMerchant->merchantDetail->getContactMobile());
        $this->assertEquals('+919991119991', $reFetchedMerchant->merchantDetail->getContactMobile());
    }

    public function testFindOrFailPublicWithRelations()
    {
        // verify that flow old flow is working fine
        $id = PublicEntity::generateUniqueId();
        $this->verifyFindOrFailPublicWithRelations($id, ['merchantDetail', 'pricing']);

        // verify that new flow is working fine
        $id = PublicEntity::generateUniqueId();
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', $id);
        Config::set('applications.asv_v2.splitz_send_reload_to_asv', $id);
        $this->setSplitzWithOutput("true", 8);
        $this->verifyFindOrFailPublicWithRelations($id, ['merchantDetail','pricing']);
    }

    public function verifyMerchantRefreshOperation($id)
    {
        $apiMerchant       = $this->fixtures->create('merchant', ['id' => $id]);
        $apiMerchantDetail = $this->fixtures->create('merchant_detail:associate_merchant', ['merchant_id' => $id]);

        $merchantRepository    = new Repository();
        $fetchedMerchant       = $merchantRepository->findOrFail($id);
        $fetchedMerchantDetail = $fetchedMerchant->merchantDetail;

        $this->fixtures->edit('merchant_detail', $id, [
            'contact_mobile' => '+919991119991',
        ]);

        $this->fixtures->edit('merchant', $id, [
            'live' => true,
        ]);
        $this->assertEquals(false, $fetchedMerchant->isLive());
        $fetchedMerchant->refresh();
        $this->assertEquals(true, $fetchedMerchant->isLive());
        $this->assertNotEquals($fetchedMerchantDetail->getContactMobile(), $fetchedMerchant->merchantDetail->getContactMobile());
        $this->assertEquals('+919991119991', $fetchedMerchant->merchantDetail->getContactMobile());
    }

    public function testMerchantRefreshOperation()
    {
        $id = PublicEntity::generateUniqueId();
        $this->verifyMerchantRefreshOperation($id);

        $id = PublicEntity::generateUniqueId();
        Config::set('applications.asv_v2.splitz_send_reload_to_asv', $id);
        $this->setSplitzWithOutput("true", 6);
        $this->verifyMerchantRefreshOperation($id);
    }

    public function testAccountFindByIdAndMerchantOperation()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());
        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $parentId = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $parentId]);
        $this->fixtures->create('merchant', ['id' => $id1, 'parent_id' => $parentId]);
        $this->fixtures->create('merchant', ['id' => $id2, 'parent_id' => $parentId]);
        $repository = new Account\Repository();

        $parentMerchant       = $repository->find($parentId);
        $merchant1            = $repository->find($id1);
        $merchant2            = $repository->find($id2);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Account\Repository();
        $resultWithoutSplitz1 = $repository->findByIdAndMerchant($id1, $parentMerchant);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Account\Repository();
        $resultWithoutSplitz2 = $repository->findByIdAndMerchant($id2, $parentMerchant);
        $this->assertEquals($merchant1, $resultWithoutSplitz1, "created merchant and merchant without splitz are not same");
        $this->assertEquals($merchant2, $resultWithoutSplitz2, "created merchant and merchant without splitz are not same");

        // reset connection because when we query from asv laravel attaches db connection with entity ,
        // so we manually reset the connection with entity
        $repository->resetConnectionOnModels($resultWithoutSplitz1);
        $repository->resetConnectionOnModels($resultWithoutSplitz2);

        $this->setSplitzWithOutput("true", 1);
        $repository = new Account\Repository();
        $resultWithSplitz1 = $repository->findByIdAndMerchant($id1, $parentMerchant);
        $this->assertEquals($resultWithoutSplitz1, $resultWithSplitz1, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz1), get_class($resultWithSplitz1));

        $this->setSplitzWithOutput("true", 1);
        $repository = new Account\Repository();
        $resultWithSplitz2 = $repository->findByIdAndMerchant($id2, $parentMerchant);
        $this->assertEquals($resultWithoutSplitz2, $resultWithSplitz2, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz2), get_class($resultWithSplitz2));
    }

    public function testAccountFindByPublicIdAndMerchantOperation()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());
        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $parentId = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $parentId]);
        $this->fixtures->create('merchant', ['id' => $id1, 'parent_id' => $parentId]);
        $this->fixtures->create('merchant', ['id' => $id2, 'parent_id' => $parentId]);
        $repository = new Account\Repository();

        $parentMerchant       = $repository->find($parentId);
        $merchant1            = $repository->find($id1);
        $merchant2            = $repository->find($id2);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Account\Repository();
        $resultWithoutSplitz1 = $repository->findByPublicIdAndMerchant("acc_".$id1, $parentMerchant);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Account\Repository();
        $resultWithoutSplitz2 = $repository->findByPublicIdAndMerchant("acc_".$id2, $parentMerchant);
        $this->assertEquals($merchant1, $resultWithoutSplitz1, "created merchant and merchant without splitz are not same");
        $this->assertEquals($merchant2, $resultWithoutSplitz2, "created merchant and merchant without splitz are not same");

        // reset connection because when we query from asv laravel attaches db connection with entity ,
        // so we manually reset the connection with entity
        $repository->resetConnectionOnModels($resultWithoutSplitz1);
        $repository->resetConnectionOnModels($resultWithoutSplitz2);


        $this->setSplitzWithOutput("true", 1);
        $repository = new Account\Repository();
        $resultWithSplitz1 = $repository->findByPublicIdAndMerchant("acc_".$id1, $parentMerchant);
        $this->assertEquals($resultWithoutSplitz1, $resultWithSplitz1, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz1), get_class($resultWithSplitz1));

        $this->setSplitzWithOutput("true", 1);
        $repository = new Account\Repository();
        $resultWithSplitz2 = $repository->findByPublicIdAndMerchant("acc_".$id2, $parentMerchant);
        $this->assertEquals($resultWithoutSplitz2, $resultWithSplitz2, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz2), get_class($resultWithSplitz2));
    }

    public function testFindManyOperation()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());
        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $id3 = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $id3]);
        $this->fixtures->create('merchant', ['id' => $id1]);
        $this->fixtures->create('merchant', ['id' => $id2]);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Repository();
        $resultWithoutSplitz1 = $repository->findMany(array($id1, $id2, $id3));

        $repository->resetConnectionOnModels($resultWithoutSplitz1);

        $this->setSplitzWithOutput("true", 2);
        $repository = new Repository();
        $resultWithSplitz1 = $repository->findMany(array($id1, $id2, $id3));
        $this->assertEquals($resultWithoutSplitz1, $resultWithSplitz1, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz1), get_class($resultWithSplitz1));

        $this->setSplitzWithOutput("true", 2);
        $repository = new Repository();
        $repository->repo->transactionOnLiveAndTestAndAsv(function () use ($id3, $resultWithoutSplitz1, $id2, $id1, $repository) {
            $resultWithSplitz1 = $repository->findMany(array($id1, $id2, $id3));
            $this->assertEquals($resultWithoutSplitz1, $resultWithSplitz1, "response with and without splitz are not same");
            $this->assertEquals(get_class($resultWithoutSplitz1), get_class($resultWithSplitz1));
        });

        $this->setSplitzWithOutput("true", 1);
        $repository = new Repository();
        $results = $repository->findMany(null);
        $this->assertEquals(new PublicCollection(), $results);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Repository();
        $results = $repository->findMany(null);
        $this->assertEquals(new PublicCollection(), $results);
    }

    public function testFindManyOnReadReplicaOperation()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());
        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $id3 = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $id3]);
        $this->fixtures->create('merchant', ['id' => $id1]);
        $this->fixtures->create('merchant', ['id' => $id2]);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Repository();
        $resultWithoutSplitz1 = $repository->FindManyOnReadReplica(array($id1, $id2, $id3));

        $this->setSplitzWithOutput("true", 1);
        $repository = new Repository();
        $resultWithSplitz1 = $repository->FindManyOnReadReplica(array($id1, $id2, $id3));
        $this->assertEquals($resultWithoutSplitz1, $resultWithSplitz1, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitz1), get_class($resultWithSplitz1));
    }

    public function testMerchantsUpdatedAt()
    {
        $repo = new Repository();
        $id = 'CzmiCwTPCL3t2K';

        $repo->repo->transactionOnLiveAndTest(function() use ($repo){
            $repo->saveOrFail($this->getMerchantEntityFromJson($this->merchantEntityJson1));
        });

        $merchantEntity1 = $repo->findOrFail($id);
        assertNotEquals(1234, $merchantEntity1->getUpdatedAt());

        $merchantEntity1->setUpdatedAt(1234);
        $repo->repo->transactionOnLiveAndTest(function() use ($merchantEntity1, $repo){
            $repo->saveOrFail($merchantEntity1);
        });

        $merchantEntity2 = $repo->findOrFail($id);

        assertNotEquals(1234, $merchantEntity2->getUpdatedAt());
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

    protected function callFindAndCompare($repo, $expectedMerchantArray, $merchantId)
    {
        $merchantEntityForFind                        = $repo->find($merchantId);
        $merchantEntityForFindArray                   = $this->removeNonExistingKeysFromEntityFetchedFromDB($expectedMerchantArray, $merchantEntityForFind->toArray());
        $merchantEntityForFindArray['audit_id']       = $expectedMerchantArray['audit_id'];
        $this->assertEquals($expectedMerchantArray, $merchantEntityForFindArray);
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
