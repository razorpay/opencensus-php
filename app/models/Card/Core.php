<?php

namespace Models\Card;

use Models\Base;
use Models\Card;
use EE\Error\ErrorCode;
use EE\Exception;

class Core extends Base\Core
{
    protected $card = null;

    public function create($input, $merchant)
    {
        $card = (new Card\Entity)->build($input);

        $card->merchant()->associate($merchant);

        $this->card = $card;

        $this->fillNetworkDetails($card, $input);

        $card->saveOrFail();

        return $card;
    }

    public function edit($card, $input)
    {
        $card->edit($input);

        $this->card = $card;

        $card->saveOrFail();

        return $card;
    }

    public function getCard()
    {
        return $this->card;
    }

    public function createAndReturnWithSensitiveData(array $input, $merchant)
    {
        Card\Entity::modifyNumber($input);

        $card = null;

        if (isset($input['token']))
        {
            $card = $this->findExistingCards($input, $merchant);

            $this->card = $card;
        }

        if ($card === null)
        {
            $card = $this->create($input, $merchant);
        }
        // else
        // {
        //     $editInput = array_diff($input, $card->toArray());

        //     $card = $this->edit($card, $editInput);
        // }

        return array_merge(
            $card->toArray(),
            ['number' => $input['number'],
             'cvv' => $input['cvv']]);
    }

    public function createDuplicateCard($input, $merchant)
    {
        $createInput = array(
            'number'                =>  $input['number'],
            Entity::EXPIRY_MONTH    =>  $input[Entity::EXPIRY_MONTH],
            Entity::EXPIRY_YEAR     =>  $input[Entity::EXPIRY_YEAR],
            'cvv'                   =>  $input['cvv'],
            Entity::NAME            =>  $input[Entity::NAME],
            Entity::TOKEN           =>  $input[Entity::TOKEN],
            Entity::SERVICE         =>  $input[Entity::SERVICE]
        );

        $card = null;

        if (isset($input['token']))
        {
            $card = $this->findExistingCards($createInput, $merchant);

            $this->card = $card;
        }

        if ($card === null)
        {
            $card = $this->create($createInput, $merchant);
        }

        return $card;
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

                if ((empty($recordedNetwork) === false) and
                    (Card\Network::isValidNetwork($recordedNetwork)))
                {
                    $card->setNetwork($recordedNetwork);
                }
            }

            $type = Card\Type::getType($details['type']);

            if ($network === Network::AMEX)
            {
                $type = Type::CREDIT;
            }

            $emi = IIN\IIN::isEmiAvailableForCard($details, $input['number']);

            $arr = array(
                Entity::TYPE            => $type,
                Entity::ISSUER          => $details['issuer'],
                Entity::COUNTRY         => $details['country'],
                Entity::INTERNATIONAL   => $details->isInternational(),
                Entity::EMI             => $emi,
            );

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
    }

    protected function checkCvvLength($card, $input)
    {
        $cvvLength = strlen($input['cvv']);

        if ($card->getNetworkCode() === Card\Network::AMEX)
        {
            if ($cvvLength !== 4)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_PAYMENT_CARD_AMEX_CVV_LENGTH_MUST_BE_FOUR);
            }
        }
        else if ($cvvLength !== 3)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_CVV_LENGTH_MUST_BE_THREE,
                'cvv');
        }
    }

    protected function findExistingCards($input, $merchant)
    {
        $params = array(
            Card\Entity::MERCHANT_ID     => $merchant->getId(),
            Card\Entity::EXPIRY_MONTH    => $input['expiry_month'],
            Card\Entity::EXPIRY_YEAR     => $input['expiry_year'],
            Card\Entity::TOKEN           => $input['token'],
            Card\Entity::SERVICE         => $input['service'],
        );

        $cards = (new Card\Repository)->getByParams($params);

        if ($cards->count() > 0)
        {
            assert($cards->count() === 1);
            return $cards[0];
        }

        return null;
    }
}