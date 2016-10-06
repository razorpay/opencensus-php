<?php

namespace RZP\Models\Payment;

use RZP\Models\Base\PublicCollection;

class Collection extends PublicCollection
{
    const COUNT = 'count';
    const ITEMS = 'items';
    const ENTITY = 'entity';

    protected $entity = 'collection';

    /**
     * Get attributes needed for generating DailyReport
     * @return array
     */
    public function toArrayDailyReport()
    {
        $array[static::ENTITY] = $this->entity;
        $array[static::COUNT] = count($this->items);
        $array['admin'] = true;

        $array[static::ITEMS] = $this->itemsToArrayDailyReport();

        return $array;
    }

    protected function itemsToArrayDailyReport()
    {
        return array_map(function($item)
        {
            return $item->toArrayDailyReport();

        }, $this->items);
    }
}
