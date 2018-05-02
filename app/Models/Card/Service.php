<?php

namespace RZP\Models\Card;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function fetchById(string $id)
    {
        /** @var Entity $card */
        $card = $this->repo->card->findByPublicIdAndMerchant($id, $this->merchant);

        return $card->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        /** @var Base\PublicCollection $cards */
        $cards = $this->repo->card->fetch($input, $this->merchant->getId());

        return $cards->toArrayPublic();
    }

    public function updateSavedCards()
    {
        $data = $this->repo->card->updateSavedCardsWithIins();

        $this->trace->info(TraceCode::SAVED_CARDS_UPDATED_WITH_IIN, $data);

        return $data;
    }

    public function getCardRecurring(array $input)
    {
        (new Card\Validator)->validateInput('recurring', $input);

        $iin = $input[Entity::IIN];

        /** @var IIN\Entity|null $iinEntity */
        $iinEntity = $this->repo->iin->find($iin);

        $responseKey = 'recurring';

        if ($iinEntity === null)
        {
            return [$responseKey => false];
        }

        $recurring = (new Card\Entity)->isRecurringSupportedOnNetworkAndIssuerAndType(
                                            $this->merchant,
                                            $iinEntity->getNetworkCode(),
                                            $iinEntity->getIssuer(),
                                            $iinEntity->getType());

        return [$responseKey => $recurring];
    }
}
