<?php

namespace RZP\Models\Base;

use RZP\Exception;

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

    public function toArrayAdmin()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array['admin'] = true;

        $array[static::ITEMS] = $this->itemsToArrayAdmin();

        return $array;
    }

    public function toArrayDiff()
    {
        return $this->itemsToArrayDiff();
    }

    public function toArrayReport()
    {
        $data = $this->itemsToArrayReport();

        // remove nulls
        $data = array_filter($data);

        // return the values (array_filter adds indexes for in between nulls)
        return array_values($data);
    }

    public function toArrayGateway()
    {
        return $this->itemsToArrayGateway();
    }

    public function toArrayPublicEmbedded()
    {
        return $this->itemsToArrayPublic();
    }

    public function getIds()
    {
        $ids = array_map(function($item)
        {
            return $item->getKey();

        }, $this->items);

        return $ids;
    }

    public function toArrayWithItems()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array[static::ITEMS] = $this->items;

        return $array;
    }

    public function getPublicIds()
    {
        $publicIds = array_map(function($item)
        {
            return $item->getPublicId();

        }, $this->items);

        return $publicIds;
    }

    /**
     * Get a dictionary keyed by given attribute
     *
     * @param null                $field
     * @param  \ArrayAccess|array $items
     *
     * @return array
     */
    public function getDictionaryByAttribute($field = null, $items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = array();

        foreach ($items as $value)
        {
            $key = is_null($field) ? $value->getKey() : $value->getAttribute($field);

            $dictionary[$key] = $value;
        }

        return $dictionary;
    }

    public function getStringAttributesByKey($field = null, $items = null)
    {
        $items = is_null($items) ? $this->items : $items;

        $dictionary = array();

        foreach ($items as $value)
        {
            $key = is_null($field) ? $value->getKey() : $value->getAttribute($field);

            $dictionary[$key] = array_map('strval', $value->getAttributes());
        }

        return $dictionary;
    }

    public function filterEntitiesFromEntityIds($entityIds)
    {
        $filteredEntities = $this->only($entityIds)->items;

        // This is required to remove all null entries from the array
        return array_filter($filteredEntities);
    }

    public function callOnEveryItem($function)
    {
        return array_map(function($item) use ($function)
        {
            return $item->$function();

        }, $this->items);
    }

    protected function itemsToArrayPublic()
    {
        return array_map(function($item)
        {
            return $item->toArrayPublic();

        }, $this->items);
    }

    protected function itemsToArrayDiff()
    {
        return array_map(function($item)
        {
            return $item->toArrayDiff();

        }, $this->items);
    }

    protected function itemsToArrayAdmin()
    {
        return array_map(function($item)
        {
            return $item->toArrayAdmin();

        }, $this->items);
    }

    protected function itemsToArrayReport()
    {
        return array_map(function($item)
        {
            return $item->toArrayReport();

        }, $this->items);
    }

    protected function itemsToArrayGateway()
    {
        return array_map(function($item)
        {
            return $item->toArrayGateway();

        }, $this->items);
    }

    public static function isPublicCollection($object)
    {
        return (get_class($object) === static::class);
    }
}
