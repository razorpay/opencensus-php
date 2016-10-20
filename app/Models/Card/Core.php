<?php

namespace RZP\Models\Card;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;

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

        if (isset($input[Entity::VAULT]))
        {
            $newCard = (new Card\Entity)->build($input);

            $card = $this->findExistingCards($newCard, $merchant);

            $this->card = $card;
        }

        if ($card === null)
        {
            $card = $this->create($input, $merchant);
        }

        return array_merge(
            $card->toArray(),
            [
                'number' => $input['number'],
                'cvv' => $input['cvv']
            ]);
    }

    public function createDuplicateCard($input, $merchant)
    {
        $createInput = array(
            Entity::NUMBER          =>  $input[Entity::NUMBER],
            Entity::EXPIRY_MONTH    =>  $input[Entity::EXPIRY_MONTH],
            Entity::EXPIRY_YEAR     =>  $input[Entity::EXPIRY_YEAR],
            Entity::CVV             =>  $input[Entity::CVV],
            Entity::NAME            =>  $input[Entity::NAME],
        );

        $card = $this->create($createInput, $merchant);

        return $card;
    }

    public function fillNetworkDetails($card, $input)
    {
        $network = Card\Network::detectNetwork($input['number']);

        $networkName = Card\Network::getFullName($network);

        $card->setNetwork($networkName);

        // Get details for this iin from card repository
        $details = $this->repo->card->retrieveIinDetails($card->getIin());

        $type = null;

        if ($details)
        {
            $iinNetwork = $details->getNetwork();

            if (($network === Card\Network::UNKNOWN) and
                (Card\Network::isValidNetwork($iinNetwork)))
            {
                $card->setNetwork($iinNetwork);
            }

            $type = $details['type'];

            $emi = IIN\IIN::isEmiAvailableForCard($details, $input['number']);

            // Since AMEX is handled as a different case,
            // mark all amex cards as non international
            $isInternational = $details->isInternational();

            if ($network === Card\Network::AMEX)
            {
                $isInternational = false;
            }

            $arr = array(
                Entity::ISSUER          => $details['issuer'],
                Entity::COUNTRY         => $details['country'],
                Entity::INTERNATIONAL   => $isInternational,
                Entity::EMI             => $emi,
            );

            $card->fill($arr);
        }
        else
        {
            // For cards other than AMEX, RuPay, trace missing IIN
            if (($card->isAmex() === false) and
                ($card->isRuPay() === false) and
                ($this->mode !== Mode::TEST))
            {
                $this->traceMissingIin($card);
            }
        }

        $type = Card\Type::getType($type, $network);
        $card->setType($type);

        $this->checkCvvLength($card, $input);
    }

    protected function traceMissingIin($card)
    {
        $data = [
            'iin'       => $card->getIin(),
            'card_id'   => $card->getPublicId(),
            'merchant'  => $card->merchant->getBillingLabelElseName()
        ];

        $this->trace->warning(TraceCode::PAYMENT_CARD_IIN_MISSING, $data);
    }

    protected function checkCvvLength($card, $input)
    {
        $cvvLength = strlen($input['cvv']);

        // If card is Amex, cvv length should be 4.
        if ($card->isAmex())
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
                Entity::CVV);
        }
    }

    protected function findExistingCards($newCard, $merchant)
    {
        $params = array(
            Card\Entity::MERCHANT_ID     => $merchant->getId(),
            Card\Entity::EXPIRY_MONTH    => $newCard->getExpiryMonth(),
            Card\Entity::EXPIRY_YEAR     => $newCard->getExpiryYear(),
            Card\Entity::VAULT_TOKEN     => $newCard->getVaultToken(),
            Card\Entity::VAULT           => $newCard->getVault(),
        );

        $cards = $this->repo->card->getByParams($params);

        if ($cards->count() > 0)
        {
            // TODO: delete the other cards

            $cards->sortBy(Card\Entity::ID);

            return $cards[0];
        }

        return null;
    }
}
