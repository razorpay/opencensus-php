<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

use RZP\Models\Base;
use RZP\Models\Merchant\InternationalEnablement\Detail;

class Core extends Base\Core
{
    private function create(array $input, Detail\Entity $detailEntity, string $merchantId)
    {
        $entity = new Entity;

        $entity->build($input);

        $entity->setMerchantId($merchantId);

        $entity->enablement()->associate($detailEntity);

        return $entity;
    }

    public function upsertBulk($oldDetailEntity, Detail\Entity $newDetailEntity, $documents, string $action, $version = 'v1')
    {
        $merchantDetail = $this->merchant->merchantDetail;
        
        (new Validator)->validateExternalPayload($documents, Detail\Constants::ACTION_DRAFT, $merchantDetail, $version);

        $existingDocumentsInExternalFormat = [];

        if (is_null($oldDetailEntity) === false)
        {
            // documents are not marked for delete yet
            $existingDocuments = $oldDetailEntity->documents;

            $existingDocumentsInExternalFormat = $this->convertDocObjectsToExternalFormat($existingDocuments);

            $oldDetailEntity->documents()->delete();
        }

        $documentsInExternalFormat = $this->buildFinalExternalPayload(
            $existingDocumentsInExternalFormat, $documents);

        if ($action === Detail\Constants::ACTION_SUBMIT)
        {
            $documentsInExternalFormat['accepts_intl_txns'] = $newDetailEntity->getAcceptsIntlTxns();

            (new Validator)->validateExternalPayload($documentsInExternalFormat, $action, $merchantDetail, $version);

            unset($documentsInExternalFormat['accepts_intl_txns']);
        }
        else
        {
            (new Validator)->validateExternalPayload($documentsInExternalFormat, $action, $merchantDetail, $version);
        }

        $documentsInInternalFormat = $this->convertExternalToInternalFormat($documentsInExternalFormat);

        foreach ($documentsInInternalFormat as $documentInInternalFormat)
        {
            $newDocEntity = $this->create(
                $documentInInternalFormat, $newDetailEntity, $this->merchant->getId());

            $this->repo->saveOrFail($newDocEntity);
        }
    }

    // documents passed are in external format
    private function buildFinalExternalPayload(array $existingDocuments, $newDocuments): array
    {
        // if newDocuments is null, then existing documents should be flushed
        if (empty($newDocuments) === true)
        {
            return [];
        }

        // fix newDocuments: strip off empty arrays from newDocuments

        if (array_key_exists(Constants::OTHERS, $newDocuments) === true)
        {
            $newCustomTypeDocuments = $newDocuments[Constants::OTHERS];

            if (is_null($newCustomTypeDocuments) === false)
            {
                $newCustomTypeDocuments = array_filter($newCustomTypeDocuments, function($docList){
                    if (is_array($docList) === false) {
                        return false;
                    }

                    return (is_sequential_array($docList) === false) || (count($docList) > 0);
                });

                $newDocuments[Constants::OTHERS] = $newCustomTypeDocuments;
            }
        }

        $newDocuments = array_filter($newDocuments, function($docList){
            if (is_array($docList) === false) {
                return false;
            }

            return (is_sequential_array($docList) === false) || (count($docList) > 0);
        });

        // replace basic and custom types if applicable
        if (array_key_exists(Constants::OTHERS, $newDocuments) === true)
        {
            $newCustomTypeDocuments = $newDocuments[Constants::OTHERS];

            unset($newDocuments[Constants::OTHERS]);

            $newDocuments = array_replace($existingDocuments, $newDocuments);

            if ((is_null($newCustomTypeDocuments) === false) &&
                (array_key_exists(Constants::OTHERS, $existingDocuments) === true))
            {
                $newDocuments[Constants::OTHERS] = array_replace(
                    $existingDocuments[Constants::OTHERS], $newCustomTypeDocuments);
            }
            else
            {
                $newDocuments[Constants::OTHERS] = $newCustomTypeDocuments;
            }
        }
        else
        {
            $newDocuments = array_replace($existingDocuments, $newDocuments);
        }

        return $newDocuments;
    }

    private function convertExternalToInternalFormat($documents): array
    {
        $customTypeDocuments = $documents[Constants::OTHERS] ?? [];

        // stripping to have only basic type documents
        unset($documents[Constants::OTHERS]);

        $internalFormattedBasicTypeDocuments = $this->convertExternalToInternalFormatEx($documents, false);

        $internalFormattedCustomTypeDocuments =  $this->convertExternalToInternalFormatEx($customTypeDocuments, true);

        return array_merge($internalFormattedBasicTypeDocuments, $internalFormattedCustomTypeDocuments);
    }

    private function convertExternalToInternalFormatEx($documents, $isCustomType): array
    {
        $internalFormat = [];

        foreach ($documents as $docType => $docArray)
        {
            if (empty($docArray) === true)
            {
                continue;
            }

            foreach ($docArray as $docItem)
            {
                if ($isCustomType === true)
                {
                    $internalFormat[] = [
                        Entity::TYPE         => Constants::OTHERS,
                        Entity::CUSTOM_TYPE  => $docType,
                        Entity::DOCUMENT_ID  => $docItem[Entity::ID],
                        Entity::DISPLAY_NAME => $docItem[Entity::DISPLAY_NAME],
                    ];
                }
                else
                {
                    $internalFormat[] = [
                        Entity::TYPE         => $docType,
                        Entity::DOCUMENT_ID  => $docItem[Entity::ID],
                        Entity::DISPLAY_NAME => $docItem[Entity::DISPLAY_NAME],
                    ];
                }
            }
        }

        return $internalFormat;
    }

    public function convertDocObjectsToExternalFormat($documents): array
    {
        $externalFormat = [];

        if (empty($documents) === true)
        {
            return $externalFormat;
        }

        foreach ($documents as $document)
        {
            $docId = $document->getPublicDocumentId();

            $docType = $document->getType();

            $docCustomType = $document->getCustomType();

            $docDisplayName = $document->getDisplayName();

            if ($document->isCustomType() === true)
            {
                $externalFormat[$docType][$docCustomType][] = [
                    Entity::ID           => $docId,
                    Entity::DISPLAY_NAME => $docDisplayName,
                ];
            }
            else
            {
                $externalFormat[$docType][] = [
                    Entity::ID           => $docId,
                    Entity::DISPLAY_NAME => $docDisplayName,
                ];
            }
        }

        return $externalFormat;
    }
}
