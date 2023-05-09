<?php

namespace RZP\Models\Base;

class PublicCollection extends Collection
{
    const COUNT = 'count';
    const ITEMS = 'items';
    const ENTITY = 'entity';
    const HAS_MORE = 'has_more';

    protected $hasMore = null;
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
        $this->setHasMoreInCollectionResponse($array);
        $array[static::ITEMS] = $this->itemsToArrayPublic();

        return $array;
    }

    public function setHasMore(bool $hasMore)
    {
        $this->hasMore = $hasMore;

        return $this;
    }

    public function getHasMore()
    {
        return $this->hasMore;
    }

    /**
     * Get the collection of items as a plain array with fields in proxy array of the entity.
     *
     * @return array
     */

    public function toArrayProxy()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array[static::ITEMS] = $this->itemsToArrayProxy();

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

    public function toArrayProxyWithExpand(): array
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array[static::ITEMS] = $this->itemsToArrayProxy($expand = true);

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

    public function toArrayRecon()
    {
        return $this->itemsToArrayRecon();
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

    public function toListSubmerchantsArray()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT]  = count($this->items);
        $array[static::ITEMS]  = array_map(function($item)
        {
            return $item->toListSubmerchantsArray();

        }, $this->items);

        return $array;
    }

    public function toArrayCaPartnerBankPoc(): array
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT]  = count($this->items);
        $array[static::ITEMS]  = $this->itemsToArrayCaPartnerBankPoc();

        return $array;
    }

    public function toArrayCaPartnerBankManager(): array
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT]  = count($this->items);
        $array[static::ITEMS]  = $this->itemsToArrayCaPartnerBankManager();

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

    /**
     * Converts the items list in the list of arrays having fields which are in proxy list of the entity
     *
     * @return array
     */
    protected function itemsToArrayProxy(bool $expand = false): array
    {
        return array_map(function($item) use ($expand)
        {
            if ($expand === true)
            {
                return $item->toArrayProxyWithExpand();
            }
            else
            {
                return $item->toArrayProxy();
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

    private function itemsToArrayRecon()
    {
        return array_map(function ($item)
        {
            return $item->toArrayRecon();

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

    protected function itemsToArrayCaPartnerBankPoc(): array
    {
        return array_map(function($item)
        {
            return $item->toArrayCaPartnerBankPoc();

        }, $this->items);
    }


    protected function itemsToArrayCaPartnerBankManager(): array
    {
        return array_map(function($item)
        {
            return $item->toArrayCaPartnerBankPoc();

        }, $this->items);
    }

    protected function setHasMoreInCollectionResponse(& $array)
    {
        $hasMore = $this->getHasMore();

        //
        // We are setting hasMore only for proxy auth currently.
        // We don't want this class (PublicCollection) to have
        // context of auth. Whoever is creating the collection
        // will set hasMore as per business logic. If they don't
        // set it to anything specifically, we don't want to
        // expose hasMore with null value and hence removing
        // it completely from the collection result set.
        //
        // Also, we are checking with null explicitly and not
        // `empty` because hasMore can be `false` too and that
        // would evaluate `empty` to false.
        //
        if ($hasMore === null)
        {
            return;
        }

        $array[static::HAS_MORE] = $hasMore;
    }

    public static function isPublicCollection($object): bool
    {
        if (empty($object) === true || !is_object($object))
        {
            return false;
        }

        return (get_class($object) === static::class);
    }
}
