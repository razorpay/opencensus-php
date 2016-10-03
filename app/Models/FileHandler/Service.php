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
                    throw new Exception\InvalidArgumentException('Invalid storage service' . $service);
            }

            $this->storageHandler = (new $class);

            $this->serviceProvider = $service;
        }
    }

    public function create($input)
    {
        $filePath = $input['filePath'];

        unset($input['filePath']);

        // TODO : add handler if multiple service provider are added in future
        $this->getStorageHandle('s3');

        if (in_array('mime', $input) === false)
        {
            $input['mime'] = (new Helper)->getMimeType($filePath);
        }

        //TODO : choose proper bucket depending on entity type
        $bucket = 'rzp-test-bucket';

        $url = $this->storageHandler->save($bucket, 'name', $filePath, $input['mime'], []);

        $input['url'] = $url;

        //TODO add prcoessing for other fields like entity id name and document_type
        $fileHandler = $this->core->create($input);

        if (in_array($input, 'expiryTime') === true)
        {
            $fileHandler->url = $this->storageHandler->getTemporaryUrl(
                $bucket,
                $fileHandler->url,
                $input['expiryTime']
            );
        }

        return $fileHandler->toArrayPublic();
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
