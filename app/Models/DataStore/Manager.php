<?php

namespace RZP\Models\DataStore;

use RZP\Exception;

class Manager
{
    /**
     * Saves given store object data to data store and throws exception if any
     *
     * @param  Base $store object to save
     *
     * @return Base saved store object
     */
    public function saveOrFail(Base $store)
    {
        return $store->saveOrFail();
    }

    /**
     * Fetches data from store for given object
     */
    public function fetch(Base $store)
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
    public function fetchOrFail(Base $store)
    {
        return $store->fetch();
    }

    /**
     * Deletes store object data from store
     */
    public function delete(Base $store)
    {
        return $store->delete();
    }
}
