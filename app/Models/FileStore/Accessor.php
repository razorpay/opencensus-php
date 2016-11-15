<?php

namespace RZP\Models\FileStore;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant\Account;
use RZP\Models\FileStore\Formatter;

class Accessor extends Base\Service
{
    /**
     * @var Entity
     */
    protected $id;
    protected $merchantId;
    protected $entityId;
    protected $entityType;
    protected $type;

     protected $entity = 'file_store';

    public function __construct()
    {

        parent::__construct();
    }

    public function id($id)
    {
        $this->id = $id;

        return $this;
    }

    public function entityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function entityType($entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function merchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    public function type($type)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * Returns Array of File Store Values
     * @return array
     */
    public function get()
    {
        if($this->merchantId === null)
        {
            $this->merchantId = Account::SHARED_ACCOUNT;
        }

        $data = $this->repo->file_store->getByParams(
            $this->id,
            $this->merchantId,
            $this->entityId,
            $this->entityType,
            $this->type
        );

        return $data->toArrayPublic();
    }

    /**
     * Returns File Contents
     * @return array
     */
    public function getFile()
    {
        $data = $this->get();

        if ($data['count'] !== 1)
        {
            throw new Exception\LogicException('getFile Can only fetch one file COntents');
        }

        // TODO : fetch the contents instead of location
        return $data['items'][0]['location'];
    }
}
