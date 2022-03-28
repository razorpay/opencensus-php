<?php

namespace RZP\Models\Card;

use Route;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer;
use RZP\Models\FundAccount;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundAccount\Type as FundAccountType;

class Core extends Base\Core
{
    protected $card = null;

    public function create($input, $merchant, $recurring = false, $dummyProcessing = false)
    {
        $card = (new Card\Entity)->build($input);

        $this->setVaultTokenAndFingerPrint($card, $input, $recurring);

        $card->merchant()->associate($merchant);

        $this->card = $card;

        $iin = $this->fillNetworkDetails($card, $input);

        if (empty($iin) === false)
        {
            $card->iinRelation()->associate($iin);
        }

        if ($dummyProcessing === false)
        {
            $this->repo->saveOrFail($card);
        }

        return $card;
    }

    public function migrateToTokenizedCard($card, $merchant, $input)
    {
        $response = $this->getTokenizedCardResponseFromAnExistingVault($card, $merchant, $input);

        return $this->migrationCardToTokenisedCard($card, $input, $merchant, $response);
    }

    public function fetchParValue($input)
    {
        return (new Card\CardVault)->fetchParValueFromVault($input);e;
    }

    public function createTokenizedCard($input, $merchant)
    {
        $response = $this->getTokenizedCardResponseFromVault($input, $merchant);

        return $this->createTokenizedCardEntity($input['card'], $merchant, $response);
    }


    protected function migrationCardToTokenisedCard($card, $input, $merchant, $response)
    {
        $tokenisedCard = $card->replicate();

        $tokenisedCard->generateID();

        $tokenisedCard->setVaultToken($response['token']);

        $tokenisedCard->setGlobalFingerprint($response['fingerprint']);

        if(empty($response['service_provider_tokens']) === false)
        {
            $tokenisedCard->setVault(strtolower($response['service_provider_tokens'][0]['provider_name']));

            if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_number'))
            {
                $tokenisedCard->setTokenIin(substr($response['service_provider_tokens'][0]['provider_data']['token_number'], 0, 9));
            }

            if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_expiry_month') &&
                $this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_expiry_year'))
            {
                $tokenisedCard->setTokenExpiryMonth($response['service_provider_tokens'][0]['provider_data']['token_expiry_month']);

                $expiry_year = $response['service_provider_tokens'][0]['provider_data']['token_expiry_year'];

                if ($expiry_year !== null && strlen($expiry_year) == 2)
                {
                    $expiry_year = '20' . $expiry_year;
                }

                $tokenisedCard->setTokenExpiryYear($expiry_year);
            }

            $tokenisedCard->saveOrFail();
        }

        return [$tokenisedCard, $response['service_provider_tokens']];
    }

    protected function createTokenizedCardEntity($input, $merchant, $response)
    {
        $createInput = [
            Card\Entity::VAULT_TOKEN        => $response['token'],
            Card\Entity::GLOBAL_FINGERPRINT => $response['fingerprint'],
            Card\Entity::LAST4              => $this->getLast4($input, $response),
            Card\Entity::LENGTH             => 0,
            Card\Entity::TOKEN_IIN          => '000000000',
            Card\Entity::TOKEN_EXPIRY_MONTH => '0',
            Card\Entity::TOKEN_EXPIRY_YEAR  => '9999',
        ];

        if ($this->isPresent($input, Card\Entity::NAME))
        {
            $createInput[Card\Entity::NAME] = $input[Card\Entity::NAME];
        }

        $iinNumber  = substr($input[Card\Entity::NUMBER] ?? null, 0, 6);

        $createInput[Card\Entity::IIN] = $iinNumber;

        $createInput[Card\Entity::EXPIRY_MONTH] = (int)$input[Card\Entity::EXPIRY_MONTH];

        $createInput[Card\Entity::EXPIRY_YEAR] = $input[Card\Entity::EXPIRY_YEAR];

        if (empty($response['service_provider_tokens']) === false)
        {
            $createInput[Card\Entity::VAULT] = strtolower($response['service_provider_tokens'][0]['provider_name']);

            if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_number'))
            {
                $createInput[Card\Entity::TOKEN_IIN] = substr($response['service_provider_tokens'][0]['provider_data']['token_number'], 0, 9);
            }
            else
            {
                $response['service_provider_tokens'][0]['provider_data']['token_iin'] = null;
            }

            if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_number'))
            {
                $createInput[Card\Entity::LENGTH] = strlen($response['service_provider_tokens'][0]['provider_data']['token_number']);
            }

            if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_expiry_month') &&
                $this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_expiry_year'))
            {
                $createInput[Card\Entity::TOKEN_EXPIRY_MONTH] = $response['service_provider_tokens'][0]['provider_data']['token_expiry_month'];

                $expiry_year = $response['service_provider_tokens'][0]['provider_data']['token_expiry_year'];

                if ($expiry_year !== null && strlen($expiry_year) == 2)
                {
                    $expiry_year = '20' . $expiry_year;
                }

                $createInput[Card\Entity::TOKEN_EXPIRY_YEAR] = $expiry_year;
            }
            else
            {
                $response['service_provider_tokens'][0]['provider_data']['token_expiry_month'] = null;

                $response['service_provider_tokens'][0]['provider_data']['token_expiry_year'] = null;
            }
        }

        $tokenizedCard = (new Card\Entity)->buildCard($createInput, 'tokenizedCard');

        $tokenizedCard->merchant()->associate($merchant);

        $card = $this->getCardForIin($tokenizedCard, $input);

        // this is to update token iin incase of s2s merchants;
        $input[Card\Entity::TOKENISED] = 1;

        $iin = $this->fillNetworkDetails($card, $input);

        if (empty($iin) === false)
        {
            $tokenizedCard->iinRelation()->associate($iin);
        }

        return [$tokenizedCard, $response['service_provider_tokens']];
    }

    public function fetchCryptogram($serviceProviderTokenId, $merchant)
    {
        return $this->getCryptogramResponseFromVault($serviceProviderTokenId, $merchant);
    }

    public function fetchCryptogramForVaultToken($vaultToken, $merchant)
    {
        return $this->getCryptogramResponse($vaultToken, $merchant);
    }

    public function fetchToken($card)
    {
        return $this->getTokenResponseFromVault($card);
    }

    public function deleteToken($card)
    {
        return $this->deleteTokenResponseFromVault($card);
    }

    public function createViaCps($input, $merchant, $recurring)
    {
        $card = (new Card\Entity)->build($input);

        $card->merchant()->associate($merchant);

        $card->SetVault(Vault::RZP_VAULT);

        $this->setVaultTokenAndFingerPrint($card, $input, $recurring);

        $iin = $this->fillNetworkDetails($card, $input);

        $card->saveOrFail();

        return $card;
    }

    public function createForFundAccount($input, $merchant, $compositePayoutSaveOrFail)
    {
        $input[Card\Entity::VAULT] = Card\Vault::RZP_VAULT;

        //Duplication of code here, since using contact.name in place of card.name created problems
        //with validation due to dissimilar regex.
        // Todo: Find better approach
        // We are sending negation of compositePayoutSaveOrFail because to save the card entity
        // dummy processing needs to be false
        if ($merchant->isFeatureEnabled(Feature\Constants::ALLOW_CARD_NAME_CHANGES) === true)
        {
            $card = $this->createCardWithContactName($input, $merchant, false, !$compositePayoutSaveOrFail);
        }
        else
        {
            $card = $this->create($input, $merchant, false, !$compositePayoutSaveOrFail);
        }

        $cardType       = $card->getType();
        $cardIssuer     = $card->getIssuer();
        $cardVaultToken = $card->getCardVaultToken();
        $cardNetwork    = $card->getNetwork();

        //experiment for fund account of prepaid card type creation
        $prepaidCardVariant = $this->app->razorx->getTreatment(
            $merchant->getId(),
            Merchant\RazorxTreatment::PAYOUT_TO_PREPAID_CARDS,
            $this->mode,
            FundAccount\Entity::FUND_ACCOUNT_RX_RETRY_COUNT
        );

        if (($cardIssuer === Issuer::SCBL) and
            ($this->checkAllowedNetworksForSCBL($card) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                null,
                [
                    'type'             => $cardType,
                    'issuer'           => $cardIssuer,
                    'network'          => $cardNetwork,
                    'card_vault_token' => $cardVaultToken,
                ],
                $cardNetwork . " cards are not supported for issuer " . Issuer::SCBL
            );
        }

        // Querying m2p supported modes here because m2p supports debit as well as credit card payouts.
        // If a credit card is not supported for IMPS, NEFT, we check if m2p supports it.
        // If it does, we allow FA creation.
        $m2pSupportedModeConfigs = (new FundTransfer\Mode)->getM2PSupportedChannelModeConfig($cardIssuer, $cardNetwork, $cardType, $card->getIin());

        if ($cardType === Card\Type::DEBIT)
        {
            if (($card->getCardVaultToken() === null) or
                (empty($m2pSupportedModeConfigs) === true))
            {
                // if the fund account is of type card and it is a debit card and if no supported mode
                // is found via m2p channel(m2p is the only channel supporting debit card payouts),
                // then fail FA creation.
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                    null,
                    [
                        'type'             => $cardType,
                        'issuer'           => $cardIssuer,
                        'card_vault_token' => $cardVaultToken,
                    ]);
            }

            return $card;
        }

        // If a credit card is not supported for IMPS, NEFT, we check if m2p supports it.
        // If it does, we allow FA creation.
        if (($card->getCardVaultToken() === null) or
            (Type::isValidFundAccountCardType($cardType, $prepaidCardVariant) === false) or
            ((in_array($cardIssuer, FundTransfer\Mode::getSupportedIssuers(), true) === false) and
             (empty($m2pSupportedModeConfigs) === true)))
        {

            if (($card->isAmex() === true) and
                ($cardIssuer === null))
            {
                return $card;
            }

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                null,
                [
                    'type'             => $cardType,
                    'issuer'           => $cardIssuer,
                    'card_vault_token' => $cardVaultToken,
                ]);
        }

        (new Beneficiary)->enqueueForBeneficiaryRegistration($card, FundAccountType::CARD);

        return $card;
    }

    public function checkAllowedNetworksForSCBL($card)
    {
        $supportedNetworksForSCBL = [
            Card\Network::$fullName[Card\Network::VISA],
            Card\Network::$fullName[Card\Network::MC],
            Card\Network::$fullName[Card\Network::AMEX]
        ];

        return in_array($card->getNetwork(), $supportedNetworksForSCBL, true);
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

    public function createAndReturnWithSensitiveData(array $input, Merchant\Entity $merchant, bool $recurring, bool $dummyProcessing): array
    {
        //
        // We are running modifiers outside the build() because
        // modifiers only change the input under the scope of build.
        // As `$input` is not passed by reference to build().
        //
        Card\Entity::modifyNumber($input);
        Card\Entity::modifyMaestro($input);
        Card\Entity::modifyBajajFinserv($input);

        $card = $this->create($input, $merchant, $recurring, $dummyProcessing);

        if ($this->isCvvOptional($input) === true)
        {
            $input['cvv'] = null;
        }

        return array_merge(
            $card->toArray(),
            [
                'number'                        => $input['number'],
                'cvv'                           => $input['cvv'],
                Card\Entity::CRYPTOGRAM_VALUE   => $input[Card\Entity::CRYPTOGRAM_VALUE] ?? null,
                Card\Entity::TOKENISED          => (empty($input[Card\Entity::TOKENISED]) === false) ? boolval($input[Card\Entity::TOKENISED]) : false,
                CARD\Entity::TOKEN_PROVIDER     => $input[CARD\Entity::TOKEN_PROVIDER] ?? null
            ]);
    }

    public function setVaultTokenAndFingerPrint(Card\Entity $card, array $input, bool $recurring)
    {
        if (empty($card->getVault()) === true)
        {
            return;
        }

        try
        {
            $cardVault = (new Card\CardVault);

            $tempInput['card'] = $input['number'];

            $response = $cardVault->getTokenAndFingerprint($tempInput);
        }
        catch (\Throwable $e)
        {
           $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_ENCRYPTION_FAILED
            );

           $card->setVault(null);

           return;
        }

        $card->setVaultToken($response['token']);

        $card->setGlobalFingerprint($response['fingerprint']);

        $vault = Card\VAULT::RZP_ENCRYPTION;

        if (isset($response['scheme']) === true)
        {
           $vault = Card\Vault::getVaultName($response['scheme']);
        }

        if (($recurring === true) and
            ($vault === Card\Vault::RZP_ENCRYPTION))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_SAVE_FAILED,
                []);
        }

        $card->setVault($vault);
    }

    public function createDuplicateCard($input, $merchant)
    {
        $createInput = [
            Entity::NUMBER             => $input[Entity::NUMBER],
            Entity::EXPIRY_MONTH       => $input[Entity::EXPIRY_MONTH],
            Entity::EXPIRY_YEAR        => $input[Entity::EXPIRY_YEAR],
            Entity::CVV                => $input[Entity::CVV],
            Entity::NAME               => $input[Entity::NAME],
        ];

        $card = $this->create($createInput, $merchant);

        $card->setGlobalFingerprint($input[Entity::GLOBAL_FINGERPRINT]);

        return $card;
    }

    public function fillNetworkDetails($card, $input)
    {
        $iinNumber = $card->getIin();

        if ((empty($input[Card\Entity::TOKENISED]) === false) and
            (boolval($input[Card\Entity::TOKENISED]) === true) and
            (array_key_exists('number', $input) === true))
        {
            $tokenizedRange = substr($input['number'], 0, 9);

            $iinNumber = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange) ?? $iinNumber;
        }

        $network = Card\Network::detectNetwork($iinNumber);

        $networkName = Card\Network::getFullName($network);

        $card->setNetwork($networkName);

        // Get details for this iin from card repository
        $iin = $this->repo->card->retrieveIinDetails($iinNumber);

        $type = null;

        $subtype = null;

        $category = null;

        if ($iin)
        {
            $iinNetwork = $iin->getNetwork();

            if (Card\Network::isValidNetwork($iinNetwork))
            {
                $card->setNetwork($iinNetwork);
            }
            else
            {
                $this->trace->error(
                    TraceCode::CARD_NETWORK_INVALID,
                    ['network' => $iinNetwork, 'iin' => $iinNumber]);
            }

            $type = $iin['type'];

            $subtype = $iin[Card\IIN\Entity::SUBTYPE];

            $category = $iin[Card\IIN\Entity::CATEGORY];

            if (array_key_exists('emi', $input))
            {
                $emi = $input['emi'];
            }
            else
            {
                $emi = IIN\IIN::isEmiAvailableForCard($iin, $input['number']);
            }

            // Since AMEX is handled as a different case,
            // mark all amex cards as non international
            $isInternational = $iin->isInternational();

            if ($network === Card\Network::AMEX)
            {
                $isInternational = false;
            }

            $arr = [
                Entity::ISSUER          => $iin['issuer'],
                Entity::COUNTRY         => $iin['country'],
                Entity::INTERNATIONAL   => $isInternational,
                Entity::EMI             => $emi,
            ];

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

        $card->setSubtype($subtype);

        $card->setCategory($category);

        $this->checkCvvLength($card, $input);

         // for tokenised card we need to fetch the details from a static list.
        /** token iin not required to be set here in case of token_provision as
        this field will already have a value in case of provisioning.
         */
        if (empty($tokenizedRange) === false && empty($card->getTokenIin()) === true)
        {
            $card->setTokenIIn($tokenizedRange);
        }

        return $iin;
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

        if ($card->isRzpTokenisedCard() === false)
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

            // cases where cvv is not required
            if ($this->isCvvOptional($input) === true)
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

        $cards = $this->repo->useSlave(function() use ($params, $limit)
        {
            return $this->repo->card->getByParams($params, ['iinRelation'], $limit);
        });

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

    public function getCardEntity($id)
    {
        return $this->repo->card->find($id);
    }

    public function isCvvOptional($input): bool
    {
        if ((isset($input[Card\Entity::IS_CVV_OPTIONAL]) === true) and
            ($input[Card\Entity::IS_CVV_OPTIONAL] === true))
        {
            return true;
        }

        return false;
    }

    public function getCardInputFromCryptogram($cryptgram, $card, $input)
    {
        $input = [
            Card\Entity::NUMBER           => $cryptgram['token_number'],
            Card\Entity::NAME             => $card->getName(),
            Card\Entity::EXPIRY_MONTH     => $cryptgram['token_expiry_month'],
            Card\Entity::EXPIRY_YEAR      => $cryptgram['token_expiry_year'],
            Card\Entity::CRYPTOGRAM_VALUE => $cryptgram['cryptogram_value'],
            Card\Entity::TOKENISED        => 1,
            Card\Entity::VAULT            => "rzpvault",
            CARD\Entity::IS_CVV_OPTIONAL  => false,
            Card\Entity::CVV              => $input['card']['cvv'] ?? "123", // adding dummy cvv
            Card\Entity::TOKEN_PROVIDER   => 'Razorpay',
        ];

        return $input;
    }

    public function fillCardDetailsWithVaultToken($input): array
    {
        $cardNumber = null;

        $vaultToken = $input[Entity::TOKEN];

        try
        {
            $cardVault = (new Card\CardVault);

            $cardNumber = $cardVault->getCardNumber($vaultToken);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'token'         => $vaultToken,
                    'message'       => 'failed to get card number from vault token'
                ]
            );

            throw $e;
        }

        //Add card number
        $input[Entity::NUMBER] = $cardNumber;

        //Unset vault token as we have fetched card number
        unset($input[Entity::TOKEN]);

        //fetch other card details like expiry month/year etc. from vault token.
        $card = $this->repo->card->fetchLatestCardWithVaultTokenOnly($vaultToken);

        if (isset($card) === false)
        {
            $this->trace->info(
                TraceCode::CARD_FETCH_WITH_VAULT_TOKEN_FAILED,
                [
                    'token'         => $vaultToken,
                    'message'       => 'failed to fetch card entity from vault token',
                ]
            );

            return $input;
        }

        //fill card details into input array
        if (isset($card[Card\Entity::NAME]) === true)
        {
            $input[Card\Entity::NAME] = $card[Card\Entity::NAME];
        }

        if (isset($card[Card\Entity::EXPIRY_MONTH]) === true)
        {
            $input[Card\Entity::EXPIRY_MONTH] = $card[Card\Entity::EXPIRY_MONTH];
        }

        if (isset($card[Card\Entity::EXPIRY_YEAR]) === true)
        {
            $input[Card\Entity::EXPIRY_YEAR] = $card[Card\Entity::EXPIRY_YEAR];
        }

        return $input;
    }

    protected function getTokenizedCardResponseFromVault($input, $merchant)
    {
        $iinNumber  = substr($input['card']['number'] ?? null, 0, 6);

        $iin = $this->repo->card->retrieveIinDetails($iinNumber);

        $iinInfo = [
            'issuer'       => $iin->getIssuer(),
            'network'      => $iin->getNetwork(),
            'network_code' => $iin->getNetworkCode(),
            'iin'          => $iinNumber,
            'category'     => $iin->getCategory(),
            'type'         => $iin->getType(),
            'country'      => $iin->getCountry(),
            'issuer_name'  => $iin->getIssuerName(),
        ];

        $cardVault = (new Card\CardVault);

        return $cardVault ->createTokenizedCard($input, $merchant, $iinInfo);
    }

    protected function getTokenizedCardResponseFromAnExistingVault($card, $merchant, $input)
    {
        $iin = $this->repo->card->retrieveIinDetails($card->getIin());

        $iinInfo = [
            'issuer'       => $iin->getIssuer(),
            'network'      => $iin->getNetwork(),
            'network_code' => $iin->getNetworkCode(),
            'iin'          => $iin->getIin(),
            'category'     => $iin->getCategory(),
            'type'         => $iin->getType(),
            'country'      => $iin->getCountry(),
            'issuer_name'  => $iin->getIssuerName(),
        ];

        $cardVault = (new Card\CardVault);

        return $cardVault ->migrateToTokenizedCard($card, $merchant, $iinInfo, $input);
    }

    protected function getCryptogramResponseFromVault($serviceProviderTokenId, $merchant)
    {
        $cardVault = (new Card\CardVault);

        return $cardVault->fetchCryptogram($serviceProviderTokenId, $merchant);
    }

    protected function getCryptogramResponse($vaultToken, $merchant)
    {
        $cardVault = (new Card\CardVault);

        return $cardVault->fetchCryptogramFromVaultToken($vaultToken, $merchant);
    }

    protected function getTokenResponseFromVault($card)
    {
        $cardVault = (new Card\CardVault);

        $cardVaultToken = $card->getVaultToken();

        return $cardVault->fetchToken($cardVaultToken);
    }

    protected function deleteTokenResponseFromVault($card)
    {
        $cardVault = (new Card\CardVault);

        $cardVaultToken = $card->getVaultToken();

        return $cardVault->deleteNetworkToken($cardVaultToken);
    }

    public function updateCardWithTokenData($id, $tokenData)
    {
        $updateData = [];

        if(empty($tokenData['iin']) == false)
        {
            $updateData[Card\Entity::TOKEN_IIN] = $tokenData['iin'];
        }

        if((empty($tokenData['expiry_year']) == false) &&
            (empty($tokenData['expiry_month']) == false))
        {
            $updateData[Card\Entity::TOKEN_EXPIRY_MONTH] = $tokenData['expiry_month'];

            $expiryYear = $tokenData['expiry_year'];

            $updateData[Card\Entity::TOKEN_EXPIRY_YEAR]  = $expiryYear;

            if (strlen($expiryYear) === 2)
            {
                $updateData[Card\Entity::TOKEN_EXPIRY_YEAR] = '20' . $expiryYear;
            }
        }

        if(empty($updateData) === false)
        {
            $rowsAffected = $this->repo->card->updateById($id, $updateData);

            if ($rowsAffected === 0)
            {
                throw new Exception\BadRequestException(\RZP\Error\P2p\ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
                    'card',
                    ['data' => $tokenData]
                );
            }
        }
    }

    protected function isPresent($array, $param)
    {
        if(array_key_exists($param, $array) &&
            !($array[$param] === null || $array[$param] === 0 || $array[$param] === ''))
        {
            return true;
        }

        return false;
    }

    protected function getCardForIin($tokenizedCard, $inputCard)
    {
        $card = $tokenizedCard;

        if (array_key_exists('iin', $inputCard))
        {
            $card[Card\Entity::IIN] = $inputCard[Card\Entity::IIN];

        }
        else
        {
            $card[Card\Entity::IIN] = substr($inputCard['number'] ?? 0, 0, 6);
        }

        $card[Card\Entity::EXPIRY_MONTH] = $inputCard[Card\Entity::EXPIRY_MONTH];

        $card[Card\Entity::EXPIRY_YEAR] = $inputCard[Card\Entity::EXPIRY_YEAR];

        return $card;
    }

    protected function getLast4($input, $response)
    {
        $default = "0000";

        if (empty($response['last4']) === false)
        {
            return $response['last4'];
        }

        if (empty($input['number']) === false)
        {
            return substr($input['number'] ?? null, -4);
        }

        if (empty($input['last4']) === false)
        {
            return $input['last4'];
        }

        return $default;
    }

    public function createCardWithContactName($input, $merchant, $recurring = false, $dummyProcessing = false)
    {
        $card = (new Card\Entity);

        $this->customBuild($card, $input, 'createWithContact');

        $this->setVaultTokenAndFingerPrint($card, $input, $recurring);

        $card->merchant()->associate($merchant);

        $this->card = $card;

        $iin = $this->fillNetworkDetails($card, $input);

        if (empty($iin) === false)
        {
            $card->iinRelation()->associate($iin);
        }

        if ($dummyProcessing === false)
        {
            $this->repo->saveOrFail($card);
        }

        return $card;
    }

    public function customBuild($card, $input, $operation)
    {
        $card->modify($input);

        $card->validateInput($operation, $input);

        $card->generate($input);

        $card->unsetInput('create', $input);

        $card->fill($input);
    }
}
