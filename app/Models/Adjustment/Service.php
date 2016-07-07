<?php

namespace RZP\Models\Adjustment;

use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class Service extends Base\Service
{
    public function getAdjustment($id)
    {
        Adjustment\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->adjustment->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $setl->toArrayPublic();
    }

    public function getAdjustments($input)
    {
        $adjustments = $this->repo->adjustment->fetch($input, $this->merchant->getKey());

        return $adjustments->toArrayPublic();
    }

    public function addAdjustment($input)
    {
        $merchant = $this->merchant;

        $adj = (new Adjustment\Core)->createAdjustment($input, $merchant);

        return $adj->toArrayPublic();
    }
}