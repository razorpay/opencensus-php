<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;

class Service extends Base\Service
{
    public function fetchById($id)
    {
        $card = $this->repo->card->findByPublicIdAndMerchant($id, $this->merchant);

        return $card->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $cards = $this->repo->card->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }
}
