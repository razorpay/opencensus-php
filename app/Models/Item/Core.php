<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Exception;

class Core extends Base\Core
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getTotalAmountFromItems(array $items)
    {
        $totalAmount = 0;

        array_map(function($item) use (& $totalAmount)
        {
            $totalAmount += $item->getAmount();

        }, $items);

        return $totalAmount;
    }
}