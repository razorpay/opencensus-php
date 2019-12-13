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

    /**
     * Get the collection of items as a plain array.
     * Response will include the relations which are fetched and present in the expanded[] array of the entity.
     *(eg. expand[] = transaction, transaction.settlement with payment fetch).
     *
     * @return array
     */
    public function toArrayPublicWithExpand(): array
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array[static::ITEMS] = $this->itemsToArrayPublic($expand = true);

        return $array;
    }

    /**
     * `load` on a collection fails if some items of the collection don't have the given foreign_key/relation.
     * This function removes those items from the collection by checking explicitly whether the foreign_key is set.
     * It then runs `load` on the collection and then adds back the previous items that were removed from the collection.
     * This ensures that we are able to load for all those items in the collection which have the foreign_key/relation set.
     *
     * @param string $relation
     * @param string $foreignKey
     *
     * @return $this
     */
    public function loadRelationWithForeignKey(string $relation, string $foreignKey = null)
    {
        $entitiesWithoutRelation = [];

        if (empty($foreignKey) === true)
        {
            $foreignKey = snake_case($relation) . '_id';
        }

        foreach ($this->items as $index => $entity)
        {
            if (empty($entity[$foreignKey]) === true)
            {
                $entitiesWithoutRelation[$index] = $entity;

                $this->forget($index);
            }
        }

        $this->load(camel_case($relation));

        $this->items = array_replace($this->items, $entitiesWithoutRelation);

        ksort($this->items, SORT_NUMERIC);

        return $this;
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

    public function toArrayPublicEmbedded(bool $expand = false)
    {
        return $this->itemsToArrayPublic($expand);
    }

    public function toArrayHosted()
    {
        return $this->itemsToArrayHosted();
    }

    public function toArrayPublicCustomer(bool $populateMessages = false)
    {
        return $this->itemsToArrayPublicCustomer($populateMessages);
    }

    public function toArrayPartner(): array
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT]  = count($this->items);
        $array[static::ITEMS]  = $this->itemsToArrayPartner();

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

    protected function itemsToArrayPublic(bool $expand = false): array
    {
        return array_map(function($item) use ($expand)
        {
            if ($expand === true)
            {
                return $item->toArrayPublicWithExpand();
            }
            else
            {
                return $item->toArrayPublic();
            }
        }, $this->items);
    }

    public function itemsToArrayHosted()
    {
        return array_map(function ($item)
        {
            return $item->toArrayHosted();

        }, $this->items);
    }

    public function itemsToArrayPublicCustomer(bool $populateMessages = false)
    {
        return array_map(function($item) use ($populateMessages)
        {
            return $item->toArrayPublicCustomer($populateMessages);
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

    protected function itemsToArrayPartner()
    {
        return array_map(function($item)
        {
            return $item->toArrayPartner();

        }, $this->items);
    }

    public static function isPublicCollection($object)
    {
        if (empty($object) === true)
        {
            return false;
        }

        return (get_class($object) === static::class);
    }
}
