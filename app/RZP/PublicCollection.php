<?php

namespace App\RZP;

use Illuminate\Support\Collection;

class PublicCollection extends Collection
{
    const COUNT = 'count';
    const ITEMS = 'items';
    const ENTITY = 'entity';

    protected $entity = 'collection';

    public function toArray()
    {
        $array[static::ENTITY] = $this->entity;

        $array[static::COUNT] = count($this->items);

        $array[static::ITEMS] = $this->itemsToArray();

        return $array;
    }

    protected function itemsToArray()
    {
        return array_map(function($item)
        {
            return $item->toArray();

        }, $this->items);
    }
}
