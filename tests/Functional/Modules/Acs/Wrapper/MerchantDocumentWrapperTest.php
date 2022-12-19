<?php

namespace RZP\Tests\Functional\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantDocumentsResponse;
use RZP\Constants\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Modules\Acs\Wrapper\Constant;
use RZP\Tests\Functional\TestCase;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Exception\IntegrationException;
use RZP\Modules\Acs\Wrapper\MerchantDocument;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;
use RZP\Trace\TraceCode;

class MerchantDocumentWrapperTest extends TestCase
{

    function setUp(): void
    {
        parent::setUp();
    }

    function tearDown(): void
    {
        parent::tearDown();
    }

    function testDeleteOrFail()
    {
        MerchantDocumentEntity::unguard();
        $documentEntity = new MerchantDocumentEntity(['id' => '10000000000111', 'merchant_id' => '10000000000000']);
        MerchantDocumentEntity::reguard();

        $exception = new IntegrationException('some error encountered');

        #T1 Starts - Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument');

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T1 Ends


        #T2 Starts - Shadow on - Failure in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument')->willThrowException($exception);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T2 Ends


        #T3 Starts - Reverse Shadow on - Success in Asv
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument');

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
        #T3 Ends

        #T4 Starts - Reverse Shadow on - Failure in Asv - Propagate Exception
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException');

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('DeleteAccountDocument')->willThrowException($exception);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')
            ->withConsecutive(['10000000000000', 'shadow', 'write'], ['10000000000000', 'reverse_shadow', 'write'])->willReturnOnConsecutiveCalls(false, true);

        try {
            $merchantDocumentWrapperMock->DeleteOrFail($documentEntity);
            assertTrue(false);
        } catch (IntegrationException $e) {
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }
        #T4 Ends
    }

    function testFindDocumentsForMerchantId() {
        $merchantId = "10000000000000";
        $entityId = "10000000000001";
        $documentId = "10000000000002";
        $validationId = "10000000000003";
        MerchantDocumentEntity::unguard();
        $merchantDocumentEntityForApi = new MerchantDocumentEntity(['id' => $documentId, 'merchant_id' => $merchantId, 'entity_id' => $entityId, 'entity_type' => 'merchant', 'validation_id' => $validationId]);
        $merchantDocumentCollectionForApi = new PublicCollection([$merchantDocumentEntityForApi]);
        $merchantDocumentEntityForAsvWithMismatch = new MerchantDocumentEntity(['id' => $documentId, 'merchant_id' => $merchantId, 'entity_id' => $entityId, 'entity_type' => 'account', 'validation_id' => $validationId]);
        $merchantDocumentCollectionForAsvWithMismatch = new PublicCollection([$merchantDocumentEntityForAsvWithMismatch]);
        MerchantDocumentEntity::reguard();
        $merchantDocumentProto = new \Rzp\Accounts\Account\V1\MerchantDocument(['id' => $documentId, 'merchant_id' => $merchantId, 'entity_id' => $entityId, 'entity_type' => 'merchant']);
        $fetchMerchantDocumentsResponse = new FetchMerchantDocumentsResponse(['documents'=> [$merchantDocumentProto]]);
        $merchantDocumentProtoMismatch = new \Rzp\Accounts\Account\V1\MerchantDocument(['id' => $documentId, 'merchant_id' => $merchantId, 'entity_id' => $entityId, 'entity_type' => 'account']);
        $fetchMerchantDocumentsResponseMismatch = new FetchMerchantDocumentsResponse(['documents'=> [$merchantDocumentProtoMismatch]]);


        //Shadow mode returns values from api entity
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->never())->method('info');
        $traceMock->expects($this->never())->method('count');
        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willReturn($fetchMerchantDocumentsResponse);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedCollection = $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
        self::assertEquals($merchantDocumentCollectionForApi, $returnedCollection);

        //ReverseShadow overrides common fields with values from asv and logs difference
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->never())->method('traceException');
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_document", "document_id" => "", "difference" =>[$documentId =>["entity_type"]], 'merchant_id' => $merchantId]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_document"]]);
        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willReturn($fetchMerchantDocumentsResponseMismatch);

        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedCollection = $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
        self::assertEquals($merchantDocumentCollectionForAsvWithMismatch, $returnedCollection);

        //Exception in ReadShadow From FetchMerchantDocuments
        $traceMock = $this->createTraceMock();
        $exception = new IntegrationException('error in FetchMerchantDocuments');
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::ERROR, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_document"]]);

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willThrowException($exception);
        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        try {
            $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception is not raised in shadow if asv call fails
            assertTrue(false);
        }

        //Exception in Reverse Shadow From FetchMerchantDocuments
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('traceException')->withConsecutive([$exception, Trace::CRITICAL, TraceCode::ASV_READ_SHADOW_EXCEPTION, ["id" => $merchantId, "entity" => "merchant_document"]]);

        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willThrowException($exception);
        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        try {
            $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
            assertTrue(true);
        } catch (IntegrationException $e) {
            //Exception will be raised in reverse_shadow if asv call fails
            assertTrue(true);
            self::assertEquals($exception->getMessage(), $e->getMessage());
        }

        //Empty Response from ASV in Shadow logged and returns api entities
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_document", "document_id" => "", "difference" =>[$documentId =>"Entity Present in only one of ASV/API"], 'merchant_id' => $merchantId]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_document"]]);
        $fetchMerchantDocumentsEmptyResponse = new FetchMerchantDocumentsResponse();
        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willReturn($fetchMerchantDocumentsEmptyResponse);
        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(1))->method('isShadowOrReverseShadowOnForOperation')->willReturn(true);
        $returnedCollection = $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
        self::assertEquals($merchantDocumentCollectionForApi, $returnedCollection);

        //Empty Response from ASV in ReverseShadow logged and returns empty collection
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(1))->method('info')->withConsecutive([TraceCode::ASV_COMPARE_MISMATCH, ["entity_name" => "merchant_document", "document_id" => "", "difference" =>[$documentId =>"Entity Present in only one of ASV/API"], 'merchant_id' => $merchantId]]);
        $traceMock->expects($this->exactly(1))->method('count')->withConsecutive([Metric::ASV_COMPARE_MISMATCH, ["merchant_document"]]);
        $fetchMerchantDocumentsEmptyResponse = new FetchMerchantDocumentsResponse();
        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willReturn($fetchMerchantDocumentsEmptyResponse);
        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedCollection = $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, $merchantDocumentCollectionForApi);
        self::assertEquals(new PublicCollection([]), $returnedCollection);

        //Empty Response from ASV in ReverseShadow with empty API collection: no diff logged and returns empty collection
        $traceMock = $this->createTraceMock();
        $traceMock->expects($this->exactly(0))->method('info');
        $traceMock->expects($this->exactly(0))->method('count');
        $fetchMerchantDocumentsEmptyResponse = new FetchMerchantDocumentsResponse();
        $accountDocumentAsvClientMock = $this->createAccountDocumentAsvClientMock();
        $accountDocumentAsvClientMock->expects($this->exactly(1))->method('FetchMerchantDocuments')->willReturn($fetchMerchantDocumentsEmptyResponse);
        $merchantDocumentWrapperMock = $this->getMockedMerchantDocumentWrapper(['isShadowOrReverseShadowOnForOperation']);
        $merchantDocumentWrapperMock->accountDocumentAsvClient = $accountDocumentAsvClientMock;
        $merchantDocumentWrapperMock->expects($this->exactly(2))->method('isShadowOrReverseShadowOnForOperation')->
        withConsecutive([$merchantId, CONSTANT::SHADOW, CONSTANT::READ], [$merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ])->willReturnOnConsecutiveCalls(false, true);
        $returnedCollection = $merchantDocumentWrapperMock->FindDocumentsForMerchantId($merchantId, new PublicCollection([]));
        self::assertEquals(new PublicCollection([]), $returnedCollection);
    }

    protected function getMockedMerchantDocumentWrapper($methods = [])
    {
        return $this->getMockBuilder(MerchantDocument::class)
            ->enableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }

    protected function createTraceMock()
    {
        $traceMock = $this->getMockBuilder(Trace::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->app->instance('trace', $traceMock);
        return $traceMock;
    }

    protected function createAccountDocumentAsvClientMock()
    {
        return $this->getMockBuilder(AsvClient\AccountDocumentAsvClient::class)
            ->getMock();
    }
}
