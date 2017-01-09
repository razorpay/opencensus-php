<?php

namespace RZP\Services\Mock;

use RZP\Services\RedisStore as BaseStore;

class RedisStore extends BaseStore
{
    protected function fetchSortedSetData(string $key)
    {
        return [];
    }
}
