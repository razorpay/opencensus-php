<?php

namespace RZP\Services;

use RZP\Models\Store;
use RZP\Exception;

class StoreManager
{
    protected $trace;

    /**
     * Saves given store object data to data store and throws exception if any
     *
     * @param  Store\Base $store object to save
     *
     * @return Store\Base saved store object
     */
    public function saveOrFail(Store\Base $store)
    {
        return $store->saveOrFail();
    }

    /**
     * Fetches data from store for given object
     */
    public function fetch(Store\Base $store)
    {
        try
        {
            $store = $store->fetch();
        }
        catch(Exception\ServerErrorException $e)
        {
            // Suppress any storage exception and set data as null
            $store->setData(null);
        }

        return $store;
    }

    /**
     * Similar to fetch method but throws any exception instead of suppressing
     */
    public function fetchOrFail(Store\Base $store)
    {
        return $store->fetch();
    }

    /**
     * Deletes store object data from store
     */
    public function delete(Store\Base $store)
    {
        return $store->delete();
    }
}
