<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Exception\ServerNotFoundException;

class Service extends Base\Service
{
    /**
     * Fetches single activation file signed url
     * by given file store id
     *
     * @param string $fileId
     *
     * @return string (url)
     * @throws Exception\BadRequestException
     */
    public function fetchFileSignedUrlById(string $fileId)
    {
        try
        {
            $signedUrl = $this->app->batchService->downloadS3UrlForBatchOrFileStore($fileId, 'files');
        }
        catch (ServerNotFoundException $exception)
        {
            // Either Batch Microservice is down or not found
            // check in DB.
            $file = $this->repo->file_store->findByPublicId($fileId);

            $signedUrl = (new Accessor)->getSignedUrlOfFile($file);
        }


        return $signedUrl;
    }

    /**
     * Fetches signed url, given entity and entity id,
     * for the file assosciated with entity
     *
     * @param string $entity
     * @param string $entityId
     *
     * @return string $signedUrl
     * @throws Exception\BadRequestException
     */
    public function fetchSignedUrlForEntityFile(string $entity, string $entityId)
    {
        return (new Core)->signedUrlForEntityFile($entity, $entityId);
    }

    public function updateFileBucketAndRegion(array $input)
    {
        return (new Core)->migrateInvoiceBuckets($input);
    }
}
