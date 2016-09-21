<?php

namespace RZP\Models\Payout;

use RZP\Models\Base;
use RZP\Models\Payout;

class Service extends Base\Service
{
    public function getPayout($id)
    {
        Payout\Entity::verifyIdAndStripSign($id);

        $payout = $this->repo->payout->findByIdAndMerchantId($id, $this->merchant->getId());

        return $payout->toArrayPublic();
    }

    public function getPayouts($input)
    {
        $payouts = $this->repo->payout->fetch($input, $this->merchant->getId());

        return $payouts->toArrayPublic();
    }

    public function postPayout($input)
    {
        $merchant = $this->merchant;

        $payout = (new Payout\Core)->createPayout($input, $merchant);

        return $payout->toArrayPublic();
    }
}