<?php

namespace RZP\Models\Merchant\Acs\ParityChecker\Entity;

use RZP\Exception\BadRequestException;
use RZP\Exception\BaseException;
use RZP\Models\Merchant\Acs\ParityChecker\Constant\Constant;
use RZP\Models\Merchant\Acs\ParityChecker\ParityInterface;
use RZP\Models\Merchant\Document\Entity as DocumentEntity;

class MerchantDocument extends Base implements ParityInterface
{
    protected $document;

    function __construct(string $merchantId, array $parityCheckMethods)
    {
        parent::__construct($merchantId, $parityCheckMethods);
        $this->document = new \RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument();
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    public function checkParity()
    {
        $merchantDocuments = $this->repo->merchant_document->getAllDocumentsFromReplica($this->merchantId);

        if (in_array(Constant::GET_BY_MERCHANT_ID, $this->parityCheckMethods) === true) {
            $documentTypeMap = [];
            foreach ($merchantDocuments as $merchantDocument) {
                $documentTypeMap[$merchantDocument[DocumentEntity::DOCUMENT_TYPE]] = true;
            }

            foreach ($documentTypeMap as $documentType => $value) {
                $this->checkParityForGetDocumentByMerchantIdAndType($this->merchantId, $documentType);
            }

        } else if (in_array(Constant::GET_BY_ID, $this->parityCheckMethods) === true) {
            foreach ($merchantDocuments as $merchantDocument) {
                $this->checkParityForGetDocumentById($merchantDocument[DocumentEntity::ID]);
            }
        }
    }

    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetDocumentById(string $id)
    {
        $merchantDocumentFromAPIDb = $this->repo->merchant_document->findDocumentByIdFromDatabase($id);
        $merchantDocumentFromASV = $this->repo->merchant_document->findDocumentById($id);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_DOCUMENT,
            Constant::ID => $id,
            Constant::METHOD => "checkParityForGetDocumentById"
        ];

        if ($merchantDocumentFromAPIDb === null or $merchantDocumentFromASV === null) {

            $additionalLogDetailUnmatched = [
                Constant::API_ENTITY_ARRAY => $merchantDocumentFromAPIDb?->toArray(),
                Constant::ASV_ENTITY_ARRAY => $merchantDocumentFromASV?->toArray(),
            ];

            $this->compareAndLogApiAndAsvResponseForNull($merchantDocumentFromAPIDb, $merchantDocumentFromASV, $logDetailMatched, $additionalLogDetailUnmatched);
        }

        $merchantDocumentArrayFromAPIDbRawAttributes = $merchantDocumentFromAPIDb->getAttributes();
        $merchantDocumentArrayFromAPIDbArray = $merchantDocumentFromAPIDb->toArray();

        $merchantDocumentArrayFromASVRawAttributes = $merchantDocumentFromASV->getAttributes();
        $merchantDocumentArrayFromASVArray = $merchantDocumentFromASV->toArray();

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantDocumentArrayFromAPIDbRawAttributes, $merchantDocumentArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantDocumentArrayFromAPIDbArray, $merchantDocumentArrayFromASVArray);

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantDocumentArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantDocumentArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantDocumentArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantDocumentArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }


    /**
     * @throws BadRequestException
     * @throws BaseException
     */
    function checkParityForGetDocumentByMerchantIdAndType(string $merchantId, string $documentType)
    {
        $merchantDocumentFromAPIDb = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentTypeFromDatabase($merchantId, $documentType);
        $merchantDocumentFromASV = $this->repo->merchant_document->findDocumentsForMerchantIdAndDocumentType($merchantId, $documentType);

        $logDetailMatched = [
            Constant::ENTITY => Constant::MERCHANT_DOCUMENT,
            Constant::MERCHANT_ID => $merchantId,
            Constant::TYPE => $documentType,
            Constant::METHOD => "checkParityForGetDocumentByMerchantIdAndType"
        ];

        if ($merchantDocumentFromAPIDb === null or $merchantDocumentFromASV === null) {

            $additionalLogDetailUnmatched = [
                Constant::API_ENTITY_ARRAY => $merchantDocumentFromAPIDb?->toArray(),
                Constant::ASV_ENTITY_ARRAY => $merchantDocumentFromASV?->toArray(),
            ];

            $this->compareAndLogApiAndAsvResponseForNull($merchantDocumentFromAPIDb, $merchantDocumentFromASV, $logDetailMatched, $additionalLogDetailUnmatched);
        }

        $merchantDocumentArrayFromAPIDbRawAttributes = $merchantDocumentFromAPIDb->getAttributes();
        $merchantDocumentArrayFromAPIDbArray = $merchantDocumentFromAPIDb->toArray();

        $merchantDocumentArrayFromASVRawAttributes = $merchantDocumentFromASV->getAttributes();
        $merchantDocumentArrayFromASVArray = $merchantDocumentFromASV->toArray();

        $differenceRawAttributes = $this->comparator->getExactDifference($merchantDocumentArrayFromAPIDbRawAttributes, $merchantDocumentArrayFromASVRawAttributes);
        $differenceArray = $this->comparator->getExactDifference($merchantDocumentArrayFromAPIDbArray, $merchantDocumentArrayFromASVArray);

        $additionalLogDetailUnmatched = [
            Constant::DIFFERENCE_RAW_ATTRIBUTES => $differenceRawAttributes,
            Constant::DIFFERENCE_ARRAY => $differenceArray,
            Constant::API_ENTITY_ARRAY => $merchantDocumentArrayFromAPIDbArray,
            Constant::ASV_ENTITY_ARRAY => $merchantDocumentArrayFromASVArray,
            Constant::API_ENTITY_RAW_ATTRIBUTES => $merchantDocumentArrayFromAPIDbRawAttributes,
            Constant::ASV_ENTITY_RAW_ATTRIBUTES => $merchantDocumentArrayFromASVRawAttributes,
        ];

        $this->compareAndLogApiAndAsvResponse($differenceRawAttributes, $differenceArray, $logDetailMatched, $additionalLogDetailUnmatched);
    }
}
