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
        $card = $this->create($input);

        Card\Entity::modifyNumber($input);

        return array_merge(
            $card->toArray(),
            ['number' => $input['number'],
             'cvv' => $input['cvv']]);
    }

    public function fillNetworkDetails($card, $input)
    {
        $network = Card\Network::detectNetwork($input['number']);

        $card->setNetwork($network);

        // Get details for this iin from card repository
        $details = (new Card\Repository)->retrieveIinDetails($card->getIin());

        if ($details)
        {
            if ($network === Card\Network::UNKNOWN)
            {
                if ($details->getBrand() !== null)
                {
                    $network = $details->getBrand();

                    if (Card\Network::isValidNetwork($network))
                    {
                        $card->setNetwork($network);
                    }
                }
            }

            $arr = array(
                self::TYPE    => $details['type'],
                self::ISSUER  => $details['issuer'],
                self::COUNTRY => $details['country']);

            $this->fill($arr);
        }
        else
        {
            $card->setType(Type::UNKNOWN);
        }
    }
}