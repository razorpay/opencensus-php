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

    public function getCardRecurring($input)
    {
        (new Card\Validator)->validateInput('recurring', $input);

        $iin = substr($input['number'], 0, 6);

        $iinEntity = $this->repo->iin->find($iin);

        $data['recurring'] = false;

        if (($iinEntity !== null) and
            ($iinEntity->getType() === Card\Type::CREDIT) and
            (in_array($iinEntity->getNetworkCode(), Card\Network::$recurringNetworks, true)))
        {
            $data['recurring'] = true;
        }

        return $data;
    }
}