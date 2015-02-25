<?php

namespace Models\Adjustment;

use Models\Base;
use Models\Adjustment;
use Models\Settlement;
use Models\Transaction;

class Core extends Base\Core
{
    public function createAdjustment($input, $merchant)
    {
        $adj = (new Adjustment\Entity)->build($input);
        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $adjRepo = new Adjustment\Repository;
        $adjRepo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        (new Transaction\Repository)->saveOrFail($txn);
        $adjRepo->saveOrFail($adj);

        return $adj;
    }
}