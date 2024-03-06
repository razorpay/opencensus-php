<?php

namespace RZP\Models\Merchant\Acs\AsvSdkIntegration;

use RZP\Error\ErrorCode;
use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use Rzp\Accounts\Merchant\V1 as MerchantV1;
use Rzp\Accounts\Merchant\V1\FilterRequest;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Utils\ProtoToEntityConverter\MerchantDocument as MerchantDocumentProtoMapper;

class MerchantDocument extends Base
{

    const FILTER_TIMEOUT_IN_MICRO_SECONDS = 5000000;

    const GET_MERCHANT_DOCUMENTS_FROM_MERCHANT_IDS = 'get_merchant_documents_from_merchant_ids';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getByMerchantId(string $merchantId, RequestMetadata $requestMetadata = null): PublicCollection
    {
        /**
         * @var MerchantV1\MerchantDocumentResponseByMerchantId $response
         */
        list($response, $err) = $this->asvSdkClient->getDocument()->getByMerchantId(
            $merchantId,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $documentsArray = [];

        $documents = $response->getDocuments();

        /**
         * @var $document MerchantV1\MerchantDocument
         */
        foreach ($documents as $document) {
            $merchantDocumentProtoConvertor = new MerchantDocumentProtoMapper($document);
            $documentEntity = $merchantDocumentProtoConvertor->ToEntity();
            $documentsArray[] = $documentEntity;
        }

        return (new MerchantDocumentEntity)->newCollection($documentsArray);
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getById(string $id, RequestMetadata $requestMetadata = null): MerchantDocumentEntity
    {
        /**
         * @var MerchantV1\MerchantDocumentResponse $response
         */
        list($response, $err) = $this->asvSdkClient->getDocument()->getById(
            $id,
            $this->getRequestMetaData($requestMetadata)
        );

        if ($err !== null) {
            $this->handleError($err);
        }

        $document = $response->getDocument();

        return (new MerchantDocumentProtoMapper($document))->ToEntity();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getByDocumentsByMerchantIdAndType(string $merchantId, string $documentType, ?RequestMetadata $requestMetadata = null): ?MerchantDocumentEntity
    {
        try {
            $merchantDocumentsByMerchantId = $this->getByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT) {
                return null;
            }

            throw $e;
        }

        $merchantDocumentByMerchantIdAndType = [];

        /**
         * @var MerchantDocumentEntity $merchantDocument
         */
        foreach ($merchantDocumentsByMerchantId as $merchantDocument) {
            if ($merchantDocument->getDocumentType() === $documentType) {
                $merchantDocumentByMerchantIdAndType = $merchantDocument;
                break;
            }
        }

        if (empty($merchantDocumentByMerchantIdAndType) === true) {
            return null;
        }

        return $merchantDocumentByMerchantIdAndType;
    }

    public function getByDocumentsByMerchantIdAndTypeCallBack(string $merchantId, string $documentType, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function () use ($merchantId, $documentType, $requestMetadata) {
            return $this->getByDocumentsByMerchantIdAndType($merchantId, $documentType, $requestMetadata);
        };
    }

    public function getDocumentByIdCallback(string $id, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function () use ($id, $requestMetadata) {
            return $this->getById($id, $requestMetadata);
        };
    }

    public function findDocumentByMerchantId(string $merchantId, ?RequestMetadata $requestMetadata = null) : ?PublicCollection
    {
        try {
            $merchantDocumentsByMerchantId = $this->getByMerchantId($merchantId, $requestMetadata);
        } catch (\Exception $e) {
            if ($e->getCode() == ErrorCode::BAD_REQUEST_INVALID_ARGUMENT || $e->getCode() == ErrorCode::BAD_REQUEST_NO_RECORD_FOUND_FOR_ID ) {
                return (new MerchantDocumentEntity)->newCollection([]);
            }
            throw $e;
        }

        return $merchantDocumentsByMerchantId;
    }


    public function findDocumentByMerchantIdCallback(string $merchantId, ?RequestMetadata $requestMetadata = null): \Closure
    {
        return function () use ($merchantId, $requestMetadata) {
            return $this->findDocumentByMerchantId($merchantId, $requestMetadata);
        };
    }

    /**
     * @param array $merchantIds
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findDocumentsForMerchantIds(array $merchantIds): Collection|PublicCollection
    {
        $filterRequest = (new FilterRequest())
            ->setQueryIdentifier(self::GET_MERCHANT_DOCUMENTS_FROM_MERCHANT_IDS)
            ->setBindings(json_encode([$merchantIds]));

        $response = $this->getFilterResponseFromAsv(
            $filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS
        );

        return $this->getMerchantDocumentCollectionFromResponse($response);
    }

    public function findDocumentByFileStoreId(string $fileStoreId): PublicCollection|Collection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('find_documents_by_filestore_id');
        $filterRequest->setBindings(
            json_encode([
                            $fileStoreId
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantDocumentCollectionFromResponse($response);
    }

    public function findDocumentsForMerchantIdAndValidationId(string $merchantId, string $validationId): PublicCollection|Collection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('find_documents_for_merchant_id_and_validation_id');
        $filterRequest->setBindings(
            json_encode([
                            $merchantId, $validationId
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantDocumentCollectionFromResponse($response);
    }

    public function findNonDeletedDocumentsForMerchantId(string $merchantId, array $documentTypes): PublicCollection|Collection
    {
        $filterRequest =  new FilterRequest();
        $filterRequest->setQueryIdentifier('find_non_deleted_documents_for_merchant_id');
        $filterRequest->setBindings(
            json_encode([
                            $merchantId, $documentTypes
                        ])
        );

        $response = $this->getFilterResponseFromAsv($filterRequest, self::FILTER_TIMEOUT_IN_MICRO_SECONDS);

        return $this->getMerchantDocumentCollectionFromResponse($response);
    }
}
