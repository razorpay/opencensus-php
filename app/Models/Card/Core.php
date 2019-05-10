<?php

namespace RZP\Models\Card;

use Route;
use RZP\Constants;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Card;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Services\SlackPoster;

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

    public function createForFundAccount($input, $merchant)
    {
        // - Validate Issuer Bank
        // - Ensure the card is saved

        $card = $this->create($input, $merchant);

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

    public function createAndReturnWithSensitiveData(array $input, Merchant\Entity $merchant): array
    {
        //
        // We are running modifiers outside the build() because
        // modifiers only change the input under the scope of build.
        // As `$input` is not passed by reference to build().
        //
        Card\Entity::modifyNumber($input);
        Card\Entity::modifyMaestro($input);
        Card\Entity::modifyBajajFinserv($input);

        $card = null;

        if (isset($input[Entity::VAULT]) === true)
        {
            $newCard = (new Card\Entity)->build($input);

            if (($newCard->getVaultToken() !== null) and
                ($newCard->getVault() === Card\Vault::RZP_VAULT))
            {
                $card = $this->findOneExistingCards($newCard, $merchant);

                $this->card = $card;
            }
        }

        if ($card === null)
        {
            $card = $this->create($input, $merchant);
        }

        $messageType = $card->iinRelation ? $card->iinRelation['message_type'] : null;

        return array_merge(
            $card->toArray(),
            [
                'number'       => $input['number'],
                'cvv'          => $input['cvv'],
                'message_type' => $messageType,
            ]);
    }

    public function createDuplicateCard($input, $merchant)
    {
        $createInput = array(
            Entity::NUMBER          => $input[Entity::NUMBER],
            Entity::EXPIRY_MONTH    => $input[Entity::EXPIRY_MONTH],
            Entity::EXPIRY_YEAR     => $input[Entity::EXPIRY_YEAR],
            Entity::CVV             => $input[Entity::CVV],
            Entity::NAME            => $input[Entity::NAME],
        );

        $card = $this->create($createInput, $merchant);

        return $card;
    }

    public function fillNetworkDetails($card, $input)
    {
        $network = Card\Network::detectNetwork($card->getIin());

        $networkName = Card\Network::getFullName($network);

        $card->setNetwork($networkName);

        // Get details for this iin from card repository
        $details = $this->repo->card->retrieveIinDetails($card->getIin());

        $type = null;

        if ($details)
        {
            $iinNetwork = $details->getNetwork();

            if (Card\Network::isValidNetwork($iinNetwork))
            {
                $card->setNetwork($iinNetwork);
            }
            else
            {
                $this->trace->error(
                    TraceCode::CARD_NETWORK_INVALID,
                    ['network' => $iinNetwork, 'iin' => $card->getIin()]);
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
            'merchant'  => $card->merchant->getBillingLabel()
        ];

        $this->trace->warning(TraceCode::PAYMENT_CARD_IIN_MISSING, $data);
    }

    protected function checkCvvLength($card, $input)
    {
        //
        // In case of global subscription recurring, we create
        // a duplicate card for a charge so that we can associate
        // that with the payment entity. But, to create a card,
        // the cvv should always be present. Since, we cannot get
        // cvv when charge is being done in a recurring manner,
        // we skip it.
        // The charges are done by internal auth.
        // Ideally, we shouldn't have internal auth check because
        // we might need to do a similar thing when we start with
        // global charge at will recurring.
        //
        if (($this->app['basicauth']->isProxyOrPrivilegeAuth() === true) and
            (isset($input['cvv']) === false))
        {
            return;
        }

        //
        // If the card is not Maestro, then cvv has to be set
        //
        if (empty($input['cvv']) === true)
        {
            // If card is Maestro, cvv may be absent
            if ($card->isMaestro() === true)
            {
                return;
            }

            throw new Exception\BadRequestValidationFailureException(
                'The cvv field is required',
                Entity::CVV);
        }

        $cvvLength = strlen($input['cvv']);

        // If card is Amex, cvv length should be 4.
        if ($card->isAmex() === true)
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

    public function findAllExistingCards(Card\Entity $newCard, Merchant\Entity $merchant, $limit = 10)
    {
        $params = array(
            Card\Entity::MERCHANT_ID     => $merchant->getId(),
            Card\Entity::EXPIRY_MONTH    => $newCard->getExpiryMonth(),
            Card\Entity::EXPIRY_YEAR     => $newCard->getExpiryYear(),
            Card\Entity::VAULT_TOKEN     => $newCard->getVaultToken(),
            Card\Entity::VAULT           => $newCard->getVault(),
        );

        $cards = $this->repo->card->getByParams($params, $limit);

        if ($cards->count() > 0)
        {
           return $cards;
        }

        return null;
    }

    protected function findOneExistingCards(Card\Entity $newCard, Merchant\Entity $merchant)
    {
        $limit = 1;

        $cards = $this->findAllExistingCards($newCard, $merchant, $limit);

        if ($cards === null)
        {
            return null;
        }

        return $cards[0];
    }
}
