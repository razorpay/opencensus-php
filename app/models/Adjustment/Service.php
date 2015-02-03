<?php

namespace Models\Adjustment;

use Models\Base;
use Models\Adjustment;
use Models\Settlement;
use Models\Transaction;

class Service extends Base\Service
{
    public function getAdjustment($id)
    {
        Adjustment\Entity::verifyIdAndStripSign($id);

        $setl = (new Adjustment\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $setl->toArrayPublic();
    }

    public function getAdjustments($input)
    {
        $adjustments = (new Adjustment\Repository)->fetch($input, $this->merchant->getKey());

        return $adjustments->toArrayPublic();
    }

    public function addAdjustment($input)
    {
        $merchant = $this->merchant;

        $adj = (new Adjustment\Entity)->build($input);
        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $adjRepo = new Adjustment\Repository;
        $adjRepo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj);

        (new Transaction\Repository)->saveOrFail($txn);
        $adjRepo->saveOrFail($adj);

        return $adj->toArrayPublic();
    }
}