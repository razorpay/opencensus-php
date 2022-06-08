<?php

namespace RZP\Models\FileStore;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore\Store;
use RZP\Models\Merchant\Account;
use RZP\Trace\TraceCode;

class Accessor extends Base\Core
{
    /**
     * Param Dictionary for doing Db Query
     */
    protected $params = [];

    /**
     * Id for which entity has to be fetched
     */
    protected $id = null;

    protected $merchantId;

    protected $auth;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];
    }

    /**
     * Set the Id in Query Param
     *
     * @param string $id ID of object to fetch
     *
     * @return Accessor object
     */
    public function id(string $id)
    {
        Entity::verifyIdAndSilentlyStripSign($id);

        $this->id = $id;

        return $this;
    }

    /**
     * Set the Merchant Id in Query Param
     *
     * @param string $merchantId Merchant ID of object to fetch
     *
     * @return Accessor object
     */
    public function merchantId(string $merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Set the Entity Id in Query Param
     *
     * @param string $entityId Entity ID of object to fetch
     *
     * @return Accessor object
     */
    public function entityId(string $entityId)
    {
        $this->params[Entity::ENTITY_ID] = $entityId;

        return $this;
    }

    /**
     * Set the File Type in Query Param
     *
     * @param string $type File type of object to fetch
     *
     * @return Accessor object
     */
    public function type(string $type)
    {
        $this->params[Entity::TYPE] = $type;

        return $this;
    }

    /**
     * @return Base\PublicCollection|Base\PublicEntity
     */
    public function get()
    {
        $this->updateMerchantId();

        if ($this->id !== null)
        {
            return $this->repo->file_store->findByIdAndMerchantId($this->id, $this->merchantId);
        }
        else
        {
            return $this->repo->file_store->fetch($this->params, $this->merchantId);
        }
    }

    /**
     * Returns File Contents
     *
     * @return string File Contents
     * @throws Exception\LogicException
     */
    public function getFile()
    {
        $file = $this->get();

        if ($file instanceof Base\PublicCollection)
        {
            $this->validateFileCount($file);

            $file = $file->first();
        }

        $storageHandler = Store::getHandler($file->getStore());

        $filePath = $this->createFullFilePath($file->getLocation());

        $dir = dirname($filePath);

        if (file_exists($dir) === false)
        {
            (new Utility)->callFileOperation('mkdir', [$dir, 0777, true]);
        }

        $bucketConfig = [
            'name'   => $file->getBucket(),
            'region' => $file->getRegion(),
        ];

        $storageHandler->saveAs($bucketConfig, $file->getLocation(), $filePath);

        return $filePath;
    }

    /**
     * Get Signed URL for single and/or multiple file entities
     *
     * @return array array with entity id as key and signed url as value
     */
    public function getSignedUrl()
    {
        $urls = [];

        $files = $this->get();

        if (($files instanceof Base\PublicCollection) === false)
        {
            $files = (new Base\PublicCollection)->push($files);
        }

        foreach ($files as $file)
        {
            $urls[$file->getId()] = $this->getUrl($file);
        }

        return $urls;
    }

    /**
     * @param Entity      $file
     * @param string|null $downloadAs - For not null values returns signed url
     *                                  which on following forces download with
     *                                  Content-disposition headers.
     *
     * @return string
     */
    public function getSignedUrlOfFile(
        Entity $file,
        string $downloadAs = null): string
    {
        $signedUrl = $this->getUrl($file, $downloadAs);

        if ($this->app->environment('dev', 'testing') === true)
        {
            $signedUrl = $this->getFullFilePath($file);
        }

        return $signedUrl;
    }

    public function getFullFilePath(Entity $file): string
    {
        return $this->getStorageDir() . $file->getName() . '.' . $file->getExtension();
    }

    /**
     * Get Signed URL for single file entity
     *
     * @param Entity      $fileStore
     * @param string|null $downloadAs - Refer getSignedUrlOfFile()
     *
     * @return string signed url
     */
    protected function getUrl(
        Entity $fileStore,
        string $downloadAs = null): string
    {
        $storageHandler = Store::getHandler($fileStore->getStore());

        $bucketConfig = [
            'name'   => $fileStore->getBucket(),
            'region' => $fileStore->getRegion(),
        ];

        // Additional parameters
        $params = [
            'downloadAs' => $downloadAs,
        ];

        $url = $storageHandler->getSignedUrl(
                                    $bucketConfig,
                                    $fileStore->getLocation(),
                                    '15',
                                    $params);

        return $url;
    }

    /**
     * @param string $location location
     *
     * @return string full path url
     */
    protected function createFullFilePath(string $location)
    {
        return $this->getStorageDir() . $location;
    }

    /**
     * @return string storage directory
     */
    protected function getStorageDir()
    {
        return storage_path(Store::STORAGE_DIRECTORY);
    }

    /**
     * Throws Exception if Invalid No of files are found
     *
     * @param Base\PublicCollection $files files
     *
     * @return void
     * @throws Exception\LogicException
     */
    protected function validateFileCount(Base\PublicCollection $files)
    {
        // TODO : Make it more meaningful
        if ($files->count() === 0)
        {
            throw new Exception\LogicException('No file found');
        }

        if ($files->count() > 1)
        {
            throw new Exception\LogicException('Multi file fetch not supported');
        }
    }

    /**
     * Updates Merchant Id
     *
     * @return void
     */
    protected function updateMerchantId()
    {
        if ($this->merchantId === null)
        {
            $this->merchantId(Account::SHARED_ACCOUNT);
        }
    }

    /**
     * Updates bucket name and region for file Id as
     * invoice bucket is migrated to ap-south-1
     *
     * @param Entity
     * @param array
     */

    public function updateBucketNameAndRegion(Entity $file,array $bucketConfig)
    {
        //update bucket name and region
        if($file->getBucket() !== $bucketConfig['name'] || $file->getRegion() !== $bucketConfig['region']){

            $file->bucket = $bucketConfig['name'];
            $file->region = $bucketConfig['region'];

            $this->repo->saveOrFail($file);
        }

        $this->trace->info(TraceCode::UPDATED_BUCKET_CONFIG,[
            'file_id'  => $file->getId(),
            'response' => $file->bucket,
            'region'   => $file->region,
        ]);
    }
}
