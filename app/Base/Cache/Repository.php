<?php

namespace RZP\Base\Cache;

use Illuminate\Cache\Repository as BaseRepository;

class Repository extends BaseRepository
{
    protected function getSeconds($ttl)
    {
        if (is_numeric($ttl) === true)
        {
            $ttl = $ttl * 60;
        }

        return parent::getSeconds($ttl);
    }
}
