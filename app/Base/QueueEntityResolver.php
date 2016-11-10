<?php

namespace RZP\Base;

use Illuminate\Contracts\Queue\EntityResolver as EntityResolverContract;
use RZP\Exception;

class QueueEntityResolver implements EntityResolverContract
{
    /**
     * Resolve the entity for the given ID.
     *
     * @param  string  $type
     * @param  mixed  $id
     * @return mixed
     *
     * @throws Exception\DbQueryException
     */
    public function resolve($type, $id)
    {
        $instance = (new $type)->setConnection('live')->find($id);

        if ($instance)
        {
            return $instance;
        }

        $instance = (new $type)->setConnection('test')->find($id);

        if ($instance)
        {
            return $instance;
        }

        throw new Exception\DbQueryException(['id' => $id, 'entity' => $type]);
    }
}
