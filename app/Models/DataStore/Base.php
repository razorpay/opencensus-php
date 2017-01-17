<?php

namespace RZP\Models\DataStore;

use RZP\Models\Base\Core;

class Base extends Core
{
    protected $redis;

    protected $keyPrefix;

    protected $key;

    protected $data;

    /**
     * Used to construct key for data store.
     * To be overridden by child classes
     * @var string
     */
    protected static $delimiter = '';

    protected function init()
    {
        $this->redis = $app['redis'];
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getKeyPrefix()
    {
        return $this->keyPrefix;
    }

    public function generateStoreKey()
    {
        return $this->keyPrefix . static::$delimiter . $this->key;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function toArray()
    {
        return [
            $this->key => $this->data
        ];
    }

}
