<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Core extends Base\Core
{
    /**
     * Fetches signed url for file assosciated with any entity
     *
     * @param $entity       string
     * @param $entityId     string
     * @return $signedUrl   string
     */
    public function signedUrlForEntityFile(string $entity, string $entityId)
    {
        (new Validator)->validateInput('entity_fetch', ['entity' => $entity, 'entity_id' => $entityId]);

        $entityObj = $this->repo->$entity->findByIdAndMerchantId($entityId, $this->merchant->getId());

        $file = $entityObj->file;

        $signedUrl = (new Accessor)->getSignedUrlOfFile($file);

        $data = [
            'file_id'    => $file->getId(),
            'signed_url' => $signedUrl,
        ];

        return $data;
    }

    public function getSignedUrl(string $fileStoreId, string $merchantId)
    {
        $signedUrls = (new Accessor)->id($fileStoreId)
                               ->merchantId($merchantId)
                               ->getSignedUrl();

        return $signedUrls[$fileStoreId];
    }
}
