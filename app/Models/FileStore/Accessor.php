<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Models\FileStore\Formatter;

class Accessor extends Base\Service
{
    /**
     * Id of object to fetched
     *
     * @var id
     */
    protected $id;

    /**
     * Merchant id of the file object to be fetched
     *
     * @var merchantId
     */
    protected $merchantId;

    /**
     * Entity id of the file object to be fetched
     *
     * @var entityId
     */
    protected $entityId;

    /**
     * Entity Type of the file object to be fetched
     *
     * @var entityType
     */
    protected $entityType;

    /**
     * File Type of the file object to be fetched
     *
     * @var type
     */
    protected $type;

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
        $this->id = $id;

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
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Set the Entity Type  in Query Param
     *
     * @param string $entityType Entity Type of object to fetch
     *
     * @return Accessor object
     */
    public function entityType(string $entityType)
    {
        $this->entityType = $entityType;

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
     * Set the File Type in Query Param
     *
     * @param string $type File type of object to fetch
     *
     * @return Accessor object
     */
    public function type(string $type)
    {
        $this->type = $type;

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

        $data = $this->repo->file_store->fetchByParams(
            $this->id,
            $this->merchantId,
            $this->entityId,
            $this->entityType,
            $this->type
        );

        return $data->toArrayPublic();
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
    }

    /**
     * Returns File Contents
     *
     * @return string File Conntents
     */
    public function getFile()
    {
        $data = $this->get();

        if ($data['count'] !== 1)
        {
            throw new Exception\LogicException(
                'Multi file fetch not supported');
        }

        // TODO : fetch the contents instead of location
        return $data['items'][0]['location'];
    }
}
