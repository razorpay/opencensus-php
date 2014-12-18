<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;
use Illuminate\Database\Eloquent\Collection;

class PublicCollection extends Collection
{
    const COUNT = 'count';
    const ITEMS = 'items';
    const ENTITY = 'entity';

    protected $entity = 'collection';

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArrayPublic()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);

        $array[static::ITEMS] = array_map(function($value)
        {
            return $value->toArrayPublic();

        }, $this->items);

        return $array;
    }

    public function getIds()
    {
        $ids = array_map(function($item)
        {
            return $item->getKey();

        }, $this->items);

        return $ids;
    }
}