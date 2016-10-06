<?php

namespace RZP\Models\Payment;

use RZP\Models\Base\PublicCollection;

class PaymentCollection extends PublicCollection
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
        $array[static::COUNT] = $this->count();

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
