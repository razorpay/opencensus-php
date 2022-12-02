<?php

namespace RZP\Modules\Acs\Wrapper;

use Rzp\Accounts\Account\V1\FetchMerchantDocumentsResponse;
use RZP\Constants\Metric;
use RZP\Models\Base\PublicCollection;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Comparator\MerchantDocumentComparator;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvClient;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;
use Throwable;

class MerchantDocument extends Base
{
    public $accountDocumentAsvClient;
    private string $entityName;
    private MerchantDocumentComparator $merchantDocumentComparator;
    private SaveApiHelper $saveApiHelper;

    function __construct()
    {
        parent::__construct();
        $this->accountDocumentAsvClient = new AsvClient\AccountDocumentAsvClient();
        $this->entityName = 'merchant_document';
        $this->merchantDocumentComparator = new MerchantDocumentComparator();
        $this->saveApiHelper = new SaveApiHelper();
    }

    /**
     * @param MerchantDocumentEntity $entity
     * @throws \RZP\Exception\IntegrationException
     */
    public function DeleteOrFail(MerchantDocumentEntity $entity)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'shadow', 'write') === true) {
            try {
                $this->accountDocumentAsvClient->DeleteAccountDocument($entity['id']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::ERROR, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'shadow'
                ]);
            }
        } else if ($this->isShadowOrReverseShadowOnForOperation($entity->getMerchantId(), 'reverse_shadow', 'write') === true) {
            try {
                $this->accountDocumentAsvClient->DeleteAccountDocument($entity['id']);
            } catch (\Exception $e) {
                $this->trace->traceException($e, Trace::CRITICAL, TraceCode::ASV_WRITE_EXCEPTION, [
                    'merchant_id' => $entity->getMerchantId(), 'entity_name' => $entity->getEntityName(), 'operation' => 'write->delete', 'mode' => 'reverse_shadow'
                ]);
                throw $e;
            }
        }
    }

    public function FindDocumentsForMerchantId(string $merchantId, PublicCollection $documentsFromAPI)
    {
        if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::SHADOW, CONSTANT::READ)) {
            $this->findDocumentsForMerchantIdShadow($merchantId, $documentsFromAPI);
            return $documentsFromAPI;
        }
        else if ($this->isShadowOrReverseShadowOnForOperation($merchantId, CONSTANT::REVERSE_SHADOW, CONSTANT::READ)) {
            return $this->findDocumentsForMerchantIdReverseShadow($merchantId, $documentsFromAPI);
        }

        return $documentsFromAPI;
    }

    /**
     * @param MerchantDocumentEntity $entity
     * @throws \RZP\Exception\IntegrationException
     * @throws \Google\ApiCore\ValidationException
     */
    public function SaveOrFail(MerchantDocumentEntity $entity)
    {
        $this->saveApiHelper->saveOrFail($entity->getMerchantId(), $entity->getEntityName(), $entity->getEntityName(), $entity->toArray());
    }


    private function findDocumentsForMerchantIdShadow(string $merchantId, $documentsFromAPI): PublicCollection
    {
        try {
            $this->fetchAndCompareMerchantDocumentsFromMerchantId($merchantId, $documentsFromAPI);
        } catch (Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $merchantId,
                    "entity" => $this->entityName,
                ]);
        }
        return $documentsFromAPI;
    }

    /**
     * @throws Throwable
     */
    private function findDocumentsForMerchantIdReverseShadow(string $merchantId, $documentsFromAPI): PublicCollection
    {
        try {
            $documentsFromASV = $this->fetchAndCompareMerchantDocumentsFromMerchantId($merchantId, $documentsFromAPI);
            return ASVEntityMapper::OverwriteWithAsvEntities(MerchantDocumentEntity::class, 'id', $documentsFromAPI->toArray(), $documentsFromASV->toArray());
        }catch (Throwable $ex) {
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                TraceCode::ASV_READ_SHADOW_EXCEPTION,
                [
                    "id" => $merchantId,
                    "entity" => $this->entityName,
                ]);
            // rethrow the exception, fail the read on API
            throw $ex;
        }
    }

    private function fetchAndCompareMerchantDocumentsFromMerchantId(string $merchantID, $documentsFromAPI): PublicCollection
    {
        $res = $this->accountDocumentAsvClient->FetchMerchantDocuments($merchantID);
        $documentsFromASV = $this->getMerchantDocumentEntitiesFromResponse($res);
        $difference = $this->merchantDocumentComparator->getDifferenceCompareByUniqueId($documentsFromAPI->toArray(), $documentsFromASV->toArray(), 'id');
        $this->logDifferenceIfNotNilAndPushMetrics($this->entityName, $difference, "", $merchantID);
        return $documentsFromASV;
    }

    public function logDifferenceIfNotNilAndPushMetrics(string $entityName, array $difference, string $id, string $merchant_id) {
        if(count($difference) > 0) {
            $this->trace->info(TraceCode::ASV_COMPARE_MISMATCH, [
                'entity_name' => $entityName,
                'difference' => $difference,
                'document_id' => $id ,
                'merchant_id' =>$merchant_id
            ]);
            $this->trace->count(Metric::ASV_COMPARE_MISMATCH, [$entityName]);
        }
    }

    private function getMerchantDocumentEntitiesFromResponse(FetchMerchantDocumentsResponse $res): PublicCollection
    {
        $merchant_documents = [];
        $documentsFromAsv = $res->getDocuments();
        foreach ($documentsFromAsv as $document) {
            $merchant_document = ASVEntityMapper::MapProtoObjectToEntity($document, MerchantDocumentEntity::class);
            array_push($merchant_documents, $merchant_document);
        }
        return new PublicCollection($merchant_documents);
    }
}
