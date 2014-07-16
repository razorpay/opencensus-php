<?php

namespace Models\Card;

use Models\Card;

class Core
{
    protected $card = null;

    public function create($input)
    {
        $card = (new Card\Entity)->build($input);

        $this->card = $card;

        $this->fillNetworkDetails($input, $card);

        $this->checkNetwork($card, $input['number']);

        return $card;
    }

    public function getCard()
    {
        return $this->card;
    }

    public function createAndReturnWithSensitiveData(array $input)
    {
        $card = $this->create($input);

        Card\Entity::modifyNumber($input);

        return array_merge(
            $card->toArray(),
            ['number' => $input['number'],
             'cvv' => $input['cvv']]);
    }

    public function fillNetworkDetails($input, $card)
    {
        $iin = substr($input['number'], 0, 6);

        $details = (new Card\Repository)->retrieveDetails($iin);

        $card->fillNetworkDetails($details, $iin);
    }

    public function checkNetwork($card, $number)
    {
        $network = $card->getNetwork();

        if ($network === null)
        {
            $iin = substr($number, 0, 6);

            Card\Unrecognized::create(['iin' => $iin]);
        }
    }
}