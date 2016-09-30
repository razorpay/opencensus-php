<?php

namespace RZP\Models\FileHandler;

use RZP\Models\Base;

class Service extends Base\Service
{
    protected $storageHandler;

    protected $serviceProvider;

    public function __construct()
    {

    }

    protected function getStorageHandle($service)
    {
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
        $file = $input['file'];

        unset($input['file']);

        // TODO : add handler if multiple service provider are added in future
        $this->getStorageHandle('s3');

        if (in_array($input, 'mime') === false)
        {
            $input['mime'] = (new Helper)->getMimeType($file);
        }

        //TODO : choose proper bucket depending on entity type
        $bucket = 'rzp-test-bucket';

        $url = $this->storageHandler->save($bucket, 'name', $file, $fileMime, []);

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
