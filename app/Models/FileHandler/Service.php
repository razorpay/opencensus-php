<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $storageHandler;

    protected $serviceProvider;

    protected $core;

    public function __construct()
    {
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

        // TODO : add handler if multiple service provider are added in future
        $this->getStorageHandle('s3');

        if (in_array('mime', $input) === false)
        {
            $input['mime'] = $this->helper->getMimeType($filePath);
        }

        //TODO : choose proper bucket depending on entity type
        $bucket = 'rzp-test-bucket';

        $input['url'] = $this->storageHandler->save($bucket, 'name', $filePath, $input['mime'], []);

        $fileHandlerInput = $this->getFileHandlerData($input);

        $fileHandler = $this->core->create($fileHandlerInput);

        if (in_array('expiryTime', $input) === true)
        {
            $fileHandler->url = $this->storageHandler->getTemporaryUrl(
                $bucket,
                $fileHandler->url,
                $input['expiryTime']
            );
        }

        return $fileHandler->toArrayPublic();
    }

    protected function getFileHandlerData($input)
    {
        //TODO : this is for testing only, refactor and make it better
        $fileHandlerInput = [];

        $size = $this->helper->getFileSize($input['filePath']);

        $password = (isset($input['password']) === true) ? $input['password'] : '';

        $encryptionMethod = 'none';

        $service = 's3';

        $entityName = (isset($input['entityName']) === true) ? $input['entityName'] : '';
        $entityId = (isset($input['entityId']) === true) ? $input['entityId'] : '';
        $merchantId = (isset($input['merchantId']) === true) ? $input['merchantId'] : '';
        $bucket = '';
        $permission = '';
        $metaData = '';
        $comments = '';
        $documentType = '';

        $fileHandlerInput[Entity::FORMAT] = $input['mime'];

        $fileHandlerInput[Entity::SIZE] = $size;

        $fileHandlerInput[Entity::ENCRYPTION_METHOD] = $encryptionMethod;
        $fileHandlerInput[Entity::LOCATION] = $input['url'];
        $fileHandlerInput[Entity::SERVICE] = $service;
        $fileHandlerInput[Entity::BUCKET] = $bucket;

        $fileHandlerInput[Entity::NAME] = $input['filePath'];

        $fileHandlerInput[Entity::PASSWORD] = $password;

        $fileHandlerInput[Entity::ENTITY_NAME] = $entityName;
        $fileHandlerInput[Entity::ENTITY_ID] = $entityId;
        $fileHandlerInput[Entity::MERCHANT_ID] = $merchantId;
        $fileHandlerInput[Entity::PERMISSION] = $permission ;
        $fileHandlerInput[Entity::METADATA] = $metaData;
        $fileHandlerInput[Entity::COMMENTS] = $comments;
        $fileHandlerInput[Entity::DOCUMENT_TYPE] = $documentType;

        return $fileHandlerInput;
    }

    public function fetch($id)
    {

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
}
