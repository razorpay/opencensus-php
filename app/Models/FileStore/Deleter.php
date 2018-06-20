<?php

namespace RZP\Models\FileStore;

use Config;
use RZP\Exception;
use RZP\Encryption;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FileStore\Formatter;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Deleter extends Base\Core
{
    const DEFAULT_STORE    = 's3';

    protected $storageHandler;

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

    protected $type;

    protected $file;

    public function __construct()
    {
        parent::__construct();

        $this->auth = $this->app['basicauth'];
        $this->setDefaults();
    }

    public function setDefaults()
    {
        $this->store(self::DEFAULT_STORE);
    }

    public function store(string $store)
    {
        $this->storageHandler = Store::getHandler($store);

        return $this;
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
     */
    public function type($type)
    {
        $this->type = $type;
        $this->params[Entity::TYPE] = $type;

        return $this;
    }

    public function file($ufh)
    {
        $this->file = $ufh;
        return $this;
    }

    protected function getFullFileName()
    {
        $extension = $this->file->getExtension();

        $fileName  = $this->file->getName();

        if ($extension !== null)
        {
            $fileName .= ('.' . $extension);
        }

        return $fileName;
    }

    public function delete()
    {
        $bucketConfig = $this->storageHandler->getBucketConfig($this->type, $this->env);
        $this->storageHandler->delete($bucketConfig, $this->getFullFileName());

        $this->file->setDeletedAt();
        $this->repo->saveOrFail($this->file);
    }
}
