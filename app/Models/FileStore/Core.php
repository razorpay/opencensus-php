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
        $entityObj = $this->repo->$entity->findByIdAndMerchantId($entityId, $merchantId);

        $file = $entityObj->file;

        $file->getValidator()->validateRequestEntity($entity);

        $signedUrl = (new Accessor)->getSignedUrlOfFile($file);

        return $signedUrl;
    }
}
