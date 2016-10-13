<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Base\Service
{
    use SoftDeletes;

    const DEFAULT_SERVICE   = 's3';

    protected $storageHandler;

    protected $serviceProvider;

    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core;

        $this->helper = new Helper;
    }

    protected function getStorageHandle($service)
    {
        $class = 'RZP\Models\FileStore\StorageService\\';

        if (is_null($this->serviceProvider) !== $service)
        {
            switch ($service)
            {
                // TODO : change to constants
                case 's3':
                    $class  = $class . 'AwsS3' . '\Handler';
                    break;

                case 'default':
                    throw new Exception\InvalidArgumentException('Invalid storage service ' . $service);
            }

            $this->storageHandler = (new $class);

            $this->serviceProvider = $service;
        }
    }

    public function create($input)
    {
        $filePath = $input['filePath'];

        $handler = @$input['handler'] ?: self::DEFAULT_SERVICE;

        $this->getStorageHandle($handler);

        $input['bucket'] = $this->storageHandler->getBucketName('default');

        $fileDetails = $this->helper->getFileDetails($filePath, $input);

        $fileDetails[Entity::LOCATION] = $this->storageHandler->save(
            $input['bucket'],
            $fileDetails[Entity::NAME],
            $filePath,
            $fileDetails[Entity::FORMAT],
            []
        );

        $fileStoreInput = [];

        $fileStoreInput[Entity::SERVICE] = $this->serviceProvider;

        $fileStoreInput[Entity::BUCKET] = $input['bucket'];

        $fileStoreInput = array_merge($fileStoreInput, $this->getEntityData($input));

        $fileStoreInput = array_merge($fileStoreInput, $fileDetails);

        $fileStore = $this->core->create($fileStoreInput);

        $signedUrlFlag = @$input['signedUrl'] ?: false;

        if ($signedUrlFlag === true)
        {
            $expiryTime = @$input['expiryTime'] ?: '0';

            if ((int)$expiryTime <= 0)
            {
                throw new Exception\InvalidArgumentException('Give valid expiry time');
            }

            $fileStore->url = $this->updateUrl($input['bucket'], $expiryTime, $fileDetails[Entity::LOCATION]);
        }

        return $fileStore->toArrayPublic();
    }

    protected function updateUrl($bucket, $expiryTime, $url)
    {
        $signedUrl = $this->storageHandler->getTemporaryUrl(
            $bucket,
            $url,
            $expiryTime
        );

        return $signedUrl;
    }

    public function fetch($id, $signedUrlFlag = true, $expiryTime = '15')
    {
        Entity::verifyIdAndStripSign($id);

        $fileStore = $this->repo->filestore->getByIdOrFail($id);

        if ($signedUrlFlag === true)
        {
            $fileStore[Entity::LOCATION] = $this->updateUrl(
                $fileStore[Entity::BUCKET],
                $expiryTime,
                $fileStore[Entity::LOCATION]);
        }

        return $fileStore->toArrayPublic();
    }

    public function fetchContent($id)
    {
        Entity::verifyIdAndStripSign($id);

        $fileStore = $this->repo->filestore->getByIdOrFail($id);

        return $this->storageHandler->fetch(
            $fileStore[Entity::BUCKET],
            $fileStore[Entity::NAME]);
    }

    public function fetchAndSaveFile($id, $filePath)
    {
        Entity::verifyIdAndStripSign($id);

        $fileStore = $this->repo->filestore->getByIdOrFail($id);

        $fileStore[Entity::LOCATION] = $this->storageHandler->fetchAndSaveFile(
            $fileStore[Entity::BUCKET],
            $fileStore[Entity::NAME],
            $filePath);

        return $fileStores->toArrayPublic();
    }

    public function fetchByEntityIdAndType($entityId, $entityType, $signedUrlFlag = true, $expiryTime = '15')
    {
        $fileStores = $this->repo->filestore->getByEntityIdAndEntityType(
            $entityId,
            $entityType);
        foreach ($fileStores as $fileStore)
        {
            if ($signedUrlFlag === true)
            {
                $fileStore[Entity::LOCATION] = $this->updateUrl(
                    $fileStore[Entity::BUCKET],
                    $expiryTime,
                    $fileStore[Entity::LOCATION]);
            }
        }

        return $fileStores->toArrayPublic();
    }

    public function search($input)
    {
        //TODO :: Implement it when nothing else is left
    }

    public function update($id, $input)
    {
        $fileStore = $this->repo->filestore->findByPublicId($id);

        $newFileStore = $this->create($input);

        $fileStore->delete();

        return $newFileStore;
    }

    public function delete($id)
    {
        $fileStore = $this->repo->filestore->findByPublicId($id);

        $fileStore->delete();

        return $fileStore->toArrayDeleted();
    }

    public function deleteByEntityIdAndEntityType($id)
    {
        $fileStores = $this->repo->filestore->getByEntityIdAndEntityType(
            $entityId, $entityType);

        foreach ($fileStores as $fileStore)
        {
            $fileStore->delete();
        }

        return $fileStore->toArrayDeleted();
    }

    protected function getEntityData($input)
    {
        $fileStoreInput = [];

        $entityType = @$input['entityType'] ?: '';

        $entityId = @$input['entityId'] ?: '';

        $merchantId = @$input['merchantId'] ?: '';

        if (in_array($entityType, EntityTypeConstants::getValidEntity()) === true)
        {
            $fileStoreInput[Entity::ENTITY_TYPE] = $entityType;

            $fileStoreInput[Entity::ENTITY_ID] = $entityId;
        }

        $fileStoreInput[Entity::MERCHANT_ID] = $merchantId;

        $documentType = $this->getDocumentType($entityType, $entityId);

        $fileStoreInput[Entity::DOCUMENT_TYPE] = $documentType;

        return $fileStoreInput;
    }

    protected function getDocumentType($name, $id)
    {
        $documentType = $name;

        if (isset($id) === true)
        {
            $documentType = $documentType . ':' . $id;
        }

        return $documentType;
    }
}
