<?php

namespace RZP\Models\DataStore;

use RZP\Models\Base\Core;

class Base extends Core
{
    protected $redis;

    /**
     * Stores the prefix with which the keys of a particular
     * store object are generated. Defined by child classes
     * @var string
     */
    protected $prefix;

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
        $this->redis = $this->app['redis'];
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function generateStoreKey()
    {
        return $this->prefix . static::$delimiter . $this->key;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
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
