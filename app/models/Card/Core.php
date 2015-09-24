<?php

namespace Models\Card;

use Models\Card;
use EE\Error\ErrorCode;
use EE\Exception;

class Core
{
    protected $card = null;

    public function create($input, $merchant)
    {
        $card = (new Card\Entity)->build($input);

        $card->merchant()->associate($merchant);

        $this->card = $card;

        $this->fillNetworkDetails($card, $input);

        return $card;
    }

    public function getCard()
    {
        return $this->card;
    }

    public function createAndReturnWithSensitiveData(array $input, $merchant)
    {
        Card\Entity::modifyNumber($input);

        $card = $this->create($input, $merchant);

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
                $recordedNetwork = $details->getNetwork();

                if (($recordedNetwork !== null) and
                    ($recordedNetwork !== ''))
                {
                    if (Card\Network::isValidNetwork($recordedNetwork))
                    {
                        $card->setNetwork($recordedNetwork);
                    }
                }
            }

            $type = Card\Type::getType($details['type']);

            $arr = array(
                Entity::TYPE    => $type,
                Entity::ISSUER  => $details['issuer'],
                Entity::COUNTRY => $details['country']);

            if (($details['type'] !== '') and
                ($details['type'] !== null))
            {
                $arr[Entity::TYPE] = $details['type'];
            }

            $card->fill($arr);
        }
        else
        {
            $card->setType(Type::UNKNOWN);
        }

        $this->checkCvvLength($card, $input);

        $card->saveOrFail();
    }

    protected function checkCvvLength($card, $input)
    {
        $cvvLength = strlen($input['cvv']);

        if (($card->getNetworkCode() === Card\Network::AMEX) and
            ($cvvLength !== 4))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_AMEX_CVV_LENGTH_MUST_BE_FOUR);
        }
        else if ($cvvLength !== 3)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_LENGTH_MUST_BE_THREE,
                'cvv');
        }
    }
}