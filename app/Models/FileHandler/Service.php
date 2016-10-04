<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Service extends Base\Service
{
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
            $expiryTime = @$input['expiryTime'] ?: 0;

            if ($expiryTime <= 0)
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

    }

    public function fetchByEntityIdAndType($id)
    {

    }

    public function search($input)
    {

    }

    public function update($id, $input)
    {

    }

    public function delete($id)
    {

    }

    public function deleteByEntityIdAndEntityType($id)
    {

    }

    protected function getEntityData($input)
    {
        $fileHandlerInput = [];

        $entityName = @$input['entityName'] ?: '';

        $entityId = @$input['entityId'] ?: '';

        $merchantId = @$input['merchantId'] ?: '';

        $fileHandlerInput[Entity::ENTITY_NAME] = $entityName;

        $fileHandlerInput[Entity::ENTITY_ID] = $entityId;

        $fileHandlerInput[Entity::MERCHANT_ID] = $merchantId;

        $documentType = $this->getDocumentType($entityName, $entityId);

        $fileHandlerInput[Entity::DOCUMENT_TYPE] = $documentType;

        return $fileHandlerInput;
    }

    protected function getDocumentType($name, $id)
    {
        return $name . ':' . $id;
    }
}
