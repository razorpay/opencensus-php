<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Service extends Base\Service
{
    public function fetchById($id)
    {
        Card\Entity::verifyIdAndStripSign($id);

        $card = $this->repo->card->findByIdAndMerchantId($id, $this->merchant->getId());

        return $card->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $cards = $this->repo->card->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }

    public function getCardRecurring($input)
    {
        (new Card\Validator)->validateInput('recurring', $input);

        $iin = substr($input['number'], 0, 6);

        $iinEntity = $this->repo->iin->find($iin);

        $data['recurring'] = false;

        if (($iinEntity != null) and
            ($iinEntity->getType() === Card\Type::CREDIT))
        {
            $data['recurring'] = true;
        }

        return $data;
    }
}