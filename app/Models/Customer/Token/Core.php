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
use RZP\Jobs\SavedCardTokenisationJob;
use Razorpay\Trace\Logger as Trace;

class Core extends Base\Core
{
    const GATEWAY_VISA = 'tokenisation_visa';
    const GATEWAY_MC   = 'tokenisation_mastercard';
    const GATEWAY_RUPAY = 'tokenisation_rupay';

    const TokenizationGateways = [
        self::GATEWAY_VISA,
        self::GATEWAY_MC,
        self::GATEWAY_RUPAY
    ];

    public const TokenisationGatewayToNetworkMapping = [
        self::GATEWAY_VISA  => Card\Network::VISA,
        self::GATEWAY_MC    => Card\Network::MC,
        self::GATEWAY_RUPAY => Card\Network::RUPAY
    ];

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


        //We need to set wallet before since for lazypay we want to encrypt gateway_token
        if (isset($input[Token\Entity::WALLET]) === true)
        {
            $token->setWallet($input[Token\Entity::WALLET]);
        }

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

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'token'     => $token->toArrayPublic(),
                'message'   => 'Post token build',
            ]
        );

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

    public function createWithoutCustomer($input, Merchant\Entity $merchant, Card\Entity $card = null , bool $validateExisting = true) {

        $traceInput = $input;

        unset($traceInput[Entity::AADHAAR_NUMBER]);

        $this->trace->info(
            TraceCode::TOKEN_CREATE,
            [
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
        // This should be before associations because if defaults for the
        // foreign entities are present as null in the entity class
        // and if the association is done before the build, the
        // association will get overridden as null.
        //
        $token->build($input);

        if ($card !== null)
        {
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

        $token->merchant()->associate($merchant);

        if ($validateExisting === true)
        {
            $existingToken = $this->validateExistingToken($token);

            if ($existingToken !== null)
            {
                return $existingToken;
            }
        }

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

    /**
     * Get the token entity for merchant with no customer.
     * $id can be token or
     * token id or
     * gateway token(with recurring_debit_umrn feature enabled for merchant)
     * for now.
     * @param $id
     * @param $merchant
     * @return Token\Entity
     */
    public function getByTokenIdAndMerchant($id, Merchant\Entity $merchant)
    {
        $token = null;

        if (($this->merchant !== null) and
            ($this->merchant->isFeatureEnabled(Feature::RECURRING_DEBIT_UMRN) === true))
        {
            $token = $this->repo->token->getByGatewayTokenAndMerchantId($id, $merchant->getId());
        }

        // TODO: remove this once merchants shifts to token_id
        if ($token === null)
        {
            $token = $this->repo->token->getByTokenAndMerchant($id, $merchant);
        }

        if ($token === null)
        {
            $token = $this->repo->token->findByPublicIdAndMerchant($id, $merchant);
        }

        if (($token->getMerchantId() !== $merchant->getId()) || (empty($token->getCustomerId()) === false))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_NOT_FOUND,
                Entity::ID,
                $id,
                'Token not found for id: ' . $id . ' merchant id: ' . $merchant->getId());
        }

        return $token;
    }

    public function getByTokenIdAndSubscriptionId($id, string $subscriptionId): Entity
    {
        $token = $this->repo->token->getByPublicIdAndMerchant($id, $this->merchant);

        // removed (($token->getEntityId() !== $subscriptionId) check
        // because one token id can be associated to more than one subscription

        if  (($token === null) or ($token->getEntityType() !== Constants\Entity::SUBSCRIPTION))
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

        if (is_null($input[Entity::VPA_ID]) === false)
        {
            $token->setVpaId($input[Entity::VPA_ID]);
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
        $customer = $token->customer;

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'message'           => 'fetching existing tokens',
                'is_customer_null'  => is_null($customer),
            ]
        );

        if ($token->getMethod() === Method::UPI)
        {
            return $this->validateExistingTokenUpi($token);
        }

        if ($customer !== null)
        {
            $existingTokens = $this->repo->token->getByMethodAndCustomerId(
                $token->getMethod(), $token->customer);
        }
        else
        {
            if ($token->card->getVaultToken() === null )
            {
                return null;
            }

            $existingTokens = $this->repo->token->getByMethodAndCustomerIdIsNull($token->getMethod(),$token->getMerchantId(), $token->card->getVaultToken());
        }

        $this->trace->info(
            TraceCode::MISC_TRACE_CODE,
            [
                'message'                   => 'fetched existing tokens',
                'existing_tokens_count'     => count($existingTokens),
            ]
        );

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

    protected function validateAndReturnExistingNetworkToken($token)
    {
        $newCard = $token->card;

        if (empty($newCard->getVaultToken()) === true)
        {
            return null;
        }

        // Return cards having same vault token as that of the new card
        $cards = $this->repo->card->fetchCardsWithVaultToken($newCard->getVaultToken(), $token->getMerchantId());

        if(empty($cards) === true)
        {
            return null;
        }

        $expiryMonth = $newCard->getExpiryMonth();

        $expiryYear = $newCard->getExpiryYear();

        foreach ($cards as $card)
        {
            if (($card->getExpiryMonth() === $expiryMonth) and
                ($card->getExpiryYear()  === $expiryYear))
            {
                // fetch existing token
                return $this->repo->token->fetchByMethodAndCardIdAndMerchant(
                    Method::CARD,
                    $card->getId(),
                    $token->getMerchantId()
                );
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

    protected function validateExistingTokenPaylater($existingTokens, $newToken)
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

    protected function validateExistingTokenUpi($newToken)
    {
        // If the token being created is for recurring payment, we want to create new token. But if the token is for
        // saved vpa, we dont want to create a new token if a token already exists.
        if ($newToken->isSaveVpaToken() === false)
        {
            $this->trace->info(
                TraceCode::MISC_TRACE_CODE,
                [
                    'message'           => 'saving and returning new token',
                    'is_save_vpa_token' => 'false',
                ]
            );
            return null;
        }

        $existingToken = $this->repo->token->getByMethodCustomerIdAndVpaId(
            $newToken->getMethod(), $newToken->customer, $newToken->getVpaId());

        return $existingToken;
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

    public function pauseCardToken($tokenId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_PAUSE,
            [
                'token_id'    => $tokenId,
            ]);


        $token = $this->repo->transaction(function () use ($tokenId)
        {
            $token = $this->repo->token->lockForUpdate($tokenId);

            $currentStatus = $token->getRecurringStatus();

            if ($currentStatus !== RecurringStatus::CONFIRMED) {
                throw new Exception\BadRequestValidationFailureException(
                    'token is not in appropriate state to pause', null, [
                        'token_id' => $token->getId(),
                ]);
            }

            $token->setRecurringStatus(RecurringStatus::PAUSED);

            $token->saveOrFail();

            return $token;
        });

        $this->eventCardRecurringTokenStatus($token, RecurringStatus::CONFIRMED);

        $this->notifyAppsTokenStatus($token, RecurringStatus::PAUSED);
    }

    public function resumeCardToken($tokenId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_RESUME,
            [
                'token_id'    => $tokenId,
            ]);

        $token = $this->repo->transaction(function () use ($tokenId)
        {
            $token = $this->repo->token->lockForUpdate($tokenId);

            $currentStatus = $token->getRecurringStatus();

            if ($currentStatus !== RecurringStatus::PAUSED) {
                throw new Exception\BadRequestValidationFailureException(
                    'token is not in appropriate state to resume', null, [
                    'token_id' => $token->getId(),
                ]);
            }

            $token->setRecurringStatus(RecurringStatus::CONFIRMED);

            $token->saveOrFail();

            return $token;
        });

        $this->eventCardRecurringTokenStatus($token, RecurringStatus::PAUSED);

        $this->notifyAppsTokenStatus($token, RecurringStatus::CONFIRMED);
    }

    public function cancelCardToken($tokenId)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_CANCEL,
            [
                'token_id'    => $tokenId,
            ]);

        $previousStatus = null;

        $token = $this->repo->transaction(function () use ($tokenId, &$previousStatus)
        {
            $token = $this->repo->token->lockForUpdate($tokenId);

            $previousStatus = $token->getRecurringStatus();

            if (($previousStatus !== RecurringStatus::CONFIRMED) and
                ($previousStatus !== RecurringStatus::PAUSED)) {
                throw new Exception\BadRequestValidationFailureException(
                    'token is not in appropriate state to cancel', null, [
                    'token_id' => $token->getId(),
                ]);
            }

            $token->setRecurringStatus(RecurringStatus::CANCELLED);

            $token->saveOrFail();

            return $token;
        });

        $this->eventCardRecurringTokenStatus($token, $previousStatus);

        $this->notifyAppsTokenStatus($token, RecurringStatus::CANCELLED);
    }

    public function completeCardToken($tokenId, $completedAt)
    {
        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_COMPLETE,
            [
                'token_id'    => $tokenId,
            ]);

        $previousStatus = null;

        $token = $this->repo->transaction(function () use ($tokenId, $completedAt, &$previousStatus)
        {
            $token = $this->repo->token->lockForUpdate($tokenId);

            $previousStatus = $token->getRecurringStatus();

            if (($previousStatus !== RecurringStatus::CONFIRMED) and
                ($previousStatus !== RecurringStatus::PAUSED)) {
                throw new Exception\BadRequestValidationFailureException(
                    'token is not in appropriate state to complete', null, [
                    'token_id' => $token->getId(),
                ]);
            }

            $token->setRecurringStatus(RecurringStatus::CANCELLED);

            $token->setExpiredAt($completedAt);

            $token->saveOrFail();

            return $token;
        });

        $this->eventCardRecurringTokenStatus($token, $previousStatus);
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

    protected function eventCardRecurringTokenStatus(Token\Entity $token, string $oldRecurringStatus = null)
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
        } elseif ($token->getMethod() === Method::CARD and $token->getEntityType() === 'subscription')
        {
            $subscriptionId = $token->getEntityId();

            $tokenData = [
                'isTokenAction'   => true,
                'token_id'        => $token->getId(),
                'subscription_id' => $subscriptionId,
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

    public function createTokenAndTokenizedCard($input)
    {
        $customer = null;

        $this->trace->info(
            TraceCode::TOKEN_CREATE_FOR_TOKENIZED_CARD_REQUEST,
            [
                'notes' => (isset($input['notes']) === true) ? $input['notes'] : null,
                'authentication' => (isset($input['authentication']) === true) ? $input['authentication'] : null,
            ]);

        (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN, $input);

        (new Validator)->validateInput(Validator::CREATE_NETWORK_CARD, $input[Entity::CARD]);

        if ($this->isNetworkRuPay($input[Entity::CARD]))
        {
            (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN_RUPAY, $input);
        }
        else if (empty($input[Token\Entity::AUTHENTICATION]) === false)
        {
            (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA, $input[Token\Entity::AUTHENTICATION]);
        }

        if (strlen($input[Entity::CARD]['expiry_year']) === 2)
        {
            $input[Entity::CARD]['expiry_year'] = '20' . $input[Entity::CARD]['expiry_year'];
        }

        if (empty($input[Token\Entity::CUSTOMER_ID]) === false)
        {
            $customer = $this->repo->customer->findOrFailByPublicIdAndMerchant($input[Token\Entity::CUSTOMER_ID], $this->merchant);
        }

        list($card, $serviceProviderTokens) = (new Card\Core)->createTokenizedCard($input, $this->merchant);

        if(!empty($serviceProviderTokens[0]["provider_data"]["network_reference_id"])){
            unset($serviceProviderTokens[0]["provider_data"]["network_reference_id"]);
        }

         $this->trace->info(
            TraceCode::TOKEN_CREATE_FOR_TOKENIZED_CARD
        );

        $token = new Token\Entity;

        // todo: change to golabal status when token v/s card design is finalized
        $tokenStatus = $serviceProviderTokens[0]['status'];

        $createTokenInput = [
            Entity::METHOD      => Method::CARD,
            Entity::CARD_ID     => $card->getId(),
            Entity::STATUS      => $tokenStatus,
            Entity::NOTES       => $input['notes'] ?? [],
        ];

        $token->build($createTokenInput);

        $token->card()->associate($card);

        $token->setExpiredAt($card->getExpiryTimestamp());

        $token->merchant()->associate($this->merchant);

        if (empty($customer) === false)
        {
            $token->customer()->associate($customer);
        }

        $this->trace->info(
            TraceCode::EXISTING_TOKEN_CHECK,
            [
                'new_card'  => $token->getCardId()
            ]);

        $requeststartAt = millitime();

        $existingToken = $this->validateAndReturnExistingNetworkToken($token);

        $this->trace->info(
            TraceCode::EXISTING_TOKEN_CHECK,
            [
                'existing_token'  => empty($existingToken) === false ? $existingToken->getId() : null,
                'fetch_time_ms'      => millitime() - $requeststartAt,
            ]);

        if (empty($existingToken) === false)
        {
            return [$existingToken, $serviceProviderTokens];
        }

        $this->repo->saveOrFail($card);

        $this->repo->saveOrFail($token);

        $updateData = [
            'merchant_token' => $token['id'],
        ];

        (new Card\CardVault)->updateToken($card->getVaultToken(), $updateData);

        return [$token, $serviceProviderTokens];
    }

    public function migrateToTokenizedCard($token, $cardInput)
    {
        $cardInput['merchant_token'] = $token->getId();

        list($card, $serviceProviderTokens) = (new Card\Core)->migrateToTokenizedCard($token->card, $token->merchant, $cardInput);

         $this->trace->info(
            TraceCode::TOKEN_MIGREATE_FOR_TOKENIZED_CARD);

        $token->setStatus($serviceProviderTokens[0]['status']);

        $token->card()->associate($card);

        $this->repo->saveOrFail($card);

        $this->repo->saveOrFail($token);
    }

    public function getIIN($input)
    {
        $iin = substr($input["number"], 0, 6);

        $tokenizedRange = substr($input['number'], 0, 9);

        if(isset($input["tokenised"]) === true)
        {
            if($input["tokenised"] === false) {
                return [$iin, false];
            }
            else{
                $result = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange);
                return [$result, true];
            }
        }

        $result = Card\IIN\IIN::getTransactingIinforRange($tokenizedRange) ?? $iin;

        return [$result, $result != $iin];
    }

    public function fetchParValue($input)
    {
        (new Validator)->validateInput(Validator::FETCH_PAR_VALUE, $input);

        list($iin, $isTokenized) = $this->getIIN($input);

        $network = Card\Network::detectNetwork($iin);

        if($network === "UNKNOWN"){
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS, ["iin" => $iin]);
        }

        $network = Card\Network::$fullName[$network];

        $input["network"] = strtolower($network);

        $input["tokenised"] = $isTokenized;

        return [$network, (new Card\Core)->fetchParValue($input)];
        // hit the vault with number and the network
    }

    public function createNetworkToken($input)
    {
        $customer = null;

        (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN, $input);

        if (empty($input[Token\Entity::AUTHENTICATION]) === false)
        {
            (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA, $input[Token\Entity::AUTHENTICATION]);
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
            Entity::NOTES       => $input['notes'] ?? [],
        ];

        $token->build($createTokenInput);

        $token->card()->associate($card);

        $token->setExpiredAt($card->getExpiryTimestamp());

        $token->merchant()->associate($this->merchant);

        if (empty($customer) === false)
        {
            $token->customer()->associate($customer);
        }

        $existingToken = $this->validateAndReturnExistingNetworkToken($token);

        if (empty($existingToken) === false)
        {
            return $existingToken;
        }

        $this->repo->saveOrFail($token);

        return $token;
    }

    public function fetchCryptogram($input, $merchant)
    {
        if (empty($input['token_id']) === false)
        {
            $token = $this->repo->token->getByPublicIdAndMerchant($input['token_id'], $this->merchant);

            $vaultToken = $token->card->getVaultToken();

            $response = (new Card\Core)->fetchCryptogramForVaultToken($vaultToken, $merchant);
        }
        else
        {
            $id = $this->stripSptPrefix($input['id']);

            $response = (new Card\Core)->fetchCryptogram($id, $merchant);
        }

        return $response[Entity::SERVICE_PROVIDER_TOKENS];
    }


    public function fetchToken($token)
    {
        $response = (new Card\Core)->fetchToken($token->card);

        return $response[Entity::SERVICE_PROVIDER_TOKENS];
    }

    public function deleteToken($token)
    {
        $response = (new Card\Core)->deleteToken($token->card);

        $token->setExpiredAt(Carbon::now()->getTimestamp());

        $token->setStatus(Entity::DEACTIVATED);

        $this->repo->saveOrFail($token);

        return $response;
    }

    /**
     * @param Merchant\Entity $merchant             The Merchant to onboard onto self::TokenizationGateways
     * @param array           $tokenizationGateways Optional. Specific tokenization gateways the merchant needs to be onboarded onto.
     *
     * @throws Exception\BadRequestException
     * @throws Exception\ServerErrorException
     */
    public function onboardMerchant(Merchant\Entity $merchant, array $tokenizationGateways = []): void
    {
        $input = [
            Merchant\Entity::ORG_ID => $merchant->getOrgId()
        ];

        $gateways = self::TokenizationGateways;

        if (!empty($tokenizationGateways)) {
            // Check if valid network/gateway values are present in $tokenizationGateways
            if (array_intersect($tokenizationGateways, self::TokenizationGateways) !== $tokenizationGateways) {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_INVALID_GATEWAY, 'tokenization_gateways', $tokenizationGateways
                );
            }
            // Override if only specific tokenization gateways are requested
            $gateways = $tokenizationGateways;
        }

        foreach ($gateways as $gateway)
        {
            $this->trace->info(
            TraceCode::TOKENIZATION_MERCHANT_ONBOARD,
            ['gateway' => $gateway,
                'merchant' => $merchant->getId()]);

            $data = $this->app['terminals_service']->initiateOnboarding($merchant->getId(), $gateway, null, null, [], $input);

            if ($data == null)
            {
                throw new Exception\ServerErrorException('Tokenization Onboarding failed', ErrorCode::MERCHANT_ONBOARD_ERROR_TERMINAL_CREATION);
            }
        }
    }

    /**
     * @param $tokens
     * @return mixed
     */
    public function addConsentFieldInTokens($tokens)
    {
        foreach ($tokens as $token)
        {
            if($token->getMethod() === Entity::CARD)
            {
                $acknowledgedAt = $token->getAcknowledgedAt();

                if(empty($acknowledgedAt) === false)
                {
                    $token[Entity::CONSENT_TAKEN] = true;
                }
                else
                {
                    $token[Entity::CONSENT_TAKEN] = false;
                }
            }
        }
        return $tokens;
    }

    public function updateStatus($tokenData)
    {
        $updateData = [];

        if(array_key_exists('status', $tokenData) && $tokenData['status'] !== null)
        {
            $updateData[Token\Entity::STATUS] = $tokenData['status'];
        }

        if($this->isPresent($tokenData, 'expiry_year') && $this->isPresent($tokenData, 'expiry_month'))
        {
            $expiryMonth = (int)$tokenData['expiry_month'];

            $expiryYear = (int)$tokenData['expiry_year'];

            if (strlen($expiryYear) == 2)
            {
                $expiryYear = '20' . $expiryYear;
            }

            $updateData[Token\Entity::EXPIRED_AT] = $this->getExpiryTimestamp($expiryMonth, $expiryYear);
        }
        else
        {
            $updateData[Token\Entity::EXPIRED_AT] = null;
        }

        $rowsAffected = $this->repo->token->updateById($tokenData['token_id'], $updateData);

        if ($rowsAffected === 0)
        {
            throw new Exception\BadRequestException(\RZP\Error\P2p\ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
                'token',
                ['data' => $tokenData]
            );
        }

        $token = $this->repo->token->findOrFailPublic($tokenData['token_id']);

        (new Card\Core)->updateCardWithTokenData($token['card_id'], $tokenData);

        return $token;
    }

    public function getExpiryTimestamp($expiryMonth, $expiryYear)
    {
        return Carbon::createFromDate($expiryYear, $expiryMonth, 1, Constants\Timezone::IST)
            ->endOfMonth()
            ->getTimestamp();
    }

    protected function isPresent($array, $param)
    {
        if(array_key_exists($param, $array) &&
            !($array[$param] !== null || $array[$param] === 0 || $array[$param] === ''))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $token
     * @param $customerId
     *
     * @return bool
     */
    public function showTokenisationConsentViewForExistingSavedCard($token, $customerId): bool
    {
        try
        {
            Token\Entity::stripSignWithoutValidation($token);

            Customer\Entity::stripSignWithoutValidation($customerId);

            $tokenEntity = $this->repo->token->getByTokenAndCustomerId($token, $customerId);

            if(isset($tokenEntity) === false)
            {
                $tokenEntity = $this->repo->token->getByTokenIdAndCustomerId($token, $customerId);

                if(isset($tokenEntity) === false)
                {
                    return false;
                }
            }

            $acknowledgedAt = $tokenEntity->getAcknowledgedAt();

            if (empty($acknowledgedAt) === true)
            {
                return true;
            }

            return false;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SAVED_CARD_TOKEN_NOT_FOUND, []);

            return false;
        }
    }

    protected function stripSptPrefix($id)
    {
        $prefix = 'spt';

        $delimiter = '_';

        return substr($id, strlen($prefix . $delimiter));
    }

    protected function isNetworkRuPay($card)
    {
        $iin =  substr($card['number'] ?? 0, 0, 6);

        $network = Card\Network::detectNetwork($iin);

        $networkName = Card\Network::getFullName($network);

        return ($networkName === Card\Network::$fullName[Card\Network::RUPAY]);
    }

    /**
     * executes datalake query to fetch consent received tokenIds for tokenisation
     *
     * @param  string $merchantId
     * @param  array $onboardedNetworkNames
     * @param  int $offset
     * @return array
     */
    private function executeDataLakeQueryToFetchConsentReceivedTokenIds(string $merchantId, array $onboardedNetworkNames, int $offset): array
    {
        $onboardedNetworkNamesInString = implode("','", $onboardedNetworkNames);

        $rzpVaultsInString = implode("','", [Card\Vault::RZP_ENCRYPTION, Card\Vault::RZP_VAULT]);

        $rawQueryBuilder = " SELECT t.id " .
            " FROM alluxio.realtime_hudi_api.tokens t " .
                " INNER JOIN alluxio.realtime_hudi_api.cards c " .
                    " ON t.card_id = c.id " .
            " WHERE  t.method = 'card' " .
                " AND t.acknowledged_at IS NOT NULL " .
                " AND c.international = 0 " .
                " AND t.merchant_id = '%s' " .
                " AND c.network IN ('%s') " .
                " AND c.vault IN ('%s') " .
                " AND t.deleted_at IS NULL " .
            " ORDER BY t.id " .
            " OFFSET %d LIMIT %d";

        $rawQuery = sprintf(
            $rawQueryBuilder,
            $merchantId,
            $onboardedNetworkNamesInString,
            $rzpVaultsInString,
            $offset,
            Entity::MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT
        );

        $queryResult = $this->app['datalake.presto']->getDataFromDataLake($rawQuery);

        return array_column($queryResult, "id");
    }

    /**
     * Fetch consent received tokenIds for tokenisation
     *
     * @param  string  $merchantId
     * @param  int  $offset
     * @param  int  $retryCount
     * @return array
     * @throws \Exception
     */
    public function fetchConsentReceivedLocalTokenIdsForTokenisation(string $merchantId, int $offset, int $retryCount = 0): array
    {
        try
        {
            $onboardedNetworks = (new Terminal\Core())->getMerchantTokenisationOnboardedNetworks($merchantId);

            $onboardedNetworkNames = Card\Network::getFullNames($onboardedNetworks);

            if (empty($onboardedNetworkNames))
            {
                return [];
            }

            if ($merchantId === Merchant\Account::SHARED_ACCOUNT) {
                throw new Exception\LogicException(
                    'Please use fetchConsentReceivedGlobalTokenIdsForTokenisation() for fetching global tokens'
                );
            }

            return $this->executeDataLakeQueryToFetchConsentReceivedTokenIds($merchantId, $onboardedNetworkNames, $offset);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::ASYNC_TOKENISATION_DATALAKE_QUERY_FAILURE, [
                'merchantId'    => $merchantId,
                'offset'        => $offset,
                'retryCount'    => $retryCount,
            ]);

            // One retry is made before throwing exception
            if ($retryCount < 1)
            {
                return $this->fetchConsentReceivedLocalTokenIdsForTokenisation($merchantId, $offset, $retryCount + 1);
            }

            throw $ex;
        }
    }

    /**
     * @param string $lastProcessedTokenId The last processed token id which will
     *                                     be used as an offset to fetch next set of tokens.
     * @param int    $limit                Number of tokens to fetch. Default 1000.
     *
     * @return array List of consent received global token ids
     */
    public function fetchConsentReceivedGlobalTokenIdsForTokenisation(
        string $lastProcessedTokenId,
        int $limit = Entity::GLOBAL_MERCHANT_ASYNC_TOKENISATION_QUERY_LIMIT
    ): array {
        return $this->repo->token->fetchConsentReceivedGlobalTokenIds(
            Card\Network::getFullNames(
                Card\Network::getGlobalMerchantTokenisationNetworks()
            ),
            $lastProcessedTokenId,
            $limit
        );
    }

    /**
     * Push local tokenIds into SQS for local saved cards tokenisation
     *
     * @param  array  $tokenIds
     * @param  string  $asyncTokenisationJobId
     *
     * @return void
     */
    public function pushTokenIdsToQueueForTokenisation(array $tokenIds, string $asyncTokenisationJobId): void
    {
        foreach ($tokenIds as $tokenId)
        {
            SavedCardTokenisationJob::dispatch($this->mode, $tokenId, $asyncTokenisationJobId);
        }
    }

    public function getValidTokensForTokenisation(string $merchantId, array $tokenIds): array
    {
        $validTokenIds = $this->repo->token->filterMerchantCardTokens($merchantId, $tokenIds);

        $invalidTokenIds = array_values(array_diff($tokenIds, $validTokenIds));

        $this->trace->info(TraceCode::BULK_LOCAL_TOKENISATION_INVALID_TOKENS, [
            'merchantId'            => $merchantId,
            'validTokensCount'      => count($validTokenIds),
            'invalidTokensCount'    => count($invalidTokenIds),
            'invalidTokensIds'      => $invalidTokenIds,
        ]);

        return $validTokenIds;
    }

    /**
     * stores the consents in DB
     *
     * @param  string $merchantId
     * @param  array $tokenIds
     * @return array
     */
    public function storeConsents(string $merchantId, array $tokenIds): array
    {
        $tokenIdsBatches     = array_chunk($tokenIds, 10000);
        $consentTimestamp    = Carbon::now()->getTimestamp();
        $tokensUpdatedCount  = 0;

        foreach ($tokenIdsBatches as $tokenIdsBatch)
        {
            $updatedCount = $this->repo->token->bulkUpdateTokenIdsConsent(
                $merchantId,
                $tokenIdsBatch,
                $consentTimestamp
            );

            $tokensUpdatedCount += $updatedCount;
        }

        $this->trace->info(TraceCode::BULK_LOCAL_TOKENISATION_CONSENT_STORAGE, [
            'merchantId'                => $merchantId,
            'validTokenIdsCount'        => count($tokenIds),
            'tokensConsentStoredCount'  => $tokensUpdatedCount,
        ]);

        return $tokenIds;
    }

    /**
     * Does the following checks
     * 1. check that token method is card
     * 2. check that consent received
     * 3. check that card is not already tokenised
     * 4. check that card is not international
     * 5. check if token is recurring, allow only rupay cards for tokenisation till other networks are supported
     * 6. check that card network is in tokenisation onboarded networks for that merchant
     *
     * @param $token Entity
     * @return bool
     */
    public function checkIfTokenisationApplicable(Entity $token): bool
    {
        // If token method is not card or consent for tokenisation is not present, then it is not applicable for tokenisation
        if (($token->getMethod() !== Method::CARD) or
            ($token->hasBeenAcknowledged() === false))
        {
            return false;
        }

        $card = $token->card;

        // If card is already tokenised or card is international card, then it is not applicable for tokenisation
        if (($card->isRzpTokenisedCard() === false) or
            ($card->isInternational() === true))
        {
            return false;
        }

        // For recurring tokens, only rupay network is supported for now
        if (($token->isRecurring() === true) and
            ($card->isRupay() === false))
        {
            return false;
        }

        $onboardedNetworks = (new Terminal\Core())->getMerchantTokenisationOnboardedNetworks($token->getMerchantId());

        if(in_array($card->getNetworkCode(), $onboardedNetworks, true) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * builds card input for tokenisation
     *
     * @param  Card\Entity $card
     * @return array
     */
    public function buildCardInputForTokenisation(Card\Entity $card): array
    {
        return [
            'cvv'          => 123,
            'last4'        => $card->getLast4() ?? "0000",
            'expiry_month' => $card->getExpiryMonth() ?? "0",
            'expiry_year'  => $card->getExpiryYear() ?? "9999",
            'emi'          => $card->getEmi() ?? false,
            'iin'          => $card->getIin() ?? "",
            'name'         => $card->getName(),
        ];
    }
}
