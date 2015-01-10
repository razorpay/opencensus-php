<?php

namespace Models\Base;

use EE\Exception;
use EE\Error\ErrorCode;

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
        $array[static::ITEMS] = $this->itemsToArrayPublic();

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

    protected function itemsToArrayPublic()
    {
        return array_map(function($item)
        {
            return $item->toArrayPublic();

        }, $this->items);
    }
}