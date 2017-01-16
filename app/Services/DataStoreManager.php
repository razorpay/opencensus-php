<?php

namespace RZP\Services;

use RZP\Models\DataStore;
use RZP\Exception;

class DataStoreManager
{
    protected $trace;

    /**
     * Saves given store object data to data store and throws exception if any
     *
     * @param  DataStore\Base $store object to save
     *
     * @return DataStore\Base saved store object
     */
    public function saveOrFail(DataStore\Base $store)
    {
        return $store->saveOrFail();
    }

    /**
     * Fetches data from store for given object
     */
    public function fetch(DataStore\Base $store)
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
    public function fetchOrFail(DataStore\Base $store)
    {
        return $store->fetch();
    }

    /**
     * Deletes store object data from store
     */
    public function delete(DataStore\Base $store)
    {
        return $store->delete();
    }
}
