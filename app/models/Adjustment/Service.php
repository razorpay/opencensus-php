<?php

namespace Models\Adjustment;

use Models\Base;
use Models\Adjustment;

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
        $settlements = (new Adjustment\Repository)->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }
}