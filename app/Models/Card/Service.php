<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

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

    public function updateSavedCards()
    {
        $data = $this->repo->card->updateSavedCardsWithIins();

        $this->trace->info(TraceCode::SAVED_CARDS_UPDATED_WITH_IIN, $data);

        return $data;
    }

    public function getCardRecurring($input)
    {
        (new Card\Validator)->validateInput('recurring', $input);

        $iin = $input['iin'];

        $iinEntity = $this->repo->iin->find($iin);

        $data['recurring'] = (new Card\Entity)->isRecurringSupportedOnNetworkAndIssuerAndType(
                                                                                    $this->merchant,
                                                                                    $iinEntity->getNetworkCode(),
                                                                                    $iinEntity->getIssuer(),
                                                                                    $iinEntity->getType());

        return $data;
    }
}
