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

        $this->fillNetworkDetails($card, $input);

        return $card;
    }

    public function getCard()
    {
        return $this->card;
    }

    public function createAndReturnWithSensitiveData(array $input)
    {
        Card\Entity::modifyNumber($input);

        $card = $this->create($input);

        return array_merge(
            $card->toArray(),
            ['number' => $input['number'],
             'cvv' => $input['cvv']]);
    }

    public function fillNetworkDetails($card, $input)
    {
        $network = Card\Network::detectNetwork($input['number']);

        $networkName = Card\Network::getFullName($network);

        $card->setNetwork($networkName);

        // Get details for this iin from card repository
        $details = (new Card\Repository)->retrieveIinDetails($card->getIin());

        if ($details)
        {
            if ($network === Card\Network::UNKNOWN)
            {
                if ($details->getNetwork() !== null)
                {
                    $network = $details->getNetwork();

                    if (Card\Network::isValidNetwork($network))
                    {
                        $card->setNetwork($network);
                    }
                }
            }

            $arr = array(
                Entity::ISSUER  => $details['issuer'],
                Entity::COUNTRY => $details['country']);

            if ($details['card_type'] !== '')
            {
                $arr[Entity::TYPE] = $details['card_type'];
            }

            $card->fill($arr);
        }
        else
        {
            $card->setType(Type::UNKNOWN);
        }
    }
}