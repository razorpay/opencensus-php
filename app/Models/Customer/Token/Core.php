<?php

namespace RZP\Models\Customer\Token;

use RZP\Constants;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Jobs\TokenActionsHandler;
use RZP\Models\Terminal;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Feature\Constants as Feature;

class Core extends Base\Core
{
    /**
     * TODO: merge create and this method
     * currently this needs to be in transaction as we are creating
     * new card entity as well with token creation without payment
     *
     * @param $customer
     * @param $input
     *
     * @return mixed
     */
    public function createDirectToken(Customer\Entity $customer, array $input)
    {
        (new Validator)->validateInput(Validator::CREATE_DIRECT, $input);

        $cardInput = $this->getCardInputForDirectToken($input);

        return $this->repo->transaction(
            function() use ($customer, $input, $cardInput)
            {
                //
                // Doing this only for cards for now.
                // Other types of tokens need to be thought out still.
                //

                $card = (new Card\Core)->create($cardInput, $customer->merchant);

                //
                // This is being done because if we don't do this, then the merchant
                // will not receive this in the response of fetch tokens because
                // we don't return back tokens which have never been used.
                //
                $input[Token\Entity::USED_AT] = Carbon::now()->getTimestamp();

                $token = $this->create($customer, $input, $card);

                return $token;
            });
    }

    /**
     * Below function is used to create token in payment flow where we
     * already have a card_id
     *
     * @param Customer\Entity  $customer
     * @param array            $input
     * @param Card\Entity|null $card
     * @param bool             $validateExisting
     *
     * @return Entity
     */
    public function create($customer, $input, Card\Entity $card = null, bool $validateExisting = true)
    {
        $traceInput = $input;
        unset($traceInput[Entity::AADHAAR_NUMBER]);
        unset($traceInput[Entity::ACCOUNT_NUMBER]);

        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_CREATE,
            [
                'customer_id' => $customer->getId(),
                'input'       => $traceInput
            ]
        );

        $token = new Token\Entity;

        if (isset($input[Token\Entity::CARD_ID]) === true)
        {
            $card = $this->repo->card->findOrFailPublic($input[Token\Entity::CARD_ID]);
        }

        //
        // This is here because we are doing
        // a terminal check later in the flow.
        //
        $terminal = null;

        if (isset($input[Token\Entity::TERMINAL_ID]))
        {
            //
            // This if block gets run only in case of wallet currently. + nach migration
            //

            $terminal = $this->repo->terminal->findOrFail($input[Token\Entity::TERMINAL_ID]);

            unset($input[Token\Entity::TERMINAL_ID]);
        }

        //
        // This is basically being used only
        // for creation of direct tokens
        //
        if (isset($input[Token\Entity::USED_AT]) === true)
        {
           $token->setUsedAt($input[Token\Entity::USED_AT]);

           unset($input[Token\Entity::USED_AT]);
        }

        //
        // This should be before associations because if defaults for the
        // foreign entities are present as null in the entity class
        // and if the association is done before the build, the
        // association will get overridden as null.
        //
        $token->build($input);

        if ($card !== null)
        {
            //
            // This is being done here and not in the above card block
            // because this function can accept a card also and we have
            // to set expiry time even then. Like duh.
            //
            if ($token->getExpiredAt() === null)
            {
                $token->setExpiredAt($card->getExpiryTimestamp());
            }

            $token->card()->associate($card);
        }

        if ($terminal !== null)
        {
            $token->terminal()->associate($terminal);
        }

        $token->customer()->associate($customer);

        $token->merchant()->associate($customer->merchant);

        if ($validateExisting === true)
        {
            $existingToken = $this->validateExistingToken($token);

            //
            // For cards, we check if there's already an existing
            // token with the same customer, and simply return that
            // instead of creating a new token altogether.
            // However, for emandate, we don't do this check,
            // because emandate tokens are newly created for each
            // and every new first recurring payment, for now.
            //
            if ($existingToken !== null)
            {
                return $existingToken;
            }
        }

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function createViaCps($input,$merchant, Card\Entity $card)
    {
        $customer =  $this->repo->customer->findOrFailPublic($input[Token\Entity::CUSTOMER_ID]);

        $token = new Token\Entity;

        $input['token'] = [
            Entity::METHOD      => Method::CARD,
            Entity::CARD_ID     => $card->getId(),
        ];

        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_CREATE,
            [
                'customer_id' => $customer->getId(),
                'input'       => $input['token']
            ]
        );

        $token->build($input['token']);

        $token->setExpiredAt($card->getExpiryTimestamp());

        $token->card()->associate($card);

        $token->merchant()->associate($merchant);

        $token->customer()->associate($customer);

        $existingToken = $this->validateExistingToken($token);

        //
        // For cards, we check if there's already an existing
        // token with the same customer, and simply return that
        // instead of creating a new token altogether.
        //
        if ($existingToken !== null)
        {
            return $existingToken;
        }
        else
        {
            $this->repo->saveOrFail($token);

            return $token;
        }
    }
    /**
     * Below function is used to create token in payment flow where we
     * already have a card_id
     *
     * @param string           $subscriptionId
     * @param array            $input
     * @param Merchant\Entity  $merchant
     *
     * @return Entity
     */
    public function createForSubscription($input, string $subscriptionId, Merchant\Entity $merchant)
    {
        $traceInput = $input;
        unset($traceInput[Entity::AADHAAR_NUMBER]);

        $this->trace->info(
            TraceCode::SUBSCRIPTION_TOKEN_CREATE,
            [
                '$subscription_id' => $subscriptionId,
                'input'            => $traceInput
            ]
        );

        $token = new Token\Entity;

        $card = null;

        if (isset($input[Token\Entity::CARD_ID]) === true)
        {
            $card = $this->repo->card->findOrFailPublic($input[Token\Entity::CARD_ID]);
        }

        $token->build($input);

        if ($card !== null)
        {
            $token->setExpiredAt($card->getExpiryTimestamp());

            $token->card()->associate($card);
        }

        $token->setSubscriptionId($subscriptionId);

        $token->merchant()->associate($merchant);

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function cloneToken(Entity $token) :Entity
    {
        $createInput = [
            Entity::METHOD      => $token->getMethod(),
            Entity::CARD_ID     => $token->getCardId(),
        ];

        return $this->create($token->customer, $createInput, null, false);
    }

    public function edit($token, $input)
    {
        $token->edit($input);

        $this->repo->saveOrFail($token);

        return $token;
    }

    /**
     * Get the token entity for local/global customer.
     * $id can be token or
     * token id or
     * gateway token(with recurring_debit_umrn feature enabled for merchant)
     * for now.
     * @param $id
     * @param $customer
     * @return Token\Entity
     */
    public function getByTokenIdAndCustomer($id, Customer\Entity $customer)
    {
        $token = null;

        if (($this->merchant !== null) and
            ($this->merchant->isFeatureEnabled(Feature::RECURRING_DEBIT_UMRN) === true))
        {
            $token = $this->repo->token->getByGatewayTokenAndCustomerId($id, $customer->getId());
        }

        // TODO: remove this once merchants shifts to token_id
        if ($token === null)
        {
            $token = $this->repo->token->getByTokenAndCustomer($id, $customer);
        }

        if ($token === null)
        {
            $token = $this->repo->token->findByPublicIdAndMerchant($id, $customer->merchant);

            if ($token->getCustomerId() !== $customer->getId())
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_TOKEN_NOT_FOUND,
                    Entity::ID,
                    $id,
                    'Token not found for id: ' . $id . ' customer id: ' . $customer->getId());
            }
        }

        return $token;
    }

    public function getByTokenIdAndSubscriptionId($id, string $subscriptionId): Entity
    {
        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        if (($token->getEntityId() !== $subscriptionId) and
            ($token->getEntityType() !== Constants\Entity::SUBSCRIPTION))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_ID,
                Entity::ID);
        }

        return $token;
    }

    /**
     * @param string $id
     * @param string $customerId
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function getByTokenIdAndCustomerId(string $id, string $customerId)
    {
        $token = null;

        if (($this->merchant !== null) and
            ($this->merchant->isFeatureEnabled(Feature::RECURRING_DEBIT_UMRN) === true))
        {
            $token = $this->repo->token->getByGatewayTokenAndCustomerId($id, $customerId);
        }

        if ($token === null)
        {
            Entity::verifyIdAndSilentlyStripSign($id);

            $token = $this->repo->token->getByTokenAndCustomerId($id, $customerId);

            if ($token === null)
            {
                $token = $this->repo->token->getByTokenIdAndCustomerId($id, $customerId);
            }
        }

        return $token;
    }

    /**
     * This method gives us all of the customer's saved tokens
     *
     * @param $customer
     * @return mixed
     */
    public function fetchTokensByCustomer($customer, $merchant = null)
    {
        $withVpas = false;

        // We will exclude VPAs tokens except of for this conditions
        // 1. Feature SAVE_VPA is enabled for merchant.
        // 2. We do not want this to be on shared merchant (Adding check Just In Case)
        // 3.
        if (($merchant instanceof Merchant\Entity) and
            ($merchant->isShared() === false) and
            ($merchant->shouldSaveVpa() === true))
        {
            $withVpas = true;
        }

        $tokens = $this->repo->token->getByCustomer($customer, $withVpas);

        return $tokens;
    }

    /**
     * This method takes in the current tokens collection, removes the
     * emandate tokens and returns the remaining tokens as an array
     *
     * @param $tokens
     *
     * @return mixed
     */
    public function removeEmandateRecurringTokens($tokens)
    {
        //
        // We are creating an array of all the items that do not pass the truth test
        // that the token is of emandate method - as we do not want to show
        // emandate tokens to the merchant via preferences
        //

        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {
            $tokens = $tokens->reject(
                function($token)
                {
                    if ($token->getMethod() === Method::EMANDATE)
                    {
                        return true;
                    }

                    return false;
                })->values();
        }
        else
        {
            $tokenItems = & $tokens['items'];

            $tokenItems = array_filter($tokenItems, function ($item)
            {
                return ($item['method'] !== Method::EMANDATE);
            });
        }

        return $tokens;
    }

    /**
     * This method takes in the current tokens collection, removes the
     * tokens for disabled card networks and returns the remaining tokens as an array
     *
     * @param $tokens
     *
     * @return mixed
     */
    public function removeDisabledNetworkTokens($tokens, $networks)
    {
        $disabledNetwork = array_keys(array_filter($networks, function($network) {
            if ($network === 0) return true;
            else return false;
        }));

        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {
            $tokens = $tokens->reject(
                function($token) use ($disabledNetwork)
                {
                    //
                    // If token has card and it's not in disabled card network then reject this token (true)
                    //
                    if ($token->hasCard() === true)
                    {
                        // get the latest card details from IIN details
                        $token->card->overrideIINDetails();

                        if (in_array($token->card->getNetworkCode(), $disabledNetwork, true) === true)
                        {
                            return true;
                        }
                    }

                    return false;
                })->values();
        }
        else
        {
            $tokenItems = & $tokens['items'];

            $tokenItems = array_filter($tokenItems, function ($item) use ($disabledNetwork)
            {
                //
                // If item contains field `card` then filter out token with card network in disabled network array
                //
                if ((isset($item[Entity::CARD]) === true) and
                    (isset($item[Entity::CARD][Card\Entity::NETWORK]) === true)
                    (in_array(Card\Network::getCode($item[Entity::CARD][Card\Entity::NETWORK]), $disabledNetwork, true) === true))
                {
                    return false;
                }

                return true;
            });
        }

        return $tokens;
    }

     /**
     * This method takes in the current tokens collection, removes the
     * card tokens which has empty name in the card entity
     *
     * @param $tokens
     *
     * @return mixed
     */
    public function removeCardTokensWithoutName($tokens)
    {
        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {
            $tokens = $tokens->reject(
                function($token)
                {
                    //
                    // If token has card and the card doesn't contain name then reject this token (true)
                    //
                    if (($token->hasCard() === true) and
                        (empty($token->card->getName()) === true))
                    {
                        return true;
                    }

                    return false;
                })->values();
        }
        else
        {
            $tokenItems = & $tokens['items'];

            $tokenItems = array_filter($tokenItems, function ($item)
            {
                //
                // If item contains field `card` then filter out token with card with empty name
                //
                if ((isset($item[Entity::CARD]) === true) and
                    (empty($item[Entity::CARD][Card\Entity::NAME]) === true))
                {
                    return false;
                }

                return true;
            });
        }

        return $tokens;
    }

    public function updateTokenFromEmandateGatewayData(Entity $token, array $gatewayData)
    {
        if (empty($gatewayData[Entity::RECURRING_STATUS]) === false)
        {
            $gatewayRecurringStatus = $gatewayData[Entity::RECURRING_STATUS];

            $token->setRecurringStatus($gatewayRecurringStatus);
        }
        else
        {
            //
            // The recurring status should always be set for token update.
            //
            $this->trace->critical(
                TraceCode::GATEWAY_RECURRING_STATUS_NOT_SET,
                [
                    'token'        => $token->toArray(),
                    'gateway_data' => $gatewayData
                ]);

            return;
        }

        if (empty($gatewayData[Entity::ACKNOWLEDGED_AT]) === false)
        {
            $acknowledgedAt = $gatewayData[Entity::ACKNOWLEDGED_AT];

            $token->setAcknowledgedAt($acknowledgedAt);
        }

        if ($gatewayRecurringStatus === RecurringStatus::CONFIRMED)
        {
            $token->setRecurring(true);

            //
            // Not all netbanking recurring have a gateway token.
            // However, if a second recurring payment is attempted without a gateway token,
            // we throw an exception or handle the case appropriately in the child gateway class.
            //
            if (empty($gatewayData[Entity::GATEWAY_TOKEN]) === false)
            {
                $gatewayToken = $gatewayData[Entity::GATEWAY_TOKEN];

                $token->setGatewayToken($gatewayToken);
            }
        }
        else if ($gatewayRecurringStatus === RecurringStatus::REJECTED)
        {
            if (empty($gatewayData[Entity::RECURRING_FAILURE_REASON]) === true)
            {
                //
                // If it's rejected, there must always be a reason.
                //

                $this->trace->critical(
                    TraceCode::GATEWAY_RECURRING_REJECTED_WITHOUT_REASON,
                    [
                        'token'        => $token->toArray(),
                        'gateway_data' => $gatewayData
                    ]);

                return;
            }

            $token->setRecurringFailureReason($gatewayData[Entity::RECURRING_FAILURE_REASON]);
        }
    }

    public function updateTokenForUpi(Entity $token, array $input)
    {
        $oldRecurringStatus = $token->getRecurringStatus();

        if ($input[Entity::RECURRING_STATUS] === RecurringStatus::CONFIRMED)
        {
            $token->setRecurringStatus(RecurringStatus::CONFIRMED);
            $token->setRecurring(true);
        }
        else if ($input[Entity::RECURRING_STATUS] === RecurringStatus::INITIATED)
        {
            $allowed = [null, RecurringStatus::INITIATED, RecurringStatus::NOT_APPLICABLE];

            if (in_array($token->getRecurringStatus(), $allowed, true) === true)
            {
                $token->setRecurringStatus(RecurringStatus::INITIATED);
            }
        }
        else if ($input[Entity::RECURRING_STATUS] === RecurringStatus::REJECTED)
        {
            // Check if the previous recurring status of token is initiated.
            if ($token->getRecurringStatus() === RecurringStatus::INITIATED)
            {
                $token->setRecurringStatus(RecurringStatus::REJECTED);

                $token->setRecurringFailureReason($input[Entity::RECURRING_FAILURE_REASON]);
            }
        }

        $this->repo->saveOrFail($token);

        $this->eventUpiRecurringTokenStatus($token, $oldRecurringStatus);
    }

    public function updateTokenFromNachGatewayData(Entity $token, array $gatewayData)
    {
        if (empty($gatewayData[Entity::RECURRING_STATUS]) === false)
        {
            $gatewayRecurringStatus = $gatewayData[Entity::RECURRING_STATUS];

            $token->setRecurringStatus($gatewayRecurringStatus);
        }
        else
        {
            //
            // The recurring status should always be set for token update.
            //
            $this->trace->critical(
                TraceCode::GATEWAY_RECURRING_STATUS_NOT_SET,
                [
                    'token'        => $token->toArray(),
                    'gateway_data' => $gatewayData
                ]);

            return;
        }

        if (empty($gatewayData[Entity::ACKNOWLEDGED_AT]) === false)
        {
            $acknowledgedAt = $gatewayData[Entity::ACKNOWLEDGED_AT];

            $token->setAcknowledgedAt($acknowledgedAt);
        }

        if ($gatewayRecurringStatus === RecurringStatus::CONFIRMED)
        {
            $token->setRecurring(true);

            //
            // If a second recurring payment is attempted without a gateway token,
            // we throw an exception or handle the case appropriately in the child gateway class.
            //
            if (empty($gatewayData[Entity::GATEWAY_TOKEN]) === false)
            {
                $gatewayToken = $gatewayData[Entity::GATEWAY_TOKEN];

                $token->setGatewayToken($gatewayToken);
            }
        }
        else if ($gatewayRecurringStatus === RecurringStatus::REJECTED)
        {
            if (empty($gatewayData[Entity::RECURRING_FAILURE_REASON]) === true)
            {
                //
                // If it's rejected, there must always be a reason.
                //

                $this->trace->critical(
                    TraceCode::GATEWAY_RECURRING_REJECTED_WITHOUT_REASON,
                    [
                        'token'        => $token->toArray(),
                        'gateway_data' => $gatewayData
                    ]);

                return;
            }

            $token->setRecurringFailureReason($gatewayData[Entity::RECURRING_FAILURE_REASON]);
        }
        else if ($gatewayRecurringStatus === RecurringStatus::INITIATED)
        {
            $gatewayToken = $gatewayData[Entity::GATEWAY_TOKEN];

            if (empty($gatewayToken) === false)
            {
                $token->setGatewayToken($gatewayToken);
            }
        }
    }

    public function updatePaymentToken($payment, $card)
    {
        $updated = false;

        if ($payment->getTokenId() !== null)
        {
            $existingToken = $this->findAndReturnExistingToken($payment->localToken, $card, $payment, $payment->customer);

            if ($existingToken !== null)
            {
                $payment->localToken()->associate($existingToken);

                $updated = true;
            }
        }

        if ($payment->getGlobalTokenId() !== null)
        {
            $existingToken = $this->findAndReturnExistingToken($payment->globalToken, $card, $payment, $payment->globalCustomer);

            if ($existingToken !== null)
            {
                $payment->globalToken()->associate($existingToken);

                $updated = true;
            }
        }

        return $updated;
    }

    protected function getExistingTokens($token, $card, $payment, $customer)
    {
        $existingCards = (new Card\Core)->findAllExistingCards($card, $customer->merchant);

        if ($existingCards === null)
        {
            return null;
        }

        $cardIds = $existingCards->pluck(Entity::ID);

        return $this->repo->token->getByMethodAndCustomerIdAndCardIds(
                                $token->getMethod(), $token->customer, $cardIds);
    }

    protected function findAndReturnExistingToken($token, $card, $payment, $customer)
    {
        $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_TOKEN,
                [
                    'token' => $token->getId(),
                ]);

        $existingTokens = $this->getExistingTokens($token, $card, $payment, $customer);

        if (($existingTokens === null) or
            ((count($existingTokens) === 1) and
            ($existingTokens[0]->getId() === $token->getId())))
        {
            return null;
        }

        $existingToken = null;

        foreach ($existingTokens as $tempToken)
        {
            if ($tempToken->getId() !== $token->getId())
            {
                $existingToken = $tempToken;

                break;
            }
        }

        if ($existingToken === null)
        {
            return null;
        }

        $token->setExpiredAt(Carbon::now()->getTimestamp());

        $this->repo->saveOrFail($token);

        if ($payment->hasBeenAuthorized() === true)
        {
            $existingToken->incrementUsedCount();

            $existingToken->setUsedAt($payment->getAuthorizeTimestamp());

            $this->repo->saveOrFail($existingToken);
        }

        $this->trace->info(
                TraceCode::VAULT_TOKEN_MIGRATION_TOKEN,
                [
                    'existing_token' => $existingToken->getId(),
                    'token'          => $token->getId(),
                ]);

        return $existingToken;
    }

    protected function validateExistingToken($token)
    {
        $existingTokens = $this->repo->token->getByMethodAndCustomerId(
                                $token->getMethod(), $token->customer);

        $func = 'validateExistingToken' . studly_case($token->getMethod());

        return $this->$func($existingTokens, $token);
    }

    protected function validateExistingNetworkToken($token)
    {
        $existingTokens = $this->repo->token->getByMethodAndMerchant(
                                $token->getMethod(), $token->merchant);

        $func = 'validateExistingToken' . studly_case($token->getMethod());

        return $this->$func($existingTokens, $token);
    }

    protected function validateExistingTokenCard($existingTokens, $newToken)
    {
        $vaultToken = $newToken->card->getVaultToken();

        $expiryMonth = $newToken->card->getExpiryMonth();

        $expiryYear = $newToken->card->getExpiryYear();

        foreach ($existingTokens as $token)
        {
            if (($token->hasCard() === true) and
                ($token->card->getVaultToken()  === $vaultToken) and
                ($token->card->getExpiryMonth() === $expiryMonth) and
                ($token->card->getExpiryYear()  === $expiryYear))
            {
                return $token;
            }
        }

        return null;
    }

    protected function validateExistingTokenEmandate($existingTokens, $newToken)
    {
        return null;
    }

    protected function validateExistingTokenWallet($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if (($token->getWallet() === $newToken->getWallet()) and
                ($token->terminal() === $newToken->terminal()))
            {
                return $token;
            }
        }

        return null;
    }

    protected function validateExistingTokenUpi($existingTokens, $newToken)
    {
        // If the token being created is for recurring payment, we want to create new token. But if the token is for
        // saved vpa, we dont want to create a new token if a token already exists.
        if ($newToken->isSaveVpaToken() === false)
        {
            return null;
        }

        foreach ($existingTokens as $token)
        {
            if ($token->getVpaId() === $newToken->getVpaId())
            {
                return $token;
            }
        }

        return null;
    }

    protected function validateExistingTokenNach($existingTokens, $newToken)
    {
        return null;
    }

    protected function getCardInputForDirectToken(array & $input)
    {
        $cardInput = array_pull($input, Entity::CARD);

        $cardInput[Card\Entity::VAULT] = Card\Vault::RZP_VAULT;

        $iin = substr($cardInput[Card\Entity::NUMBER], 0, 6);

        $network = Card\Network::detectNetwork($iin);

        $cardInput[Card\Entity::CVV] = Card\Entity::getDummyCvv($network);

        return $cardInput;
    }

    public function validateUpiTokenForUpdate(Token\Entity $token)
    {
        $this->validateExpiryTimeForUpiTokenUpdate($token);

        $this->validateRecurringStatusForUpiTokenUpdate($token);
    }

    protected function validateExpiryTimeForUpiTokenUpdate(Token\Entity $token)
    {
        $currentTimestamp = $token->freshTimestamp();

        $expireTimestamp = $token->getExpiredAt();

        if ($currentTimestamp > $expireTimestamp)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UPDATE_EXPIRED_TOKEN);
        }
    }

    protected function validateRecurringStatusForUpiTokenUpdate(Token\Entity $token)
    {
        $tokenStatus = $token->getRecurringStatus();

        if ($tokenStatus !== RecurringStatus::CONFIRMED)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_UPDATE_NOT_CONFIRMED_TOKEN);
        }
    }

    public function validateTokenForCancel(Token\Entity $token)
    {
        $method = $token->getMethod();

        $recurring = $token->isRecurring();

        if (($method !== Method::UPI) or ($recurring !== true))
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL);
        }
    }

    /**
     * Handle Token Pause Event
     */
    public function pauseTokenEvent($tokenId, $customerId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_PAUSE,
            [
                'token_id'    => $tokenId,
                'customer_id' => $customerId,
            ]);

        // Todo: handle getting customer in test case, currently erroring out
        //$customer = $this->repo->customer->findByPublicId($customerId);

        $token = $this->repo->token->findByPublicId('token_' . $tokenId);

        $oldRecurringStatus = $token->getRecurringStatus();

        $token->setRecurringStatus(RecurringStatus::PAUSED);

        $token->saveOrFail();

        $this->eventUpiRecurringTokenStatus($token, $oldRecurringStatus);

        $this->notifyAppsTokenStatus($token, RecurringStatus::PAUSED);
    }

    /**
     * Handle Token Resume Event
     */
    public function resumeTokenEvent($tokenId, $customerId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_RESUME,
            [
                'token_id'    => $tokenId,
                'customer_id' => $customerId,
            ]);

        // Todo: handle getting customer in test case, currently erroring out
        //$customer = $this->repo->customer->findByPublicId($customerId);

        $token = $this->repo->token->findByPublicId('token_' . $tokenId);

        $oldRecurringStatus = $token->getRecurringStatus();

        $token->setRecurringStatus(RecurringStatus::CONFIRMED);

        $token->saveOrFail();

        $this->eventUpiRecurringTokenStatus($token, $oldRecurringStatus);

        $this->notifyAppsTokenStatus($token, RecurringStatus::CONFIRMED);
    }

    /**
     * Handle Token Pause Event
     */
    public function cancelTokenEvent($tokenId, $customerId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_CANCEL,
            [
                'token_id'    => $tokenId,
                'customer_id' => $customerId,
            ]);

        // Todo: handle getting customer in test case, currently erroring out
        //$customer = $this->repo->customer->findByPublicId($customerId);

        $token = $this->repo->token->findByPublicId('token_' . $tokenId);

        $oldRecurringStatus = $token->getRecurringStatus();

        $token->setRecurringStatus(RecurringStatus::CANCELLED);

        $token->saveOrFail();

        $this->eventUpiRecurringTokenStatus($token, $oldRecurringStatus);

        $this->notifyAppsTokenStatus($token, RecurringStatus::CANCELLED);
    }

    protected function eventUpiRecurringTokenStatus(Token\Entity $token, string $oldRecurringStatus = null)
    {
        $currentRecurringStatus = $token->getRecurringStatus();

        // Ideally the old recurring status should not be the same as the new recurring status. But, in some cases,
        // such as in cases where we did not get callback for token getting paused, these statuses might be same.
        // We dont want to send multiple webhooks for the same final status in this case.
        if (($oldRecurringStatus !== $currentRecurringStatus) and
            (Token\RecurringStatus::isWebhookStatus($currentRecurringStatus) === true))
        {
            $event = 'api.token.' . $currentRecurringStatus;

            $eventPayload = [
                ApiEventSubscriber::MAIN => $token,
            ];

            $this->app['events']->dispatch($event, $eventPayload);
        }
    }

    /**
     * notify apps if the token status change
     * listed apps ["subscriptions"]
     */
    public function notifyAppsTokenStatus($token, $status)
    {
        // Send to apps
        if ($token->getMethod() === Method::UPI) {
            $upiMandate = $this->repo->upi_mandate->findByTokenId($token->getId());
            $orderId = $upiMandate->order->getId();

            $order = $this->repo->order->findByPublicId('order_' . $orderId);

            if (in_array($order->getProductType(), RecurringStatus::appsToNotifyTokenStatus, true) === true)
            {
                $tokenData = [
                    'isTokenAction'   => true,
                    'token_id'        => $token->getId(),
                    'subscription_id' => $order->getProductId(),
                    'token_status'    => $status,
                    'mode'            => $this->mode
                ];

                $this->trace->info(
                    TraceCode::CUSTOMER_TOKEN_ACTION_ASYNC,
                    [
                        'payload'   => $tokenData,
                        'mode'      => $this->mode,
                    ]);

                TokenActionsHandler::dispatch($tokenData, $this->mode);
            }
        }
    }

    public function createNetworkToken($input)
    {
        $customer = null;

        (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN, $input);

        if (empty($input[Token\Entity::AUTHENTICATION_DATA]) === false)
        {
            (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA, $input[Token\Entity::AUTHENTICATION_DATA]);
        }

        if (empty($input[Token\Entity::CUSTOMER_ID]) === false)
        {
            $customer = $this->repo->customer->findOrFailByPublicIdAndMerchant($input[Token\Entity::CUSTOMER_ID], $this->merchant);
        }


        $input['card'][Card\Entity::VAULT] = Card\Vault::RZP_VAULT;

        $card = (new Card\Core)->create($input['card'], $this->merchant);

        $this->trace->info(
            TraceCode::NETWORK_TOKEN_CREATE
        );

        $token = new Token\Entity;

        $createTokenInput = [
            Entity::METHOD      => Method::CARD,
            Entity::CARD_ID     => $card->getId(),
        ];

        $token->build($createTokenInput);

        $token->card()->associate($card);

        $token->setExpiredAt($card->getExpiryTimestamp());

        $token->merchant()->associate($this->merchant);

        if (empty($customer) === false)
        {
            $token->customer()->associate($customer);
        }

        $existingToken = $this->validateExistingNetworkToken($token);

        if (empty($existingToken) === false)
        {
            return $existingToken;
        }

        $this->repo->saveOrFail($token);

        return $token;
    }
}
