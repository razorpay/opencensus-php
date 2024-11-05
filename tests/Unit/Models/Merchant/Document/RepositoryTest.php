<?php

namespace Unit\Models\Merchant\Document;

use _PHPStan_f96c91c1c\Nette\Neon\Exception;
use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\DeleteResponse;
use Rzp\Accounts\Merchant\V1\EntitySaveResponse;
use Rzp\Accounts\Merchant\V1\MerchantDocument as MerchantDocumentProto;
use Rzp\Accounts\Merchant\V1\MerchantDocumentResponse;
use Rzp\Accounts\Merchant\V1\MerchantDocumentResponseByMerchantId;
use Rzp\Accounts\Merchant\V1\MerchantDocumentSaveRequest;
use Rzp\Accounts\Merchant\V1\SaveRequest;
use Rzp\Accounts\Merchant\V1\SaveResponse;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Base\Entity;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\WriteEnabledOnAsv;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;
use RZP\Models\Merchant\Document\Repository;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;
use Unit\Models\Merchant\TestingHelper\RepositoryTestHelper;

class RepositoryTest extends RepositoryTestHelper
{

    private $merchantDocumentEntityJson1 = '{
            "id": "JWNkBHL4Waqqf8",
            "file_store_id": "JWNkBU4FxTBStc",
            "source": "UFH",
            "merchant_id": "D2fahy3beSAu0S",
            "document_type": "sla_sebi_registration_certificate",
            "entity_type": "merchant",
            "ocr_verify": null,
            "created_at": 1652809548,
            "updated_at": 1652809548,
            "deleted_at": null,
            "validation_id": null,
            "entity_id": "D2fahy3beSAu0S",
            "document_date": null,
            "upload_by_admin_id": "IRSsFGIthPeh1t",
            "audit_id": "testtesttestid",
            "metadata": null
        }';

    private $merchantDocumentEntityJson2 = ' {
            "id": "JWeLWQE83MHPsU",
            "file_store_id": "JWeLWktZ6Id3NL",
            "source": "UFH",
            "merchant_id": "D2fahy3beSAu0S",
            "document_type": "memorandum_of_association",
            "entity_type": "merchant",
            "ocr_verify": null,
            "created_at": 1652868017,
            "updated_at": 1652868017,
            "deleted_at": null,
            "validation_id": null,
            "entity_id": "D2fahy3beSAu0S",
            "document_date": null,
            "upload_by_admin_id": "IRSsFGIthPeh1t",
            "audit_id": "testtesttestid",
            "metadata": null
        }';

    private $merchantDocumentEntityJson3 = ' {
            "id": "JWeLWQE83MHPs3",
            "file_store_id": "JWeLWktZ6Id3NL",
            "source": "UFH",
            "merchant_id": "D2fahy3beSAu0S",
            "document_type": "memorandum_of_association",
            "entity_type": "merchant",
            "ocr_verify": null,
            "created_at": 1652868017,
            "updated_at": 1652868017,
            "deleted_at": null,
            "validation_id": null,
            "entity_id": "D2fahy3beSAu0S",
            "document_date": null,
            "upload_by_admin_id": "IRSsFGIthPeh1t",
            "audit_id": "testtesttestid",
            "metadata": null
        }';

    private $merchantEntityJson1 = '{
        "id": "D2fahy3beSAu0S",
        "org_id": "100000razorpay",
        "default_refund_speed": "normal",
        "created_at": 1687262076,
        "updated_at": 1687262077,
        "country_code": "IN"
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

    public function testMerchantDocumentSaveOrFailMigration()
    {
        $attributes = [
            "source" => "UFH",
            "merchant_id" => "D2fahy3beSAu0S",
            "document_type" => "memorandum_of_association",
            "entity_type" => "merchant",
        ];

        $this->validateSaveOrFailReadMigration("merchant_document", $attributes, new Repository());
    }

    public function testFindDocumentByMerchantIdAndType()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_merchant_document_read_by_type_and_merchant_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson2);

        $merchantDocumentEntity1 = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);

        $merchantDocumentProto1               = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);
        $merchantDocumentResponseByMerchantId = (new MerchantDocumentResponseByMerchantId())->setDocuments([$merchantDocumentProto1]);

        // Test Case 1 - ExclusionFlow true - Request for findDocumentByMerchantIdAndType  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $merchantDocument                  = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 2 - ExclusionFlow false - Splitz on - Request for findDocumentByMerchantIdAndType  should always go to account service
        $this->setSplitzWithOutput("true", 0);
        $this->setMerchantDocumentMockClientWithIdAndResponse("D2fahy3beSAu0S", $merchantDocumentResponseByMerchantId, null, "getByMerchantId", 1);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument                  = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 3 - ExclusionFlow false - Splitz on - Request for findDocumentByMerchantIdAndType  should always go to account service -Invalid Id
        $this->setSplitzWithOutput("true", 0);
        $this->setMerchantDocumentMockClientWithIdAndResponse("randomId000000", new $merchantDocumentResponseByMerchantId(), null, "getByMerchantId", 1);
        $repo             = new Repository();
        $repo->asvRouter  = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("randomId000000", "sla_sebi_registration_certificate");
        self::assertEquals(null, $merchantDocument);

    }

    public function testFindOrFail()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_document_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson2);

        $merchantDocumentEntity1 = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);

        $merchantDocumentProto1   = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);
        $merchantDocumentResponse = (new MerchantDocumentResponse())->setDocument($merchantDocumentProto1);

        // Test Case 1 - ExclusionFlow true - Request for finById  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $merchantDocument                  = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 3 - ExclusionFlow false - Splitz off - Request for finById  should always go to account service
        $this->setSplitzWithOutput("false", 0);
        $this->setMerchantDocumentMockClientWithIdAndResponse("JWNkBHL4Waqqf8", $merchantDocumentResponse, null, "getById", 1);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument                  = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 6 - ExclusionFlow false - Splitz on - Request for finById  should always go to account service -Invalid Id
        $this->setSplitzWithOutput("true", 0);
        $this->setMerchantDocumentMockClientWithIdAndResponse("randomId", null, new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"), "getById", 1);
        $repo             = new Repository();
        $repo->asvRouter  = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("randomId");
        self::assertEquals(null, $merchantDocument);

    }

    public function testFind()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_document_find', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson2);

        $merchantDocumentEntity1 = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);

        $merchantDocumentProto1   = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);
        $merchantDocumentResponse = (new MerchantDocumentResponse())->setDocument($merchantDocumentProto1);

        // Test Case 1 - ExclusionFlow true - Request for findById  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $merchantDocument                  = $repo->find("JWNkBHL4Waqqf8");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 2 - ExclusionFlow false - Request for finById  should always go to account service
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("JWNkBHL4Waqqf8", $merchantDocumentResponse, null, "getById", 1);
        $repo                              = new Repository();
        $repo->asvRouter                   = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument                  = $repo->find("JWNkBHL4Waqqf8");
        $merchantDocumentArray             = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 6 - ExclusionFlow false - Splitz on - Request for finById  should always go to account service -Invalid Id
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("randomId", null, new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"), "getById", 1);
        $repo             = new Repository();
        $repo->asvRouter  = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->find("randomId");
        self::assertEquals(null, $merchantDocument);

    }

    public function testDocumentFindByIdAndMerchantOperation()
    {
        Config::set('applications.asv_v2.splitz_send_filter_to_asv', PublicEntity::generateUniqueId());
        $id1 = PublicEntity::generateUniqueId();
        $id2 = PublicEntity::generateUniqueId();
        $merchantId = PublicEntity::generateUniqueId();
        $randomMerchantId = PublicEntity::generateUniqueId();
        $this->fixtures->create('merchant', ['id' => $merchantId]);
        $this->fixtures->create('merchant', ['id' => $randomMerchantId]);
        $this->fixtures->create('merchant_document', ['id' => $id1, 'merchant_id' => $merchantId]);
        $this->fixtures->create('merchant_document', ['id' => $id2, 'merchant_id' => $randomMerchantId]);

        $repository = new MerchantRepository();
        $merchant       = $repository->findOrFail($merchantId);
        $nonOwnerMerchant       = $repository->findOrFail($randomMerchantId);

        $this->setSplitzWithOutput("false", 1);
        $repository = new Repository();
        $resultWithoutSplitzOff = $repository->findByIdAndMerchant($id1, $merchant);

        // reset connection because when we query from asv laravel attaches db connection with entity ,
        // so we manually reset the connection with entity
        $repository->resetConnectionOnModels($resultWithoutSplitzOff);

        $this->setSplitzWithOutput("true", 1);
        $repository = new Repository();
        $resultWithSplitzOn = $repository->findByIdAndMerchant($id1, $merchant);
        $this->assertEquals($resultWithoutSplitzOff, $resultWithSplitzOn, "response with and without splitz are not same");
        $this->assertEquals(get_class($resultWithoutSplitzOff), get_class($resultWithSplitzOn));

        $this->setSplitzWithOutput("true", 1);
        $repository = new Repository();
        try {
            $repository->findByIdAndMerchant($id1, $nonOwnerMerchant);
            // this should throw bad request exception if merchant is not parent
            throw new \Exception();
        } catch (BadRequestException $ex){

        }
    }

    public function testMerchantDocumentAssociation()
    {
        $entitiesData = [
            [
                "AssociatedEntityRepo" => new MerchantRepository(),
                "AssociatedEntityName" => "merchant",
                "AssociatedEntityData" => $this->merchantEntityJson1,
                "AssociatedEntityClass" => new MerchantEntity(),
                "merchant_id" => "D2fahy3beSAu0S",
                "shouldEntityNeedsToBeCreated" => true,
            ]
        ];


        for ($i = 0; $i < count($entitiesData); $i++) {
            $data = $entitiesData[$i];

            $this->createEntityInDatabase($data["AssociatedEntityName"], $data["AssociatedEntityData"], $data["AssociatedEntityClass"]);

            if ($data["shouldEntityNeedsToBeCreated"]) {
                $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
            }

            $entityRepo             = $data["AssociatedEntityRepo"];
            $entity                 = $entityRepo->find($data["merchant_id"]);
            $merchantDocument1      = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);
            $merchantDocument1Array = $merchantDocument1->toArray();
            $merchantDocumentProto1 = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);


//            // TestCase2 - when route belongs to exclusive flow -splitz on, data will be fetched from db
            $entity->unsetRelation('merchantDocuments');
            $merchantDocumentRepo            = new Repository();
            $this->setSplitzWithOutputForBulk(["true", "true"], 0);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(0))->method('isTransactionActive')->willReturn(true);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(true);
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);


            //TestCase3 both experiment is false, data will be fetched from db
            $entity->unsetRelation('merchantDocuments');
            $this->setSplitzWithOutputForBulk(["false", "false"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);

            //TestCase4 - experiment respons - false, true, data will be fetched from db
            $entity->unsetRelation('merchantDocuments');
            $this->setSplitzWithOutputForBulk(["false", "true"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);

            //TestCase5 - experiment respons - true, false. data will be fetched from db
            $entity->unsetRelation('merchantDocuments');
            $this->setSplitzWithOutputForBulk(["true", "false"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);

            //TestCase6 - call is going to asv, data will be fetched from asv
            $entity->unsetRelation('merchantDocuments');
            $merchantDocumentResponseByMerchantId = (new MerchantDocumentResponseByMerchantId())->setDocuments([$merchantDocumentProto1]);
            $this->setMerchantDocumentMockClientWithIdAndResponse($data["merchant_id"], $merchantDocumentResponseByMerchantId, null, "getByMerchantId", 1);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);

            //TestCase7 - should go to account service - Exception occurs fallback to DB.  data will be fetched from db
            $entity->unsetRelation('merchantDocuments');
            $this->setMerchantDocumentMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_DEADLINE_EXCEEDED, "deadline exceeded"), "getByMerchantId", 1);
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure', 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            $this->updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocument1Array);

            //TestCase8 - Not found in asv;  data will be null
            $entity->unsetRelation('merchantDocuments');
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure' , 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $merchantDocumentRepo          = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            app('repo')->merchant_document = $merchantDocumentRepo;
            $this->setMerchantDocumentMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"), "getByMerchantId", 1);
            $this->assertEquals(count($entity->merchantDocuments), 0);

            //TestCase9 - invalid argument in asv; data will be null
            $entity->unsetRelation('merchantDocuments');
            $this->setSplitzWithOutputForBulk(["true", "true"], 1);
            $asvRouterMock = $this->getMockBuilder(AsvRouter::class)->enableOriginalConstructor()->onlyMethods(['isExclusionFlowOrFailure' , 'isTransactionActive'])->getMock();
            $asvRouterMock->expects($this->exactly(1))->method('isTransactionActive')->willReturn(false);
            $asvRouterMock->expects($this->exactly(1))->method('isExclusionFlowOrFailure')->willReturn(false);
            $merchantDocumentRepo            = new Repository();
            $merchantDocumentRepo->asvRouter = $asvRouterMock;
            app('repo')->merchant_document   = $merchantDocumentRepo;
            $this->setMerchantDocumentMockClientWithIdAndResponse($data["merchant_id"], null, new GrpcError(\Grpc\STATUS_INVALID_ARGUMENT, "Invalid Argument"), "getByMerchantId", 1);
            $this->assertEquals(count($entity->merchantDocuments), 0);
        }
    }

    private function updateDocumentAuditIdAndAssert($merchantDocumentRepo, $entity, $merchantDocumentEntity1Array)
    {
        app('repo')->merchant_document = $merchantDocumentRepo;
        $documents                     = $entity->merchantDocuments->toArray();
        $documents[0]["audit_id"]      = "testtesttestid";
        $this->assertEquals($merchantDocumentEntity1Array, $documents[0]);
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
        $splitz                                                 = $this->sampleSpltizOutput;
        $splitz["response"]["variant"]["variables"][0]["value"] = $output;
        $splitzMock                                             = $this->createSplitzMock();
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

    private function setMerchantDocumentMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchantDocument           = new merchantDocument();
        $merchantDocumentMockClient = $this->getMockClient();
        $merchantDocumentMockClient->expects($this->exactly($count))->method($method)->with($id, $merchantDocument->getDefaultRequestMetaData())->willReturn([$response, $error]);
        $merchantDocument->getAsvSdkClient()->setDocument($merchantDocumentMockClient);

    }

    private function createMerchantDocumentInDatabase($json)
    {
        $this->fixtures->create("merchant_document",
            $this->getMerchantDocumentEntityFromJson($json)->toArray(),
        );
    }


    private function getMerchantDocumentProtoFromJson(string $json): MerchantDocumentProto
    {
        $merchantDocumentProto = new MerchantDocumentProto();
        $merchantDocumentProto->mergeFromJsonString($json, false);
        return $merchantDocumentProto;
    }

    private function getMerchantDocumentEntityFromJson(string $json): MerchantDocumentEntity
    {
        $merchantDocumentArray  = json_decode($json, true);
        $merchantDocumentEntity = new MerchantDocumentEntity();
        $merchantDocumentEntity->setRawAttributes($merchantDocumentArray);
        return $merchantDocumentEntity;
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
        return $this->getMockBuilder("Razorpay\Asv\Interfaces\DocumentInterface")
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
