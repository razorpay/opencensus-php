<?php

namespace RZP\Models\Merchant\Document;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Detail;

class Core extends Base\Core
{
    /**
     * @param Entity $document
     *
     * @return mixed
     */
    public function delete(Entity $document)
    {
        $this->trace->info(TraceCode::DOCUMENT_DELETE_REQUEST, ['id' => $document->getId()]);

        return $this->repo->deleteOrFail($document);
    }

    /**
     * this function creates or edit a new document with params documentType and fileStoreId
     * @param Merchant\Entity $merchant
     * @param array           $params
     * @param Entity|null     $document
     */
    public function storeInMerchantDocument(Merchant\Entity $merchant, array $params, Entity $document = null)
    {
        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $params]);

        foreach ($params as $documentType => $fileStoreId)
        {
            $input = [
                Entity::FILE_STORE_ID => $fileStoreId,
                Entity::DOCUMENT_TYPE => $documentType,
            ];

            $document = $document ?? (new Entity)->generateId();

            $document->edit($input);

            $document->merchant()->associate($merchant);

            $document->setEntityType();

            $this->repo->saveOrFail($document);
        }
    }

    /**
     * this function store activation file in merchantDocument by new route.
     *
     * @param Merchant\Entity $merchant
     * @param array           $input
     * @param bool            $validateLock
     *
     * @return array
     */
    public function uploadActivationFile(Merchant\Entity $merchant, array $input, bool $validateLock = true)
    {
        $this->trace->info(TraceCode::DOCUMENT_CREATE_REQUEST, ['input' => $input]);

        $merchantDetails = (new Detail\Core())->getMerchantDetails($merchant);

        if ($validateLock === true)
        {
            $merchantDetails->getValidator()->validateIsNotLocked();
        }

        $document = (new Entity)->generateId()->build($input);

        $document->merchant()->associate($merchant);

        $document->setEntityType();

        $this->repo->transaction(function() use ($document, $merchant, $input)
        {
            $this->repo->saveOrFail($document);

            $param = [
                $input[Entity::DOCUMENT_TYPE] => $input[Entity::FILE]
            ];

            $param = (new Detail\Service())->storeActivationFile($document, $param);

            $this->storeInMerchantDocument($merchant, $param, $document);
        });

        return $document->toArrayPublic();
    }

    /**
     * this function takes array of fileStoreIds and delete them.
     *
     * @param array $fileStoreIds
     */
    public function deleteDocuments(array $fileStoreIds)
    {
        foreach ($fileStoreIds as $fileStoreId)
        {
            $document = $this->repo->merchant_document->findDocumentByFileStoreId($fileStoreId);

            if (isset($document) === true)
            {
                $this->delete($document);
            }
        }
    }
}
