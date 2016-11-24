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

    const DEFAULT_MERCHANT_ID = Account::SHARED_ACCOUNT;

    /**
     * Set the Id in Query Param
     *
     * @param string $id ID of object to fetch
     *
     * @return Accessor object
     */
    public function id(string $id)
    {
        $this->params[Entity::ID] = $id;

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
        $this->params[Entity::MERCHANT_ID] = $merchantId;

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
        $this->updateMerchantId();

        $data = $this->repo->file_store->fetch($this->params);

        return $data->toArrayPublic();
    }

    /**
     * Returns File Contents
     *
     * @return string File Contents
     * @throws Exception\LogicException
     */
    public function getFile()
    {
        $data = $this->get();

        if ($data['count'] !== 1)
        {
            throw new Exception\LogicException('Multi file fetch not supported');
        }

        $store = $data['store'];

        $storageHandler = Store::getHandler($store);

        return $storageHandler->read($data['bucket'], $data['name']);
    }

    /**
     * Updates Merchant Id for non-admin calls
     *
     * @return void
     */
    protected function updateMerchantId()
    {
        $merchant = $this->app['basicauth']->merchant;

        // Update Merchant ID, if request is done by non-admin
        if ($merchant !== null)
        {
            $this->merchantId($merchant->getId());
        }

        if (isset($this->params[Entity::MERCHANT_ID]) === false)
        {
            $this->merchantId(self::DEFAULT_MERCHANT_ID);
        }
    }
}
