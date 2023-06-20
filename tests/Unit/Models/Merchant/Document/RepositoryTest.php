<?php

namespace Unit\Models\Merchant\Document;

use Config;
use Razorpay\Asv\Error\GrpcError;
use Rzp\Accounts\Merchant\V1\MerchantDocument as MerchantDocumentProto;
use Rzp\Accounts\Merchant\V1\MerchantDocumentResponse;
use Rzp\Accounts\Merchant\V1\MerchantDocumentResponseByMerchantId;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;
use RZP\Models\Merchant\Document\Repository;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Services\SplitzService;
use RZP\Tests\Functional\TestCase;

class RepositoryTest extends TestCase
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

    public function testFindDocumentByMerchantIdAndType()
    {
        // Set splitz experiment
        Config::set('applications.asv_v2.splitz_experiment_merchant_document_read_by_type_and_merchant_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson2);

        $merchantDocumentEntity1 = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);

        $merchantDocumentProto1 = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);
        $merchantDocumentResponseByMerchantId = (new MerchantDocumentResponseByMerchantId())->setDocuments([$merchantDocumentProto1]);

        // Test Case 1 - SaveRoute true - Request for findDocumentByMerchantIdAndType  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 2 - SaveRoute false - Splitz off - Request for findDocumentByMerchantIdAndType  should not go to account service
        $this->setSplitzWithOutput("false", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 3 - SaveRoute false - Splitz off - Request for findDocumentByMerchantIdAndType  should not go to account service - Invalid Id
        $this->setSplitzWithOutput("false", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("randomId", "sla_sebi_registration_certificate");
        self::assertEquals(null, $merchantDocument);

        // Test Case 4 - SaveRoute false - Splitz Exception - Request for findDocumentByMerchantIdAndType  should not go to account service
        $this->splitzShouldThrowException(1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

//        // Test Case 5 - SaveRoute false - Splitz on - Request for findDocumentByMerchantIdAndType  should go to account service
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("D2fahy3beSAu0S", $merchantDocumentResponseByMerchantId, null, "getByMerchantId", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("D2fahy3beSAu0S", "sla_sebi_registration_certificate");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 6 - SaveRoute false - Splitz on - Request for findDocumentByMerchantIdAndType  should go to account service -Invalid Id
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("randomId000000", new $merchantDocumentResponseByMerchantId(), null, "getByMerchantId", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentsForMerchantIdAndDocumentType("randomId000000", "sla_sebi_registration_certificate");
        self::assertEquals(null, $merchantDocument);

    }

    public function testFindById()
    {
        Config::set('applications.asv_v2.splitz_experiment_merchant_document_read_by_id', 'K1ZaAHZ7Lnumc6');

        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson1);
        $this->createMerchantDocumentInDatabase($this->merchantDocumentEntityJson2);

        $merchantDocumentEntity1 = $this->getmerchantDocumentEntityFromJson($this->merchantDocumentEntityJson1);

        $merchantDocumentProto1 = $this->getMerchantDocumentProtoFromJson($this->merchantDocumentEntityJson1);
        $merchantDocumentResponse = (new MerchantDocumentResponse())->setDocument($merchantDocumentProto1);


        // Test Case 1 - SaveRoute true - Request for finById  should not go to account service
        $this->setSplitzWithOutput("false", 0);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, true, null);
        $merchantDocument = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 2 - SaveRoute false - Splitz off - Request for finById  should not go to account service
        $this->setSplitzWithOutput("false", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 3 - SaveRoute false - Splitz off - Request for finById  should not go to account service - Invalid Id
        $this->setSplitzWithOutput("false", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("randomId");
        self::assertEquals(null, $merchantDocument);

        // Test Case 4 - SaveRoute false - Splitz Exception - Request for finById  should not go to account service
        $this->splitzShouldThrowException(1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 5 - SaveRoute false - Splitz on - Request for finById  should go to account service
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("JWNkBHL4Waqqf8", $merchantDocumentResponse, null, "getById", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("JWNkBHL4Waqqf8");
        $merchantDocumentArray = $merchantDocument->toArray();
        $merchantDocumentArray['audit_id'] = 'testtesttestid';
        $this->assertEquals($merchantDocumentEntity1->toArray(), $merchantDocumentArray);

        // Test Case 6 - SaveRoute false - Splitz on - Request for finById  should go to account service -Invalid Id
        $this->setSplitzWithOutput("true", 1);
        $this->setMerchantDocumentMockClientWithIdAndResponse("randomId", null, new GrpcError(\Grpc\STATUS_NOT_FOUND, "Not Found"), "getById", 1);
        $repo = new Repository();
        $repo->asvRouter = $this->getMockAsvRouterInRepository('isExclusionFlowOrFailure', 1, false, null);
        $merchantDocument = $repo->findDocumentById("randomId");
        self::assertEquals(null, $merchantDocument);

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

    private function setMerchantDocumentMockClientWithIdAndResponse($id, $response, $error, $method, $count)
    {
        $merchantDocument = new merchantDocument();
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
        $merchantDocumentArray = json_decode($json, true);
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
