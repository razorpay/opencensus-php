<?php

namespace RZP\Models\FileHandler;

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
        $class = 'RZP\Models\FileHandler\StorageService\\';

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

        $fileHandlerInput = [];

        $fileHandlerInput[Entity::SERVICE] = $this->serviceProvider;

        $fileHandlerInput[Entity::BUCKET] = $input['bucket'];

        $fileHandlerInput = array_merge($fileHandlerInput, $this->getEntityData($input));

        $fileHandlerInput = array_merge($fileHandlerInput, $fileDetails);

        $fileHandler = $this->core->create($fileHandlerInput);

        $signedUrlFlag = @$input['signedUrl'] ?: false;

        if ($signedUrlFlag === true)
        {
            $expiryTime = @$input['expiryTime'] ?: '0';

            if ((int)$expiryTime <= 0)
            {
                throw new Exception\InvalidArgumentException('Give valid expiry time');
            }

            $fileHandler->url = $this->updateUrl($input['bucket'], $expiryTime, $fileDetails[Entity::LOCATION]);
        }

        return $fileHandler->toArrayPublic();
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

        $fileHandler = $this->repo->file_handler->getByIdOrFail($id);

        if ($signedUrlFlag === true)
        {
            $fileHandler[Entity::LOCATION] = $this->updateUrl(
                $fileHandler[Entity::BUCKET],
                $expiryTime,
                $fileHandler[Entity::LOCATION]);
        }

        return $fileHandler->toArrayPublic();
    }

    public function fetchContent($id)
    {
        Entity::verifyIdAndStripSign($id);

        $fileHandler = $this->repo->file_handler->getByIdOrFail($id);

        return $this->storageHandler->fetch(
            $fileHandler[Entity::BUCKET],
            $fileHandler[Entity::NAME]);
    }

    public function fetchAndSaveFile($id, $filePath)
    {
        Entity::verifyIdAndStripSign($id);

        $fileHandler = $this->repo->file_handler->getByIdOrFail($id);

        $fileHandler[Entity::LOCATION] = $this->storageHandler->fetchAndSaveFile(
            $fileHandler[Entity::BUCKET],
            $fileHandler[Entity::NAME],
            $filePath);

        return $fileHandlers->toArrayPublic();
    }

    public function fetchByEntityIdAndType($entityId, $entityType, $signedUrlFlag = true, $expiryTime = '15')
    {
        $fileHandlers = $this->repo->file_handler->getByEntityIdAndEntityType(
            $entityId,
            $entityType);
        foreach ($fileHandlers as $fileHandler)
        {
            if ($signedUrlFlag === true)
            {
                $fileHandler[Entity::LOCATION] = $this->updateUrl(
                    $fileHandler[Entity::BUCKET],
                    $expiryTime,
                    $fileHandler[Entity::LOCATION]);
            }
        }

        return $fileHandlers->toArrayPublic();
    }

    public function search($input)
    {
        //TODO :: Implement it when nothing else is left
    }

    public function update($id, $input)
    {
        $fileHandler = $this->repo->file_handler->findByPublicId($id);

        $newFileHandler = $this->create($input);

        $fileHandler->delete();

        return $newFileHandler;
    }

    public function delete($id)
    {
        $fileHandler = $this->repo->file_handler->findByPublicId($id);

        $fileHandler->delete();

        return $fileHandler->toArrayDeleted();
    }

    public function deleteByEntityIdAndEntityType($id)
    {
        $fileHandlers = $this->repo->file_handler->getByEntityIdAndEntityType(
            $entityId,
            $entityType);

        foreach ($fileHandlers as $fileHandler)
        {
            $fileHandler->delete();
        }

        //TODO : change the return type
        return $fileHandler->toArrayPublic();
    }

    protected function getEntityData($input)
    {
        $fileHandlerInput = [];

        $entityType = @$input['entityType'] ?: '';

        $entityId = @$input['entityId'] ?: '';

        $merchantId = @$input['merchantId'] ?: '';

        if (in_array($entityType, EntityTypeConstants::getValidEntity()) === true)
        {
            $fileHandlerInput[Entity::ENTITY_TYPE] = $entityType;

            $fileHandlerInput[Entity::ENTITY_ID] = $entityId;
        }

        $fileHandlerInput[Entity::MERCHANT_ID] = $merchantId;

        $documentType = $this->getDocumentType($entityType, $entityId);

        $fileHandlerInput[Entity::DOCUMENT_TYPE] = $documentType;

        return $fileHandlerInput;
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
