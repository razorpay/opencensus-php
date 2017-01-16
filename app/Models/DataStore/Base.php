<?php

namespace RZP\Models\DataStore;

use App;

class Base
{
    protected $redis;

    protected $trace;

    protected $keyPrefix;

    protected $key;

    protected $data;

    // Used to construcy key for data store. To be overriden by child classes
    protected static $delimiter = '';

    public function __construct()
    {
        $app = App::getFacadeRoot();

        $this->redis = $app['redis'];

        $this->trace = $app['trace'];
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
