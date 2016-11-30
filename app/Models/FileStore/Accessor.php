<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;

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

    /**
     * Set the Id in Query Param
     *
     * @param string $id ID of object to fetch
     *
     * @return Accessor object
     */
    public function id(string $id)
    {
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
     * Returns Array of File Store Values
     *
     * @return array
     */
    public function get()
    {
        $data = $this->getEntity();

        return $data->toArrayPublic();
    }

    protected function getEntity()
    {
        $this->updateMerchantId();

        if ($this->id !== null)
        {
            $data = new Base\PublicCollection;

            $data->push($this->repo->file_store->findByIdAndMerchantId($this->id, $this->merchantId));
        }
        else
        {
            $data = $this->repo->file_store->fetch($this->params, $this->merchantId);
        }

        return $data;
    }

    /**
     * Returns File Contents
     *
     * @return string File Contents
     * @throws Exception\LogicException
     */
    public function getFile()
    {
        $data = $this->getEntity();

        $this->validateFileCount($data);

        $data = $data->first();

        $storageHandler = Store::getHandler($data->store);

        $filePath = $this->createFullFilePath($data->location);

        $storageHandler->saveAs($data->bucket, $data->location, $filePath);

        return $filePath;
    }

    protected function createFullFilePath(string $location)
    {
        return $this->getStorageDir() . $location;
    }

    protected function getStorageDir()
    {
        return storage_path(Store::STORAGE_DIRECTORY);
    }

    /**
     * Throws Exception if Invalid No of files are found
     *
     * @param Base\PublicCollection
     *
     * @return void
     * @throws Exception\LogicException
     */
    protected function validateFileCount(Base\PublicCollection $data)
    {
        // TODO : Make it more meaningful
        if ($data->count() === 0)
        {
            throw new Exception\LogicException('No file found');
        }

        if ($data->count() > 1)
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
        if ($this->merchant !== null)
        {
            $merchant = $this->merchant;
        }
        else
        {
            $merchant = $this->repo->merchant->getSharedAccount();
        }

        $this->merchantId($merchant->getId());
    }
}
