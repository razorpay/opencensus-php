<?php

namespace RZP\Models\Card;

use Illuminate\Support\Str;
use Route;

use RZP\Exception;
use RZP\Trace\Tracer;
use RZP\Jobs\ParAsyncTokenisationJob;
use RZP\Jobs\SavedCardTokenisationJob;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Models\Card\IIN;
use RZP\Models\Feature;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Customer\Token;
use RZP\Models\FundTransfer;
use RZP\Models\FundAccount;
use RZP\Constants\HyperTrace;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Beneficiary;
use RZP\Models\FundAccount\Type as FundAccountType;

class Core extends Base\Core
{
    const TEMPORARY_VAULT_TOKEN_REGEX = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-'.
                                        '[0-9a-f]{4}-[0-9a-f]{12}|[0-9a-f]{12}4[0-9a-f]{19}$/';

    protected $card = null;

    public function create($input, $merchant, $recurring = false, $dummyProcessing = false, $isRzpX = false)
    {
        /**
         * When s2s merchant initiates the payment using token pan and cryptogram ,
         * they will send the token pan 's  expiry month  and expiry year in expiry_month and expiry_year
         * but we have to store it in token_expiry_month and token_expiry_year and setting dummy value in expiry_month
         * and expiry_year when merchant initiates the payment using razorpay token / from checkout ,
         * we are sending  expiry month and year in token_expiry_month and token_expiry_year
         *
         * isRzpX flag here distinguishes between X and PG flows
        */
        if ($this->setTokenExpiryMonthAndYear($input, $isRzpX) === true)
        {
            if (empty($input[Card\Entity::TOKEN_EXPIRY_MONTH]) === true)
            {
                $input[Card\Entity::TOKEN_EXPIRY_MONTH] = $input[Card\Entity::EXPIRY_MONTH];
                $input[Card\Entity::EXPIRY_MONTH]       = '0';
            }
            if (empty($input[Card\Entity::TOKEN_EXPIRY_YEAR]) === true)
            {
                $input[Card\Entity::TOKEN_EXPIRY_YEAR] = $input[Card\Entity::EXPIRY_YEAR];
                $input[Card\Entity::EXPIRY_YEAR]       = '9999';
            }

            // this will set card's iin field with actual card bin not with the token pan's iin
            $input[Card\Entity::IS_TOKENIZED_CARD] = true;
        }

        $card = (new Card\Entity)->build($input);

        $card->merchant()->associate($merchant);

        $this->card = $card;

        $iin = $this->fillNetworkDetails($card, $input);

        if (empty($iin) === false)
        {
            $card->iinRelation()->associate($iin);
        }

        /**
         * Allow setting of vault token and fingerprint if it is the PG flow
         *
         * For X flow, if dummy processing is true, it means it is High TPS flow
         * and we need not create KMS vault token with a TTL in ingress
         *
         * if dummy processing is false, we set vault token and fingerprint and
         * check if the vault token generated is in accordance with the flow
         */
        if ($isRzpX === false)
        {
            $this->setVaultTokenAndFingerPrint($card, $input, $recurring);
        }
        else
        {
            if ($dummyProcessing === false)
            {
                $this->setVaultTokenAndFingerPrint($card, $input, $recurring, $isRzpX);

                $this->checkIfCardHasProperVaultToken($card->getVaultToken(), $input);
            }
        }

        $this->saveCardMetaData($card, $input, $isRzpX);

        if ($dummyProcessing === false)
        {
            $this->repo->saveOrFail($card);

            $this->saveParValue($card, $input);
        }

        return $card;
    }

    public function saveParValue($card, $input = null)
    {
        if(($this->checkIfFetchingParApplicable($card->getNetwork())) === false)
        {
            return;
        }

        try {

            $id = UniqueIdEntity::generateUniqueId(); // Need to generate random string because we don't have access to task id, Also need to add random string generator for this

            $variant = $this->app->razorx->getTreatment($id, Merchant\RazorxTreatment::PAR_ASYNC_FOR_CARD_FINGERPRINT, $this->mode);

            $this->trace->info(TraceCode::PAR_ASYNC_JOB, [
                "variant" => $variant
            ]);

            if (strtolower($variant) === "on") {

                $this->trace->info(TraceCode::ASYNC_FETCH_PAR_RAZORX_VARIANT, [
                    'razorx_variant' => $variant,
                ]);

                ParAsyncTokenisationJob::dispatch($this->mode, $card->getId(), $input["number"]);

            }
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::ERROR_EXCEPTION, [
                "card_id" => $card->getId(),
                "network" => $card->getNetwork()
            ]);
        }
    }

    public function migrateToTokenizedCard($card, $merchant, $input, $payment = null, $asyncTokenisationJobId)
    {
        $response = $this->getTokenizedCardResponseFromAnExistingVault($card, $merchant, $input);

        return $this->migrationCardToTokenisedCard($card, $input, $merchant, $response, $payment, $asyncTokenisationJobId);
    }

    public function fetchParValue($input)
    {
        return (new Card\CardVault)->fetchParValueFromVault($input);
    }

    public function createTokenizedCard($input, $merchant)
    {
        $response = $this->getTokenizedCardResponseFromVault($input, $merchant);

        return $this->createTokenizedCardEntity($input['card'], $merchant, $response);
    }


    protected function migrationCardToTokenisedCard($card, $input, $merchant, $response, $payment = null, $asyncTokenisationJobId)
    {
        if($asyncTokenisationJobId === "pushtokenmigrate" ) {

            $tokenisedCard = $card;

        } else {

            $tokenisedCard = $card->replicate();

            $tokenisedCard->generateID();
        }


        $tokenisedCard->setVaultToken($response['token']);

        $tokenisedCard->setGlobalFingerprint($response['fingerprint']);

        $providerReferenceId = null;

        $noOfTokens = count($response['service_provider_tokens']);

        // fetching providerReferenceId from token entity
        foreach ($response['service_provider_tokens'] as $token) {
            if ($token['provider_type'] === "network" && empty($token['provider_data']['providerReferenceId'] === false)) {
                $providerReferenceId = $token['provider_data']['providerReferenceId'];
            }
        }

        if(isset($payment) && $providerReferenceId != null)
        {
            $payment->card->setProviderReferenceId($providerReferenceId);

            $this->repo->saveOrFail($payment->card);
        }

        if($providerReferenceId === null)
        {
            $this->trace->info(TraceCode::TRACE_EMPTY_PROVIDER_REFERENCE,
                [
                    'card_id' => $card->getId()
                ]
            );
        }

        if(empty($response['service_provider_tokens']) === false)
        {
            if ($noOfTokens > 1)
            {
                $tokenisedCard->setVault(Card\Vault::PROVIDERS);
                $tokenResponse = $this->getTokenIIN($response['service_provider_tokens']);
                $tokenisedCard->setTokenIin($tokenResponse[0]);
            }
            else
            {
                $tokenisedCard->setVault(strtolower($response['service_provider_tokens'][0]['provider_name']));
                if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_number')){
                    $tokenisedCard->setTokenIin(substr($response['service_provider_tokens'][0]['provider_data']['token_number'], 0, 9));
                }
            }

            if ($noOfTokens > 0) {

                $expiry_token = $this->getDualTokenMaxExpiry($response['service_provider_tokens']);
                $tokenisedCard->setTokenExpiryYear($expiry_token[0]);
                $tokenisedCard->setTokenExpiryMonth($expiry_token[1]);
            }
            $this->repo->saveOrFail($tokenisedCard);
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
            $noOfTokens = count($response['service_provider_tokens']);

            if ($noOfTokens > 1)
            {
                $createInput[Card\Entity::VAULT] = Card\Vault::PROVIDERS;

                $tokenResponse = $this->getTokenIIN($response['service_provider_tokens']);
                $createInput[Card\Entity::TOKEN_IIN] = $tokenResponse[0];
                $createInput[Card\Entity::LENGTH] = $tokenResponse[2];
            }

            else
            {
                $createInput[Card\Entity::VAULT] = strtolower($response['service_provider_tokens'][0]['provider_name']);
                if($this->isPresent($response['service_provider_tokens'][0]['provider_data'], 'token_number'))
                {
                    $createInput[Card\Entity::TOKEN_IIN] = substr($response['service_provider_tokens'][0]['provider_data']['token_number'], 0, 9);
                    $createInput[Card\Entity::LENGTH] = strlen($response['service_provider_tokens'][0]['provider_data']['token_number']);
                }
                else
                {
                    $response['service_provider_tokens'][0]['provider_data']['token_iin'] = null;
                }
            }

            if ($noOfTokens > 0)
            {
                $expiry_token = $this->getDualTokenMaxExpiry($response['service_provider_tokens']);

                $createInput[Card\Entity::TOKEN_EXPIRY_YEAR] = $expiry_token[0];

                $createInput[Card\Entity::TOKEN_EXPIRY_MONTH] = $expiry_token[1];
            }
        }
        $tokenizedCard = (new Card\Entity)->buildCard($createInput, 'tokenizedCard');

        $tokenizedCard->merchant()->associate($merchant);

        $card = $this->getCardForIin($tokenizedCard, $input);

        // this is to update token iin incase of s2s merchants;
        $input[Card\Entity::TOKENISED] = true;

        $iin = $this->fillNetworkDetails($card, $input);

        if (empty($iin) === false)
        {
            $tokenizedCard->iinRelation()->associate($iin);
        }

        return [$tokenizedCard, $response['service_provider_tokens']];
    }

    public function getTokenIIN($serviceProviderArray) {
        $tokenIIN = 000000000;
        $tokenLast4 = 0000;
        $tokenLen = 0;
        foreach ($serviceProviderArray as $data) {
            if ($data['provider_type'] == "network" and $this->isPresent($data['provider_data'],'token_number')) {
                $tokenIIN = substr($data['provider_data']['token_number'],0,9);
                $tokenLast4 = substr($data['provider_data']['token_number'],-4);
            }
        }
        $response = array();
        array_push($response, $tokenIIN);
        array_push($response, $tokenLast4);
        array_push($response, $tokenLen);
        return $response;
    }

    public function fetchCryptogram($serviceProviderTokenId, $merchant)
    {
        return $this->getCryptogramResponseFromVault($serviceProviderTokenId, $merchant);
    }

    public function fetchCryptogramForVaultToken($vaultToken, $merchant)
    {
        return $this->getCryptogramResponse($vaultToken, $merchant);
    }

    public function fetchToken($card, $internalServiceRequest)
    {
        return $this->getTokenResponseFromVault($card, $internalServiceRequest);
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

        $this->saveParValue($card, $input);

        $card = $this->repo->card->getCardById($card->getId());

        return $card;
    }

    public function createForFundAccount($input, $merchant, $compositePayoutSaveOrFail)
    {
        $input[Card\Entity::VAULT] = Card\Vault::RZP_VAULT;

        /**
         * We are sending negation of compositePayoutSaveOrFail because to save the card entity
         * dummy processing needs to be false
         *
         * Here isRzpX flag is sent as true to indicate X flow
         */
        $card = $this->create($input, $merchant, false, !$compositePayoutSaveOrFail, true);

        $this->checkIfCardIsSupportedAndEnqueueForBeneficiaryRegistration($card, $merchant, $compositePayoutSaveOrFail);

        return $card;
    }

    public function checkIfCardHasProperVaultToken($vaultToken, $input)
    {
        if ((isset($input[Card\Entity::TOKENISED]) === true) and
            (isset($vaultToken) === true))
        {
            $isTempVaultToken = $this->checkIfVaultTokenIsTemporary($vaultToken);

            $tokenised = $input[Card\Entity::TOKENISED];

            if (($tokenised xor $isTempVaultToken) === false)
            {
                $this->trace->error(TraceCode::INVALID_VAULT_TOKEN_ASSOCIATED,
                    [
                        'vault_token'        => $vaultToken,
                        'is_temporary_token' => $isTempVaultToken,
                        'is_tokenised'       => $tokenised
                    ]);

                $this->trace->count(
                    Metric::INVALID_VAULT_TOKEN_ASSOCIATED,
                    [Metric::LABEL_IS_TOKENISED => $tokenised]
                );

                Tracer::startSpanWithAttributes(HyperTrace::INVALID_VAULT_TOKEN_ASSOCIATED,
                    [
                        Metric::LABEL_IS_TOKENISED => $tokenised
                    ]);

                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                    null,
                    [
                        'card_'.Card\Entity::VAULT_TOKEN => $vaultToken,
                    ]
                );
            }
        }
    }

    /**
     * Logic for temporary vault token with TTL confirmed from Vault team
     *
     * @param $vaultToken
     *
     * @return bool
     */
    public function checkIfVaultTokenIsTemporary($vaultToken)
    {
        $values = explode("_", $vaultToken);

        if (count($values) != 2)
        {
            return false;
        }

        if (preg_match(self::TEMPORARY_VAULT_TOKEN_REGEX, $values[1], $matches) === 1)
        {
            return true;
        }

        return false;
    }

    public function checkIfCardIsSupportedAndEnqueueForBeneficiaryRegistration($card,
                                                                               $merchant,
                                                                               $compositePayoutSaveOrFail = true)
    {
        $cardType       = $card->getType();
        $cardIssuer     = $card->getIssuer();
        $cardVaultToken = $card->getCardVaultToken();
        $cardNetwork    = $card->getNetwork();
        $tokenIin       = $card->getTokenIin();
        $cardIin        = $card->getIin();

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
        $m2pSupportedModeConfigs = (new FundTransfer\Mode)->getM2PSupportedChannelModeConfig($cardIssuer, $cardNetwork, $cardType, $cardIin);

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

            return;
        }

        // If a credit card is not supported for IMPS, NEFT, we check if m2p supports it.
        // If it does, we allow FA creation.
        if (($this->checkIfVaultTokenIsNull($card, $compositePayoutSaveOrFail) === true) or
            (Type::isValidFundAccountCardType($cardType, $prepaidCardVariant) === false) or
            ((in_array($cardIssuer, FundTransfer\Mode::getSupportedIssuers(), true) === false) and
             (empty($m2pSupportedModeConfigs) === true)))
        {

            if (($card->isAmex() === true) and
                ($cardIssuer === null))
            {
                return;
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

        // This check is needed since the tokenIIN <> card IIN mapping might have changed and earlier
        // cards created might not be supported any more
        if ((empty($tokenIin) === false) and
            ($cardIin === substr($tokenIin,0,6)))
        {
            $this->trace->error(TraceCode::CARD_BIN_NOT_FOUND_FOR_TOKEN_PAN,
                                 [
                                     'token_iin'    => $tokenIin,
                                     'is_tokenised' => true
                                 ]);

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_CARD_NOT_SUPPORTED_FOR_FUND_ACCOUNT,
                null,
                [],
                "Token not supported for fund account creation."
            );
        }

        $isTokenised = ($card->isTokenPan() === true) ? true : $card->isNetworkTokenisedCard();

        // Only enqueue for beneficiary registration for non saved card flow
        if ($isTokenised === false)
        {
            (new Beneficiary)->enqueueForBeneficiaryRegistration($card, FundAccountType::CARD);
        }
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

        // set dummy cvv for tokenised Visa via cryptogram cvvless flow
        if ($card->isVisa() && boolval($input[Card\Entity::TOKENISED]) === true
            && empty($input[Card\Entity::CVV]) === true) {

            $input['cvv'] = '123';

            $this->trace->info(
                TraceCode::CVV_OPTIONAL,
                [
                    'message'       => 'Setting cvv to dummy value',
                ]
            );
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

    public function setVaultTokenAndFingerPrint(Card\Entity $card, array $input, bool $recurring, bool $isRzpX = false)
    {
        if (empty($card->getVault()) === true)
        {
            return;
        }

        try
        {
            $cardVault = (new Card\CardVault);

            $tempInput['bu_namespace'] = $cardVault->getBuNamespaceIfApplicable($card->toArray(), $isRzpX);

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

    public function saveCardMetaData(Card\Entity $card, array $input, $isRzpX = false)
    {
        $cardMetaData = [];

        try
        {
            $cardVault    = (new Card\CardVault);

            $cardMetaData = $cardVault->saveCardMetaData($card, $input, $isRzpX);

            $card->setCardMetaData($cardMetaData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::VAULT_CARD_METADATA_SAVE_FAILED
            );


            if ( $isRzpX === true)
            {
                $this->trace->count(Metric::VAULT_CARD_METADATA_SAVE_FAILED,
                                    [
                                        'message' => $e->getMessage()
                                    ]);
            }

        }

        return $cardMetaData;
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
        $iinNumber = $card->getAttributes()[Card\Entity::IIN];

        if ((empty($input[Card\Entity::TOKENISED]) === false) and
            (boolval($input[Card\Entity::TOKENISED]) === true) and
            (array_key_exists('number', $input) === true))
        {
            $tokenizedRange = substr($input['number'], 0, 9);

            $iinNumber = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange) ?? $iinNumber;

            if(isset($input['token']) && $input['token']!=="" && isset($card['iin']) && $card['iin']!=="" && substr($tokenizedRange,0,6) === $card['iin'])
            {
                $token_entity = (new Token\Core)->getByTokenIdAndMerchant($input['token'], $this->merchant);

                if(isset($token_entity["card_id"]))
                {
                    $network_card = $this->repo->card->getCardById($token_entity["card_id"]);

                    $tokenCardIin =  $network_card->getIin();

                    $updatedIin   =  strlen($tokenCardIin) === 6 ? $tokenCardIin :$iinNumber ;
                    $card['iin']  =  $updatedIin;
                    $iinNumber    =  $updatedIin;
                }
            }
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
                    []);
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

            $isInternational =  IIN\IIN::isInternational($iin->getCountry(), $card->merchant->getCountry());

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

        if ($input[Card\Entity::NUMBER] === Card\Entity::DUMMY_AXIS_TOKENHQ_CARD)
        {
            $card->setTokenIIn('999999');
            $card->setAttribute(Card\Entity::TOKEN_LAST_4, null);

            if (empty($network_card) === false)
            {
                $card->setType($network_card['type']);
                $card->setSubType($network_card['sub_type']);
                $card->setNetwork($network_card['network']);
                $card->setCategory($network_card['category']);

                $arr = [
                    Entity::ISSUER => $network_card['issuer'],
                    Entity::COUNTRY => $network_card['country'],
                    Entity::INTERNATIONAL => $network_card->isInternational(),
                    Entity::EMI => $network_card->getEmi(),
                ];

                $card->fill($arr);
            }
        }

        return $iin;
    }

    protected function traceMissingIin($card)
    {
        $data = [
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

        if (empty($input['cvv']) && $card->getTrivia() === '1' &&
            ($card->isVisa() === true || $card->isRuPay() === true || $card->isMasterCard() == true || $card->isAmex() == true))
        {
            $this->trace->info(
                TraceCode::CVV_OPTIONAL, []);
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
        // Need to use ExternalRepo findOrFail function.
        // Find is not overriden. Replicating same behaviour by silencing exception
        try
        {
            return $this->repo->card->findOrFail($id);
        }
        catch (\Throwable $exception) {}

        return null;
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

    public function getCardInputFromCryptogram($cryptogram, $card, $input, $recurringTokenNumber = null)
    {
        $input = [
            Card\Entity::NUMBER                 => $cryptogram['token_number'] ?? $cryptogram['card']['number'],
            Card\Entity::NAME                   => $card->getName(),
            Card\Entity::TOKEN_EXPIRY_MONTH     => $cryptogram['token_expiry_month'] ?? null,
            Card\Entity::TOKEN_EXPIRY_YEAR      => $cryptogram['token_expiry_year'] ?? null,
            Card\Entity::EXPIRY_MONTH           => '0',
            Card\Entity::EXPIRY_YEAR            => '9999',
            Card\Entity::LAST4                  => $card->getLast4(),
            Card\Entity::CRYPTOGRAM_VALUE       => $cryptogram['cryptogram_value'] ?? null,
            Card\Entity::TOKENISED              => true,
            Card\Entity::VAULT                  => "rzpvault",
            CARD\Entity::IS_CVV_OPTIONAL        => false,
            Card\Entity::CVV                    => $input['card']['cvv'] ?? "123", // adding dummy cvv
            Card\Entity::TOKEN_PROVIDER         => 'Razorpay',
            Card\Entity::TOKEN                  => $input['token'] ?? "",
        ];

        // override empty cvv with dummy cvv for cvvless
        if (Card\Network::getFullName(Network::VISA) === $card->getNetwork()
            && boolval($input[Card\Entity::TOKENISED]) === true
            && empty($input[Card\Entity::CVV]) === true) {

            $input[Card\Entity::CVV ] = "123";

            $this->trace->info(
                TraceCode::CVV_OPTIONAL,
                [
                    'message'       => 'Setting cvv to dummy value',
                ]
            );
        }


        if ( $card->getVault() === Card\Vault::HDFC)
        {
            $input[Card\Entity::TOKEN_EXPIRY_MONTH ] = $cryptogram['card']['expiry_month'] ?? null;
            $input[Card\Entity::TOKEN_EXPIRY_YEAR ] =  $cryptogram['card']['expiry_year'] ?? null;
        }

        if ($card->getVault() === Card\Vault::AXIS || ($card->getVault() === Card\Vault::PROVIDERS && $cryptogram === null && $card->getIssuer() === Card\Issuer::UTIB)) {
            $input[Card\Entity::NUMBER] = Card\Entity::DUMMY_AXIS_TOKENHQ_CARD;
        }

        if($recurringTokenNumber !== null)
        {
            $input = array_merge($input, [
                Card\Entity::NUMBER                 => $recurringTokenNumber,
                Card\Entity::TOKEN_EXPIRY_MONTH     => $card->getTokenExpiryMonth() ?? null,
                Card\Entity::TOKEN_EXPIRY_YEAR      => $card->getTokenExpiryYear()?? null,
            ]);
        }

        if(isset($cryptogram["cvv"]) === true && Card\Network::getFullName(Network::AMEX) === $card->getNetwork())
        {
            $input["cvv"] = $cryptogram["cvv"];
        }

        return $input;
    }

    public function fillCardDetailsWithVaultToken($input, $merchant): array
    {
        $vaultToken = $input[Entity::TOKEN];

        $inputType = $input[Entity::INPUT_TYPE] ?? Card\InputType::CARD;

        $network = (isset($input[Entity::NETWORK]) === true) ? Network::getFullName($input[Entity::NETWORK]) : null;

        $additionalInput = [
            Card\Entity::NETWORK       => $network,
            Card\Entity::INTERNATIONAL => $input[Entity::INTERNATIONAL] ?? null,
            Card\Entity::TRIVIA        => $input[Entity::TRIVIA] ?? null
        ];

        return $this->getCardInputBasedOnInputType($inputType, $vaultToken, $merchant, $additionalInput);
    }

    public function getCardInputBasedOnInputType($inputType, $vaultToken, $merchant, $additionalInput)
    {
        $cardInput = [];

        switch ($inputType)
        {
            case Card\InputType::CARD:
                $cardInput = $this->getCardInputForCardInputType($vaultToken, $additionalInput, $inputType);

                break;

            case Card\InputType::SERVICE_PROVIDER_TOKEN:
                $cardInput = $this->getCardInputForServiceProviderTokenInputType($vaultToken, $additionalInput, $inputType);

                break;

            case Card\InputType::RAZORPAY_TOKEN:
                $cardInput = $this->getCardInputForRazorpayTokenInputType($vaultToken, $merchant, $inputType);

                break;

            default:
                throw new Exception\LogicException('Invalid Input type');
        }

        $this->fillAdditionalDetailsFromCardEntity($cardInput, $vaultToken);

        return $cardInput;
    }

    protected function getCardInputForCardInputType($vaultToken, $additionalInput, $inputType)
    {
        $cardNumber = null;

        try
        {
            $cardNumber = (new Card\CardVault)->getCardNumber($vaultToken, $additionalInput);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'token'         => $vaultToken,
                    'message'       => 'failed to get card number from vault token',
                    'input_type'    => $inputType
                ]
            );

            throw $e;
        }

        return [
            Card\Entity::NUMBER     => $cardNumber,
            Card\Entity::INPUT_TYPE => $inputType
        ];
    }

    protected function getCardInputForServiceProviderTokenInputType($vaultToken, $additionalInput, $inputType)
    {
        $tokenNumber = null;

        try
        {
            $tokenNumber = (new Card\CardVault)->getCardNumber($vaultToken, $additionalInput);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'token'         => $vaultToken,
                    'message'       => 'failed to get token number from vault token',
                    'input_type'    => $inputType
                ]
            );

            throw $e;
        }

        return [
            Card\Entity::NUMBER         => $tokenNumber,
            Card\Entity::INPUT_TYPE     => $inputType,
            Card\Entity::TOKEN_PROVIDER => Constants::REFUNDS
        ];
    }

    protected function getCardInputForRazorpayTokenInputType($vaultToken, $merchant, $inputType)
    {
        $response = null;

        try
        {
            $response = (new Card\CardVault)->fetchCryptogram($vaultToken, $merchant, true);

            $this->updateProviderFields($response);

            $this->validateProviderFields($response);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::CRITICAL,
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'token'         => $vaultToken,
                    'message'       => 'failed to get token number from vault token',
                    'input_type'    => $inputType
                ]
            );

            throw $e;
        }

        $providerData = $response[Token\Entity::SERVICE_PROVIDER_TOKENS][0][Token\Entity::PROVIDER_DATA];

        $cardInput = [
            Card\Entity::NUMBER         => $providerData[Token\Entity::TOKEN_NUMBER],
            Card\Entity::INPUT_TYPE     => Card\InputType::SERVICE_PROVIDER_TOKEN,
            Card\Entity::TOKEN_PROVIDER => Constants::REFUNDS
        ];

        // Consume token_expiry dates from fetchCryptogram API if and only both are present in the response.
        if ((isset($providerData[Card\Entity::TOKEN_EXPIRY_MONTH]) === true) and
            (isset($providerData[Card\Entity::TOKEN_EXPIRY_YEAR]) === true))
        {
            $cardInput[Card\Entity::EXPIRY_MONTH] = $providerData[Card\Entity::TOKEN_EXPIRY_MONTH];
            $cardInput[Card\Entity::EXPIRY_YEAR]  = $providerData[Card\Entity::TOKEN_EXPIRY_YEAR];
        }

        return $cardInput;
    }

    protected function fillAdditionalDetailsFromCardEntity(&$cardInput, $vaultToken)
    {
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

            return;
        }

        //fill card details into input array
        if (empty($card[Card\Entity::NAME]) === false)
        {
            $cardInput[Card\Entity::NAME] = $card[Card\Entity::NAME];
        }

        // Rerouting all the Scrooge flows to either card or Service_provider_token input_type flow.
        switch($cardInput[Card\Entity::INPUT_TYPE])
        {
            case Card\InputType::CARD:
                if (isset($card[Card\Entity::EXPIRY_MONTH]) === true)
                {
                    $cardInput[Card\Entity::EXPIRY_MONTH] = $card[Card\Entity::EXPIRY_MONTH];
                }

                if (isset($card[Card\Entity::EXPIRY_YEAR]) === true)
                {
                    $cardInput[Card\Entity::EXPIRY_YEAR] = $card[Card\Entity::EXPIRY_YEAR];
                }

                break;

            case Card\InputType::SERVICE_PROVIDER_TOKEN:
                if ((isset($card[Card\Entity::TOKEN_EXPIRY_MONTH]) === true) and
                    (isset($cardInput[Card\Entity::EXPIRY_MONTH]) === false))
                {
                    $cardInput[Card\Entity::EXPIRY_MONTH] = $card[Card\Entity::TOKEN_EXPIRY_MONTH];
                }

                if ((isset($card[Card\Entity::TOKEN_EXPIRY_YEAR]) === true) and
                    (isset($cardInput[Card\Entity::EXPIRY_YEAR]) === false))
                {
                    $cardInput[Card\Entity::EXPIRY_YEAR] = $card[Card\Entity::TOKEN_EXPIRY_YEAR];
                }

            break;
        }
    }

    protected function validateProviderFields($response)
    {
        if ((empty($response) === false) and
            (isset($response[Token\Entity::SERVICE_PROVIDER_TOKENS]) === true))
        {
            $serviceProviderTokens = $response[Token\Entity::SERVICE_PROVIDER_TOKENS];

            if (isset($serviceProviderTokens[0]['provider_data']) === true)
            {
                (new Validator())->setStrictFalse()->validateInput(
                    'fetch_cryptogram_provider_data', $serviceProviderTokens[0]['provider_data']);
            }
            else
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Provider Data is missing from service_provider_tokens.'
                );
            }
        }
        else
        {
            throw new Exception\BadRequestValidationFailureException(
                'Received invalid response from fetch Cryptogram API.'
            );
        }
    }

    protected function updateProviderFields(&$response)
    {
        if ((empty($response) === false) and
            (isset($response[Token\Entity::SERVICE_PROVIDER_TOKENS]) === true))
        {
            $serviceProviderTokens = $response[Token\Entity::SERVICE_PROVIDER_TOKENS];

            if (isset($serviceProviderTokens[0]['provider_data']) === true)
            {
                $providerData = &$serviceProviderTokens[0]['provider_data'];

                if ((isset($providerData['token_expiry_year'])) and
                    (strlen($providerData['token_expiry_year']) === 2))
                {
                    $providerData['token_expiry_year'] = '20' . $providerData['token_expiry_year'];
                }

                if (isset($providerData['token_expiry_month']))
                {
                    $providerData['token_expiry_month'] = ltrim($providerData['token_expiry_month'], '0');
                }
            }

            $response[Token\Entity::SERVICE_PROVIDER_TOKENS] = $serviceProviderTokens;
        }
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

    protected function getTokenResponseFromVault($card, $internalServiceRequest)
    {
        $cardVault = (new Card\CardVault);

        $cardVaultToken = $card->getVaultToken();

        return $cardVault->fetchToken($cardVaultToken, $internalServiceRequest);
    }

    protected function deleteTokenResponseFromVault($card)
    {
        $cardVault = (new Card\CardVault);

        $cardVaultToken = $card->getVaultToken();

        return $cardVault->deleteNetworkToken($cardVaultToken);
    }

    // TODO : Refactor later with cards team
    public function updateCardWithTokenData($id, $tokenData)
    {
        $updateData = [];

        $card = $this->getCardEntity($id);

        if (empty($tokenData['iin']) === false)
        {
            $card->setTokenIIN($tokenData['iin']);
            $updateData[Card\Entity::TOKEN_IIN] = $tokenData['iin'];
        }

        if ((empty($tokenData['expiry_year']) === false) and
            (empty($tokenData['expiry_month']) === false))
        {
            $updateData[Card\Entity::TOKEN_EXPIRY_MONTH] = $tokenData['expiry_month'];

            $card->setTokenExpiryMonth($tokenData['expiry_month']);

            $expiryYear = $tokenData['expiry_year'];

            $card->setTokenExpiryYear($expiryYear);

            $updateData[Card\Entity::TOKEN_EXPIRY_YEAR]  = $expiryYear;

            if (strlen($expiryYear) === 2)
            {
                $card->setTokenExpiryYear('20' . $expiryYear);
                $updateData[Card\Entity::TOKEN_EXPIRY_YEAR] = '20' . $expiryYear;
            }
        }

        if (empty($updateData) === false)
        {
            $rowsAffected = $this->repo->card->updateById($id, $updateData);

            $this->repo->saveOrFail($card);

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

    public function setTokenExpiryMonthAndYear($input, bool $isRzpX)
    {
        return (empty($input[Entity::TOKENISED]) === false and  boolval($input[Entity::TOKENISED]) === true);
    }

    public function checkIfVaultTokenIsNull($card, bool $compositePayoutSaveOrFail)
    {
        return ($card->getCardVaultToken() === null) and
               ($compositePayoutSaveOrFail === true);
    }

    public function addCardDetailsOfToken(
        Token\Entity $token,
        array &$cardsList,
        array &$merchantIdMapping
    ) : void
    {
        $card = $token->card;

        if ($card === null)
        {
            return;
        }

        // Fetching card details as a unique key for each token as card id will be different for each token
        // even if the tokens are of the same card
        $uniqueCardCombination = $card->getCardDetailsAsKeyForGrouping();

        if (array_key_exists($uniqueCardCombination, $cardsList) === false)
        {
            $cardDetail = $this->getCardDetails($card);

            $cardsList[$uniqueCardCombination] = $cardDetail;
        }

        $tokenDetails = [
            Token\Entity::ID          => $token->getId(),
            Token\Entity::CREATED_AT  => $token->getCreatedAt(),
            Token\Entity::MERCHANT_ID => $merchantIdMapping[$token->getMerchantId()],
        ];

        $cardsList[$uniqueCardCombination][Constants::TOKENS][] = $tokenDetails;
    }

    protected function getCardDetails(Card\Entity $card) : array
    {
        // We have decided not to expose the actual Card IDs due to security issues.
        // We would expose random IDs as Card ID as FE requires a unique value for each card entity.
        $cardId = Str::random(20);

        return [
            Token\Entity::CARD_ID => $cardId,
            Entity::LAST4         => $card->getLast4(),
            Entity::NETWORK       => $card->getNetwork(),
            Entity::TYPE          => $card->getType(),
            Entity::ISSUER        => $card->getIssuer(),
        ];
    }

    public function checkIfFetchingParApplicable($network, $isTokenized = null)
    {
        $network = strtolower($network);

        if($network === 'rupay' && $isTokenized === true)
        {
            return false;
        }

        if($network === 'visa' || $network === 'mastercard' || $network === 'rupay')
        {
            return true;
        }

        return false;
    }

    public function getCobrandingPartner($vaultToken, $tokenIin)
    {

        $card = [
            'token_iin'     => $tokenIin,
            'vault_token'   => $vaultToken,
        ];

        $card = (new Card\Entity)->fill($card);

        $cardActualIin = $card->getIin();

        $iinEntity = $this->repo->iin->find($cardActualIin);

        return $iinEntity->getCobrandingPartner();
    }

    protected function getDualTokenMaxExpiry($provider_data)
    {
        $expiry_year = '0000';
        $expiry_month = '00';
        foreach ($provider_data as $data) {
            if ($this->isPresent($data['provider_data'], 'token_expiry_month') &&
                $this->isPresent($data['provider_data'], 'token_expiry_year')) {

                if (strlen($data['provider_data']['token_expiry_year']) == 2){
                    $data['provider_data']['token_expiry_year'] = '20' . $data['provider_data']['token_expiry_year'];
                }

                if ($data['provider_data']['token_expiry_year'] > $expiry_year )
                {
                    $expiry_year = $data['provider_data']['token_expiry_year'];
                    $expiry_month = $data['provider_data']['token_expiry_month'];
                }
                else if ($data['provider_data']['token_expiry_year'] == $expiry_year) {
                    if ($data['provider_data']['token_expiry_month'] > $expiry_month){
                        $expiry_month = $data['provider_data']['token_expiry_month'];
                        $expiry_year = $data['provider_data']['token_expiry_year'];
                    }
                }
            }
        }
        $expiry_array = array();
        array_push($expiry_array, (int) $expiry_year);
        array_push($expiry_array, (int) $expiry_month);
        return $expiry_array;

    }
}
