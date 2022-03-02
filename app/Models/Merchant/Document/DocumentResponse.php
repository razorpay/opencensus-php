<?php

namespace RZP\Models\Merchant\Document;

use RZP\Constants\HyperTrace;
use RZP\Models\Merchant;
use RZP\Constants\Entity;
use RZP\Models\Merchant\Detail;
use RZP\Models\GenericDocument;
use RZP\Models\Merchant\Document;
use RZP\Trace\Tracer;


class DocumentResponse extends Detail\Core
{
    public function documentsResponse(Merchant\Entity $merchant, string $entityType, string $entityId)
    {
        $merchantDetails = $this->getMerchantDetails($merchant);

        $fieldsRequired = Tracer::inspan(['name' => HyperTrace::GET_REQUIRED_DOC_TYPES], function () use ($merchantDetails, $entityType) {

            // get documents required as per business type, category, subcategory
            return $this->getDocumentsGroupedAndMergedByProofType($merchantDetails, $entityType);
        });

        // get currently uploaded documents
        $documentsArr = [];
        $documents = $this->repo->merchant_document->findDocumentsForEntityTypeAndEntityId($entityType, $entityId);

        $documentService = new GenericDocument\Service;

        foreach ($documents as $document)
        {
            $documentsArr[$document->getDocumentType()] = $document;
        }

        $returnData = Tracer::inspan(['name' => HyperTrace::CONSTRUCT_DOCUMENT_V2_RESPONSE], function () use ($fieldsRequired, $documentService, $documentsArr) {

            // construct documents as per required docs and uploaded docs
            $returnData = [];

            foreach ($fieldsRequired as $proofType => $fields) {
                foreach ($fields as $field) {
                    if (isset($documentsArr[$field]) === true) {
                        if (isset($returnData[$proofType]) === false) {
                            $returnData[$proofType] = [];
                        }

                        $signedUrlResponse = $documentService->getDocumentDownloadLinkFromUFH([], $documentsArr[$field]->getPublicFileStoreId());

                        $returnData[$proofType][] = [
                            Constants::TYPE => $documentsArr[$field]->getDocumentType(),
                            Constants::URL => $signedUrlResponse['signed_url'],
                        ];
                    }
                }
            }
            return $returnData;
        });

        return $returnData;
    }

    public function getDocumentsGroupedAndMergedByProofType(Detail\Entity $merchantDetails, string $entityType): array
    {
        $fields = $this->getValidationFields($merchantDetails, true);

        $fields = $this->filterFieldsForDocuments($fields);

        $selectiveFields = $fields[1];

        $allFields = [];

        foreach ($selectiveFields as $groupName => $group)
        {
            $allFields[] = self::mergeFirstLevelArrays($group);
        }

        $allFields[] = $fields[0];

        $allFields[] = $fields[2];

        $allFields = self::mergeFirstLevelArrays($allFields);

        $returnData = [];

        foreach ($allFields as $field)
        {
            $proofType = Document\Type::DOCUMENT_TYPE_TO_PROOF_TYPE_MAPPING[$field];

            if (Document\Type::PROOF_TYPE_ENTITY_MAPPING[$proofType] === $entityType)
            {
                if (isset($returnData[$proofType]) === false)
                {
                    $returnData[$proofType] = [];
                }

                $returnData[$proofType][] = $field;
            }
        }
        if ($entityType === Entity::MERCHANT)
        {
            $additionalDocuments = $returnData[Document\Type::ADDITIONAL_DOCUMENTS] ?? [];

            $returnData[Document\Type::ADDITIONAL_DOCUMENTS]  = array_merge($additionalDocuments , Document\Type::BANK_PROOF_DOCUMENTS);
        }

        return $returnData;
    }

    protected static function mergeFirstLevelArrays(array $maps)
    {
        $result = [];

        foreach ($maps as $map)
        {
            $result = array_merge($result, $map);
        }

        return $result;
    }

    protected function filterFieldsForDocuments(array $fields): array
    {
        $returnRequiredFields = [];
        $requiredFields = $fields[0];

        foreach ($requiredFields as $field)
        {
            if (Document\Type::isValid($field) === true)
            {
                $returnRequiredFields[] = $field;
            }
        }

        $returnSelectiveFields = [];
        $selectiveFields = $fields[1];

        foreach ($selectiveFields as $groupName => $group)
        {
            foreach ($group as $key1 => $set)
            {
                foreach ($set as $field)
                {
                    if (Document\Type::isValid($field) === true)
                    {
                        $returnSelectiveFields[$groupName][$key1][] = $field;
                    }
                }
            }
        }

        $returnOptionalFields = [];
        $optionalFields = $fields[2];

        foreach ($optionalFields as $field)
        {
            if (Document\Type::isValid($field) === true)
            {
                $returnOptionalFields[] = $field;
            }
        }

        return [$returnRequiredFields, $returnSelectiveFields, $returnOptionalFields];
    }
}
