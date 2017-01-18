<?php

namespace RZP\Models\DataStore;

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base\Core;

class Manager extends Core
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
        try
        {
            $store = $store->save();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error saving to redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $store->toArray());
        }

        return $store;
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
        catch(\Exception $e)
        {
            $this->trace->traceException($e);

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
        try
        {
            $store = $store->fetch();
        }
        catch(\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error fetching from redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $store->getKey());
        }

        return $store;
    }

    /**
     * Deletes store object data from store
     */
    public function deleteOrFail(Base $store)
    {
        try
        {
            $store = $store->delete();
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e);

            throw new Exception\ServerErrorException(
                    'Error removing data from redis',
                    ErrorCode::SERVER_ERROR_REDIS_EXCEPTION,
                    $store->toArray());
        }

        return $store;
    }
}
