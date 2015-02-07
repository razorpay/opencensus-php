<?php

namespace Models\Pricing;

use EE\Error\ErrorCode;
use EE\Exception;
use Models\Base;
use Models\Pricing;

class Service extends Base\Service
{
    protected $repo = null;

    public function __construct()
    {
        $this->repo = new Card\Repository();
    }

    public function fetchById($id)
    {
        Card\Entity::verifyIdAndStripSign($id);

        $card = $this->repo->findByIdAndMerchantId($id, $this->merchant->getId());

        return $card->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $cards = $this->repo->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }
}
