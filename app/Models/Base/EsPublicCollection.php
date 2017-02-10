<?php

namespace RZP\Models\Base;

use RZP\Exception;

class EsPublicCollection extends Collection
{
    const COUNT  = 'count';
    const ITEMS  = 'items';
    const ENTITY = 'entity';

    protected $entity = 'collection';

    public function toArrayPublic()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT]  = count($this->items);
        $array[static::ITEMS]  = $this->toArray();

        return $array;
    }
}
