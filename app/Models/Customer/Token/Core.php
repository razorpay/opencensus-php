<?php

namespace RZP\Models\Customer\Token;

use Illuminate\Support\Arr;
use RZP\Constants;
use Carbon\Carbon;
use RZP\Constants\Environment;
use RZP\Diag\EventCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Jobs\ParAsyncTokenisationJob;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Jobs\TokenActionsHandler;
use RZP\Models\Terminal;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Customer\Token\Constants as TokenConstants;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Listeners\ApiEventSubscriber;
use RZP\Models\Feature\Constants as Feature;
use RZP\Jobs\SavedCardTokenisationJob;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Throwable;

class Core extends Base\Core
{
    use FileHandlerTrait;

    const GATEWAY_VISA  = 'tokenisation_visa';
    const GATEWAY_MC    = 'tokenisation_mastercard';
    const GATEWAY_RUPAY = 'tokenisation_rupay';
    const GATEWAY_AMEX = 'tokenisation_amex';
    const GATEWAY_HDFC  = 'tokenisation_hdfc';
    const GATEWAY_AXIS  = 'tokenisation_axis';

    const TokenizationGateways = [
        self::GATEWAY_VISA,
        self::GATEWAY_MC,
        self::GATEWAY_AMEX,
        self::GATEWAY_RUPAY,
        self::GATEWAY_HDFC,
        self::GATEWAY_AXIS,
    ];

    public const TokenisationGatewayToNetworkMapping = [
        self::GATEWAY_VISA  => Card\Network::VISA,
        self::GATEWAY_MC    => Card\Network::MC,
        self::GATEWAY_AMEX  => Card\Network::AMEX,
        self::GATEWAY_RUPAY => Card\Network::RUPAY,
        self::GATEWAY_HDFC  => Card\Network::DICL,
    ];

    public const TokenisationGatewayToIssuerMapping = [
        self::GATEWAY_AXIS  => Card\Issuer::UTIB,
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

        // required when we are cloning recurring token for card mandate
        if (isset($input[Token\Entity::STATUS]) === true)
        {
            $token->setStatus($input[Token\Entity::STATUS]);
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
                $token->setExpiredAt($card->getTokenExpiryTimestamp());
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
            $startTime = millitime();

            $existingToken = $this->validateExistingToken($token);

            $this->trace->info(TraceCode::TOKEN_DEDUPE_CHECK_RESPONSE_TIME, [
                'timeTaken'         => millitime() - $startTime,
                'isGlobalCustomer'  => $customer->isGlobal(),
                'tokenPresent'      => isset($existingToken),
            ]);

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
            $requeststartAt = millitime();

            $existingToken = $this->validateExistingToken($token);

            $this->trace->info(
                TraceCode::EXISTING_TOKEN_CHECK,
                [
                    'existing_token'  => empty($existingToken) === false ? $existingToken->getId() : null,
                    'fetch_time_ms'      => millitime() - $requeststartAt,
                ]);

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
            Entity::STATUS      => $token->getStatus()
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

        if (($token->getMerchantId() !== $merchant->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_TOKEN_NOT_FOUND,
                Entity::ID,
                $id,
                'Token not found for id: ' . $id . ' merchant id: ' . $merchant->getId());
        }

        return $token;
    }


    public function getByTokenId($id)
    {
        $token = null;

        if (($this->merchant !== null) and
            ($this->merchant->isFeatureEnabled(Feature::RECURRING_DEBIT_UMRN) === true))
        {
            $token = $this->repo->token->getByGatewayToken($id);
        }

        // TODO: remove this once merchants shifts to token_id
        if ($token === null)
        {
            $token = $this->repo->token->getByToken($id);
        }

        if ($token === null)
        {
            $token = $this->repo->token->findByPublicId($id);
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
    public function  getByTokenIdAndCustomerId(string $id, string $customerId)
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
     * @param Customer\Entity      $customer
     * @param Merchant\Entity|null $merchant
     *
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
     * Returns all the saved tokens of a customer & filters card tokens to
     * suppress global tokens if local token for same card exists.
     *
     * @param Customer\Entity $customer
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     */
    public function fetchLocalOverGlobalTokensByCustomer(Customer\Entity $customer, Merchant\Entity $merchant): Base\PublicCollection
    {
        return $this->prioritiseLocalOverGlobalCardTokens(
            $this->fetchTokensByCustomer($customer, $merchant),
            $merchant
        );
    }

    /**
     * ToDo: Refactor this method to call self::filterTokensForCheckout()
     *       before returning the response
     *
     * @param Customer\Entity $customer
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     */
    public function fetchTokensByCustomerForCheckout(Customer\Entity $customer, Merchant\Entity $merchant): Base\PublicCollection
    {
        if ($customer->isLocal()) {
            return $this->fetchTokensByCustomer($customer, $merchant);
        }

        return $this->fetchGlobalCustomerTokens($customer, $merchant);
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
                    (isset($item[Entity::CARD][Card\Entity::NETWORK]) === true) &&
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

    public function removeDuplicateCardRecurringTokensIfAny($tokens, $merchant)
    {
        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {

            $tokens = $tokens->reject(
                function($token)
                {
                    global $distinctCardTokensData;

                    if ($token->hasCard() and $token->isRecurring())
                    {
                        if(isset($distinctCardTokensData[$token->card->getVaultToken()]))
                        {
                            return true;
                        }
                        else
                        {
                            $distinctCardTokensData[$token->card->getVaultToken()] = $token->card->getNetwork() .
                                $token->card->getIssuer();
                            return false;
                        }
                    }
                    return false;
                })->values();
        }
        else
        {
            $tokenItems = & $tokens['items'];

            $this->trace->info(
                TraceCode::DEDUP_RECURRING_TOKEN_JSON
            );

            $tokenItems = array_filter($tokenItems, function ($item)
            {
                global $distinctCardTokensData;

                if ((isset($item[Entity::CARD]) === true) and
                    $item[Token\Entity::RECURRING] === true)
                {
                    $tokenIIN = $item[Entity::CARD][Card\Entity::TOKEN_IIN];

                    if (isset($distinctCardTokensData[$tokenIIN]))
                    {
                        return false;
                    }
                    else
                    {
                        $distinctCardTokensData[$tokenIIN] = $item[Entity::CARD][Card\Entity::NETWORK] .
                            $item[Entity::CARD][Card\Entity::ISSUER];
                        return true;
                    }
                }
                return true;
            });
        }
        return $tokens;
    }

    public function updateTokenForEmandateRecurringDetails(Entity $token, array $configs)
    {
        $emandateConfig = [
            "emandate_configs" => $configs
        ];

        $token->setNotes($emandateConfig);
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
        // if token is received rejected in ack files, not marking it acknowledged in this case
        if ((empty($gatewayData[Entity::ACKNOWLEDGED_AT]) === false)
                and ($gatewayRecurringStatus !== RecurringStatus::REJECTED))
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
        $existingCards = (new Card\Core)->findAllExistingCards($card, $token->merchant);

        if ($existingCards === null)
        {
            return null;
        }

        $cardIds = $existingCards->pluck(Entity::ID);

        return $this->repo->token->getByMethodAndCustomerIdAndCardIds(
            $token->getMethod(), $token->customer, $cardIds, $token->merchant);
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

    /**
     * Fetches global tokens associated with a global customer.
     *
     * @param Customer\Entity $customer
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     */
    protected function fetchGlobalTokensByCustomer(Customer\Entity $customer, Merchant\Entity $merchant): Base\PublicCollection
    {
        if ($customer->isLocal()) {
            throw new Exception\LogicException('Please use fetchTokensByCustomer() to fetch local tokens.');
        }

        $tokens = $this->fetchTokensByCustomer($customer, $merchant);

        return $tokens->filter(static function (Entity $token) {
            return $token->isGlobal();
        });
    }

    /**
     * Fetches global customer local card tokens and other non-card global tokens
     * associated with a global customer.
     *
     * @param Customer\Entity $customer
     * @param Merchant\Entity $merchant
     *
     * @return Base\PublicCollection
     */
    protected function fetchGlobalCustomerTokens(Customer\Entity $customer, Merchant\Entity $merchant): Base\PublicCollection
    {
        if ($customer->isLocal()) {
            throw new Exception\LogicException('Please use fetchTokensByCustomer() to fetch local tokens.');
        }

        $tokens = $this->fetchTokensByCustomer($customer, $merchant);

        $merchantTokens = $this->removeOtherMerchantTokens($tokens, $merchant->getId());

        return $this->removeGlobalCardTokens($merchantTokens);
    }

    /**
     * Removes customer tokens of other merchants
     * This case occurs on the global customers as the same customer is used across other merchants
     *
     * @param Base\PublicCollection $tokens
     *
     * @return Base\PublicCollection
     */
    protected function removeOtherMerchantTokens(Base\PublicCollection $tokens, string $merchantId): Base\PublicCollection
    {
        return $tokens->filter(static function (Entity $token) use ($merchantId) {
            return $token->getMerchantId() === $merchantId || $token->isGlobal();
        })->values();
    }

    /**
     * Removes global card tokens as RBI tokenisation guidelines
     * don't allow us to use global card tokens.
     *
     * @param Base\PublicCollection $tokens
     *
     * @return Base\PublicCollection
     */
    protected function removeGlobalCardTokens(Base\PublicCollection $tokens): Base\PublicCollection
    {
        return $tokens->filter(static function (Entity $token) {
            return (!$token->isCard()) || $token->isLocal();
        })->values();
    }

    /**
     * This method filters out global tokens from the given input tokens if
     * local tokens for the same card exist.
     *
     * NOTE: This method would only work if all input tokens belong to the
     *       same customer.
     *
     * @param Base\PublicCollection|Entity[] $tokens List of tokens that belong to a specific customer
     * @param Merchant\Entity $merchant              Merchant whose local card tokens need to be
     *                                               prioritised over global card tokens
     *
     * @return Base\PublicCollection
     */
    protected function prioritiseLocalOverGlobalCardTokens(Base\PublicCollection $tokens, Merchant\Entity $merchant): Base\PublicCollection
    {
        $merchantId = $merchant->getId();

        $merchantTokens = $tokens->filter(static function (Entity $token) use ($merchantId) {
            // Remove local tokens of other merchants
            return $token->isGlobal() || ($token->getMerchantId() === $merchantId);
        });

        $cardTokens = $this->suppressGlobalCardTokens($merchantTokens);

        $nonCardTokens = $merchantTokens->filter(static function (Entity $token) {
            return !$token->isCard();
        });

        return $cardTokens->concat($nonCardTokens);
    }

    /**
     * NOTE: This method is only useful if all input tokens belong to same
     * customer.
     *
     * @param Base\PublicCollection|Entity[] $tokens
     *
     * @return array
     */
    protected function separateGlobalAndLocalCardTokens(Base\PublicCollection $tokens): array
    {
        $cardTokens = [];

        // Filter global & local tokens which belong to the same card
        foreach ($tokens as $token) {
            if ($token->isCard()) {
                $cardKey = $token->card->getCardDetailsAsKey();

                if ($token->isGlobal()) {
                    $cardTokens[$cardKey]['global'][] = $token;

                    continue;
                }

                $cardTokens[$cardKey]['local'][] = $token;
            }
        }

        return $cardTokens;
    }

    /**
     * Suppress/Replace global card tokens with local tokens if local tokens
     * for same card exist. If no local token for the same card exist then the
     * global tokens are returned as-is.
     *
     * NOTE: This method is only useful if all input tokens belong to same
     * customer.
     *
     * @param Base\PublicCollection $tokens
     *
     * @return Base\PublicCollection
     */
    protected function suppressGlobalCardTokens(Base\PublicCollection $tokens): Base\PublicCollection
    {
        $cardTokens = $this->separateGlobalAndLocalCardTokens($tokens);

        $finalCardTokens = new Base\PublicCollection();

        foreach ($cardTokens as $value) {
            if (!empty($value['local'])) {
                // Append local tokens to the response & ignore global tokens
                // belonging to the same card.
                foreach ($value['local'] as $token) {
                    $finalCardTokens->add($token);
                }

                continue;
            }

            foreach ($value['global'] as $token) {
                $finalCardTokens->add($token);
            }
        }

        return $finalCardTokens;
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

            $requestStartAt = millitime();

            $existingTokens = $this->repo->token->getByMethodAndCustomerIdIsNull($token->getMethod(),$token->getMerchantId(), $token->card->getVaultToken());

            $this->trace->info(
                TraceCode::EXISTING_TOKENS_EXECUTION_TIME,
                [
                    'existing_tokens_count'     => count($existingTokens),
                    'fetch_time_ms'             => millitime() - $requestStartAt,
                ]);
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

    /**
     * @param Entity[] $existingTokens
     * @param Entity   $newToken
     *
     * @return Entity|null
     */
    protected function validateExistingTokenCard($existingTokens, $newToken): ?Entity
    {
        if (!$newToken->hasCard())
        {
            return null;
        }

        $existingTokens = $this->removeNonActiveTokenisedCardTokens($existingTokens);

        $isNewTokenNetworkTokenised = !$newToken->card->isRzpTokenisedCard();

        foreach ($existingTokens as $token)
        {
            if (!$token->hasCard())
            {
                continue;
            }

            $isExistingTokenNetworkTokenised = !$token->card->isRzpTokenisedCard();

            // If both cards are tokenised or both are non tokenised,
            // then we can use vault token to do dedupe check
            if (($isNewTokenNetworkTokenised === $isExistingTokenNetworkTokenised) &&
                ($newToken->getMerchantId() === $token->getMerchantId())
            ) {
                if ($newToken->card->getVaultToken() === $token->card->getVaultToken())
                {
                    return $token;
                }
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

        $expiryMonth = (string) $newCard->getTokenExpiryMonth();

        $expiryYear = (string) $newCard->getTokenExpiryYear();

        foreach ($cards as $card)
        {
            if (((string) $card->getTokenExpiryMonth() === $expiryMonth) and
                ((string) $card->getTokenExpiryYear()  === $expiryYear))
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

        if ($token->getRecurringStatus() !== RecurringStatus::CONFIRMED) {
            throw new Exception\BadRequestValidationFailureException(
                'token is not in appropriate state to pause', null, [
                'token_id' => $token->getId(),
            ]);
        }

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

        if ($oldRecurringStatus !== RecurringStatus::PAUSED)
           {
            throw new Exception\BadRequestValidationFailureException(
                'token is not in appropriate state to resume', null, [
                'token_id' => $token->getId(),
            ]);
        }

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

    public function createTokenAndTokenizedCard($input, $merchantPushProvisioning)
    {
        if($merchantPushProvisioning !== null) {
            $this->merchant = $merchantPushProvisioning;
        }
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

        return $this->createTokenforTokenisedCard($card, $serviceProviderTokens, $this->merchant, $customer, $input);
    }

    public function createTokenforTokenisedCard($card, $serviceProviderTokens, $merchant, $customer = null, $input = [])
    {
        $this->trace->info(
            TraceCode::TOKEN_CREATE_FOR_TOKENIZED_CARD
        );

        $token = new Token\Entity;

        $noOfTokens = count($serviceProviderTokens);

        $tokenStatus = null;

        if ($noOfTokens > 1)
        {
            $tokenStatus = $this->fetchDualTokenStatus($serviceProviderTokens);
        }
        else {
            // todo: change to golabal status when token v/s card design is finalized
            $tokenStatus = $serviceProviderTokens[0]['status'];
        }

        $createTokenInput = [
            Entity::METHOD      => Method::CARD,
            Entity::CARD_ID     => $card->getId(),
            Entity::STATUS      => $tokenStatus,
            Entity::NOTES       => $input['notes'] ?? [],
        ];

        $token->build($createTokenInput);

        $token->card()->associate($card);

        $token->setExpiredAt($card->getTokenExpiryTimestamp());

        $token->merchant()->associate($merchant);

        if (empty($customer) === false)
        {
            $token->customer()->associate($customer);
        }

        if($input['via_push_provisioning'] === true)
        {
            $token->setSource(TokenConstants::ISSUER);
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

    public function createCsvFileFromDataLake()
    {

        $currentDate = Carbon::now(Timezone::IST)->format('Y-m-d');
        $fileName = 'token_hq' . '_' . $currentDate;
        $dataLakeQuery = sprintf(TokenConstants::DATA_LAKE_TOKEN_HQ_AGGREGATE_DATA,$currentDate);

        $lakeData = $this->app['datalake.presto']->getDataFromDataLake($dataLakeQuery);

        $results = [];

        foreach($lakeData as $record)
        {
            $results[] = $record;
        }
        $url = $this->createCsvFile($results , $fileName, null, 'files/batch');

        $uploadedFile = new UploadedFile(
                $url,
                $fileName.'csv',
                'text/csv',
                null,
                true);

        return $uploadedFile;
  }

    public function createTokenForRearch($card, $cardInput, $merchant, $payment, $customer)
    {
        $networkCode = $card->getNetworkCode();

        $onboardedNetworks = (new Terminal\Core())->getMerchantTokenisationOnboardedNetworks($merchant->getId());

        if (in_array($networkCode, $onboardedNetworks,true) === false) {

            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_MERCHANT_NOT_ONBOARDED_FOR_TOKENISATION);

        }

        list($card, $serviceProviderTokens) = (new Card\Core)->migrateToTokenizedCard($card, $merchant, $cardInput, $payment);

        return $this->createTokenforTokenisedCard($card, $serviceProviderTokens, $payment->merchant, $customer);
    }

    public function fetchDualTokenStatus($serviceProviderTokens){
        $tokenStatus = null;
        if ($serviceProviderTokens[0]['status'] === 'active' || $serviceProviderTokens[1]['status'] === 'active')
        {
            $tokenStatus = 'active';
        }
        else if ($serviceProviderTokens[0]['status'] === 'initiated' || $serviceProviderTokens[1]['status'] === 'initiated')
        {
            $tokenStatus = 'initiated';
        }
        else if ($serviceProviderTokens[0]['status'] === 'deactivated' || $serviceProviderTokens[1]['status'] === 'deactivated')
        {
            $tokenStatus = 'deactivated';
        }
        else if ($serviceProviderTokens[0]['status'] === 'deleted' || $serviceProviderTokens[1]['status'] === 'deleted')
        {
            $tokenStatus = 'deleted';
        }
        return $tokenStatus;
    }

    public function migrateToTokenizedCard($token, $cardInput, $payment = null, $isAsync = false, $asyncTokenisationJobId = null)
    {
        $cardInput += [
            'merchant_token' => $token->getId(),
            'async'          => $isAsync,
            'customer_id'    => $token->getCustomerId(),
            'email'          => ($payment !== null) ? $payment->getEmaiL() : ""
        ];

        list($card, $serviceProviderTokens) = (new Card\Core)->migrateToTokenizedCard($token->card, $token->merchant, $cardInput, $payment, $asyncTokenisationJobId);

        $this->trace->info(
            TraceCode::TOKEN_MIGREATE_FOR_TOKENIZED_CARD);

        $noOfTokens = count($serviceProviderTokens);
        $tokenStatus = null;

        if ($noOfTokens > 1)
        {
            $tokenStatus = $this->fetchDualTokenStatus($serviceProviderTokens);
        }
        else {
            $tokenStatus = $serviceProviderTokens[0]['status'];
        }

        $token->setStatus($tokenStatus);

        $token->card()->associate($card);

        $token->setExpiredAt($card->getTokenExpiryTimestamp());

        $this->repo->saveOrFail($card);

        $this->repo->saveOrFail($token);
    }

    public function getIIN($input)
    {
        if(isset($input["number"]) == false){
            return [null, null];
        }

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

    public function setInstrumentationInput(& $input, $iin, $isTokenized, $internalServiceRequest)
    {
        if(!empty($this->merchant))
        {
            $input += [
                "merchant"     => [
                    "id"       => $this->merchant->getId(),
                ],
            ];
        }

        if(empty($iin) === true || $iin === null)
        {
            return ;
        }

        $IINEntity = $this->repo->card->retrieveIinDetails($iin);

        $input += [
            "iin" => [
                "iin"      => $iin,
                "network"  => (is_null($IINEntity) === true) ? null : $IINEntity->getNetwork(),
                "issuer"   => (is_null($IINEntity) === true) ? null : $IINEntity->getIssuer(),
                "type"     => (is_null($IINEntity) === true) ? null : $IINEntity->getType(),
            ],
            "tokenised"    => $isTokenized,
            "internal_service_request" => $internalServiceRequest,
        ];

        if($isTokenized)
        {
            $input += [
                "card_data" => [
                    "token_iin" => substr($input['number'], 0, 9),
                ]
            ];
        }

    }

    // Pass internal Service request as false only if Par is being called from any Merchant/External
    public function fetchParValue(& $input, $internalServiceRequest)
    {
        (new Validator)->validateInput(Validator::FETCH_PAR_VALUE, $input);

        $network = isset($input["network"]) === true ? $input["network"] : null;

        list($iin, $isTokenized) = $this->getIIN($input);

        $this->setInstrumentationInput($input, $iin, $isTokenized, $internalServiceRequest);

        (new Token\Event())->pushEvents($input, Event::PAR_API, "_REQUEST_RECEIVED");

        if($iin !== null)
        {
            $network = Card\Network::detectNetwork($iin);

            if(!isset($network) || $network === "UNKNOWN"){
                throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS, ["iin" => $input["card_iin"]]);
            }

            $network = Card\Network::$fullName[$network];

            $input["network"] = strtolower($network);
        }

        if(((new Card\Core())->checkIfFetchingParApplicable($network, $isTokenized)) === false)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ERROR);

        }

        return [$network, (new Card\Core)->fetchParValue($input)];
        // hit the vault with number and the network
    }

    public function createNetworkToken($input)
    {
        $customer = null;

        (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN, $input);

        if ($this->isNetworkRuPay($input[Entity::CARD]) && (empty($input[Token\Entity::AUTHENTICATION]) === false))
        {
            (new Validator)->validateInput(Validator::CREATE_NETWORK_TOKEN_AUTHENTICAION_DATA_RUPAY, $input[Token\Entity::AUTHENTICATION]);
        }
        else if (empty($input[Token\Entity::AUTHENTICATION]) === false)
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

    public function fetchCryptogram($input, $merchant, $token)
    {
        if (empty($input['token_id']) === false)
        {
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


    public function fetchToken($token, $internalServiceRequest)
    {
        $response = (new Card\Core)->fetchToken($token->card, $internalServiceRequest);

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

    public function fetchCardMerchantListByFingerprint($fingerprint, $account_ids)
    {
        $default_limit = 1000;

        $this->trace->info(
            TraceCode::FETCH_MERCHANT_WITH_TOKEN_LIST
        );

        try
        {
            $merchant_ids = Merchant\Account\Entity::verifyIdAndStripSignMultiple($account_ids);

            // find card entities by fingerprint
            $cardMerchantIdsWithTokenPresent = $this->repo->card->findCardMerchantIdsByFingerprint($fingerprint, $merchant_ids, $default_limit);

            return array_map(
                function ($ele){
                    return 'acc_' . $ele;
                },
                $cardMerchantIdsWithTokenPresent
            );
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::SAVED_CARD_TOKEN_NOT_FOUND, []);

            return false;
        }
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

        $tokenizationGatewayCounts = count($gateways);
        $successOnboardedGateways = 0;

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
            $logData =  ['gateway' => $gateway, 'merchant' => $merchant->getId()];

            $this->trace->info(TraceCode::TOKENIZATION_MERCHANT_ONBOARD, $logData);

            try
            {
                $data = $this->app['terminals_service']->initiateOnboarding($merchant->getId(), $gateway, null, null, [], $input);

                if ($data == null)
                {
                    $this->trace->error(TraceCode::TOKENIZATION_MERCHANT_ONBOARD_FAILED, $logData);

                    continue;
                }

                $successOnboardedGateways++;

                $this->trace->info(TraceCode::TOKENIZATION_MERCHANT_ONBOARD_SUCCESS, $logData);
            }
            catch (\Exception $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::TOKENIZATION_MERCHANT_ONBOARD_FAILED,
                    [
                        'gateway' => $gateway,
                        'merchant' => $merchant->getId()
                    ]);
            }
        }

        $this->trace->info(TraceCode::TOKENIZATION_MERCHANT_ONBOARD_COMPLETE,
            [
                'total'     =>  $tokenizationGatewayCounts,
                'success'   => $successOnboardedGateways,
            ]);
    }

    /**
     * @param Base\PublicCollection|Entity[] $tokens
     *
     * @return Base\PublicCollection|Entity[]
     */
    public function addConsentFieldInTokens($tokens)
    {

        foreach ($tokens as $token)
        {
            if($token->getMethod() === Entity::CARD)
            {
                $acknowledgedAt = $token->getAcknowledgedAt();

                if (!empty($acknowledgedAt) && $token->isLocal()) {
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

    public function updateTokenOnAuthorized($tokenData) {

        if (empty($tokenData['token_id']) === false)
        {
            $token = $this->repo->token->findOrFailPublic($tokenData['token_id']);
        } else {
            $this->trace->info(TraceCode::SAVED_CARD_TOKEN_NOT_FOUND,
                [
                    'token_data' => $tokenData
                ]);
            return null;
        }
        $token->setUsedAt(Carbon::now()->getTimestamp());

        $token->incrementUsedCount();

        if (($token->isRecurring() === false) and
            ($token->getRecurringStatus() === null))
        {
            $token->setRecurringStatus(Token\RecurringStatus::NOT_APPLICABLE);
        }
        $this->repo->saveOrFail($token);

        return  $token;
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
            !($array[$param] === null || $array[$param] === 0 || $array[$param] === ''))
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

    public function showTokenisationConsentViewForExistingSavedCardwithoutCustomer($token): bool
    {
        try
        {
            $tokenEntity = $this->repo->token->findByPublicId($token);

            if(isset($tokenEntity) === false)
            {
                return true;
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

            return true;
        }
    }


    protected function stripSptPrefix($id)
    {
        $prefix = 'spt';

        $delimiter = '_';

        return substr($id, strlen($prefix . $delimiter));
    }

    public function isNetworkRuPay($card)
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
    private function executeDataLakeQueryToFetchConsentReceivedTokenIds(string $merchantId, array $onboardedNetworkNames, int $offset, bool $onlyRecurring): array
    {
        $onboardedNetworkNamesInString = implode("','", $onboardedNetworkNames);

        $rzpVaultsInString = implode("','", [Card\Vault::RZP_ENCRYPTION, Card\Vault::RZP_VAULT]);

        if(!$onlyRecurring)
        {
            $rawQueryBuilder = " SELECT t.id " .
                " FROM hive.realtime_hudi_api.tokens t " .
                " INNER JOIN hive.realtime_hudi_api.cards c " .
                " ON t.card_id = c.id " .
                " WHERE  t.method = 'card' " .
                " AND t.acknowledged_at IS NOT NULL " .
                " AND c.international = 0 " .
                " AND t.merchant_id = '%s' " .
                " AND t.recurring = 0 " .
                " AND c.network IN ('%s') " .
                " AND c.vault IN ('%s') " .
                " AND t.deleted_at IS NULL " .
                " ORDER BY t.id " .
                " OFFSET %d LIMIT %d";
        }
        else
        {
            $rawQueryBuilder = " SELECT t.id " .
                " FROM hive.realtime_hudi_api.tokens t " .
                " INNER JOIN hive.realtime_hudi_api.cards c " .
                " ON t.card_id = c.id " .
                " WHERE t.method = 'card' " .
                " AND t.acknowledged_at IS NOT NULL " .
                " AND c.international = 0 " .
                " AND t.merchant_id = '%s' " .
                " AND t.recurring = 1 " .
                " AND c.network IN ('%s') " .
                " AND c.vault IN ('%s') " .
                " AND t.deleted_at IS NULL " .
                " ORDER BY t.id " .
                " OFFSET %d LIMIT %d";
        }

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
    public function fetchConsentReceivedLocalTokenIdsForTokenisation(string $merchantId, int $offset, int $retryCount = 0, bool $recurring = false): array
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

            return $this->executeDataLakeQueryToFetchConsentReceivedTokenIds($merchantId, $onboardedNetworkNames, $offset, $recurring);
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
                return $this->fetchConsentReceivedLocalTokenIdsForTokenisation($merchantId, $offset, $retryCount + 1, $recurring);
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
            SavedCardTokenisationJob::dispatch($this->mode, $tokenId, $asyncTokenisationJobId, null);
        }
    }

    /**
     * @param string $merchantId The Primary key of the Merchant whose valid
     *                           Token Entity Ids are being fetched
     * @param array  $tokenIds   The Primary keys of Token entity (or) `token`
     *                           column values from Token entity
     *
     * @return array An array of Primary Keys of Token entity
     */
    public function getValidTokensForTokenisation(string $merchantId, array $tokenIds): array
    {
        $validTokenEntities = $this->repo->token->filterMerchantCardTokens($merchantId, $tokenIds);

        $validTokenIds = [];
        $validTokens = [];

        foreach ($validTokenEntities as $validToken)
        {
            $validTokenIds[] = $validToken->getId();
            $validTokens[] = $validToken->getToken();
        }

        $invalidTokenIds = array_values(array_diff($tokenIds, $validTokenIds));
        $invalidTokens   = array_values(array_diff($tokenIds, $validTokens));

        $this->trace->info(TraceCode::BULK_LOCAL_TOKENISATION_INVALID_TOKENS, [
            'merchantId'            => $merchantId,
            'totalTokensCount'      => count($tokenIds),
            'validTokenIdsCount'    => count($validTokenIds),
            'invalidTokenIdsCount'  => count($invalidTokenIds),
            'invalidTokensIds'      => $invalidTokenIds,
            'invalidTokenCount'     => count($invalidTokens),
            'invalidTokens'         => $invalidTokens,
            'notes'                 => 'Consider invalidTokens or invalidTokensIds basis on what you passed as input',
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
            $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                'method'                => $token->getMethod(),
                'hasBeenAcknowledged'   => $token->hasBeenAcknowledged(),
                'token'      => $token->getId()
            ]);
            return false;
        }

        if ($token->isExpired() === true)
        {
            $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                'expired'   => $token->isExpired(),
                'token'      => $token->getId()
            ]);

            return false;
        }

        $card = $token->card;

        // If card is already tokenised or card is international card, then it is not applicable for tokenisation
        if (($card->isRzpTokenisedCard() === false) or
            ($card->isInternational() === true))
        {
            $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                'rzptokenizedcard'   => $card->isRzpTokenisedCard(),
                'isInternational'    => $card->isInternational(),
                'token'      => $token->getId()
            ]);

            return false;
        }

        $networkCode = $card->getNetworkCode();
        $issuer = NULL;

        if($token->merchant->isFeatureEnabled(Feature::ISSUER_TOKENIZATION_LIVE) === true) {
            $issuer = $card->getIssuer();
        }


        if (in_array($networkCode, Card\Network::NETWORKS_SUPPORTING_TOKEN_PROVISIONING, true) === false) {
            return false;
        }

        $onboardedNetworks = (new Terminal\Core())->getMerchantTokenisationOnboardedNetworks($token->getMerchantId());

        if (in_array($networkCode, $onboardedNetworks,true) === false && in_array($issuer, $onboardedNetworks,true) === false) {

            $errorCode = ErrorCode::BAD_REQUEST_MERCHANT_NOT_ONBOARDED_FOR_TOKENISATION;

            $this->trace->info(TraceCode::TRACE_TOKEN_MIGRATION_FAILURE, [
                'token'      => $token->getId()
            ]);

            $this->updateTokenStatus($token, Token\Constants::FAILED, $errorCode);
        }

        return in_array($networkCode, $onboardedNetworks, true) || in_array($issuer, $onboardedNetworks, true);
    }

    /**
     * builds card input for tokenisation
     *
     * @param Card\Entity $card
     * @return array
     */
    public function buildCardInputForTokenisation(Card\Entity $card): array
    {
        return [
            'cvv'          => Card\Entity::getDummyCvv($card->getNetworkCode()),
            'last4'        => $card->getLast4() ?? "0000",
            'expiry_month' => $card->getExpiryMonth() ?? "0",
            'expiry_year'  => $card->getExpiryYear() ?? "9999",
            'emi'          => $card->getEmi() ?? false,
            'iin'          => $card->getIin() ?? "",
            'name'         => $card->getName(),
            'authentication_reference_number' => $card->getReference4()
        ];
    }

    public function buildCardInputForPar($number, $card): array
    {
        $result = [];
        if(isset($number) === true)
        {
            $result = [
                "number" => $number
            ];
        }
        else {
            $result = [
                "vault"   => $card->getCardVaultToken(),
                "network" => $card->getNetwork()
            ];
        }

        return $result;
    }

    public function fetchTokenDetailsForCustomer(Customer\Entity $customer) : array
    {
        $cardsList = [];

        $tokenIdList = [];

        $merchantsList = [];

        $merchantIdMapping = [];

        $tokens = $this->repo->token->getByCustomer($customer);

        $cardTokens = $tokens->filter(static function (Entity $token) {
            // Remove non card tokens
            return $token->isCard();
        });

        $cardTokens = $this->removeGlobalCardTokens($cardTokens);

        $cardTokens = $this->removeNonCompliantCardTokens($cardTokens);

        $cardTokens = $this->removeNonActiveTokenisedCardTokens($cardTokens);

        foreach ($cardTokens as $token)
        {
            $tokenIdList[] = $token->getId();
        }

        $this->trace->info(TraceCode::FETCHED_CUSTOMER_TOKENS, [
            Card\Constants::TOKENS  => $tokenIdList
        ]);

        foreach ($cardTokens as $token)
        {
            $merchant = $token->merchant;

            (new Merchant\Core())->addMerchantDetailsOfToken($merchant, $merchantsList, $merchantIdMapping);

            (new Card\Core())->addCardDetailsOfToken($token, $cardsList, $merchantIdMapping);
        }

        return [
            Customer\Entity::CONTACT => $customer->contact,
            Entity::CARDS => array_values($cardsList),
            'mappings' => [
                Merchant\Constants::MERCHANTS => $merchantsList
            ]
        ];
    }

    public function deleteTokensForCustomer(array $input, string $customerId) : array
    {
        $this->trace->info(TraceCode::TOKENS_TO_BE_DELETED, [
            Card\Constants::TOKENS  => $input
        ]);

        (new Validator())->validateDeleteTokensInput($input, $customerId);

        $deletedTokensList = [];

        $tokens = $this->repo->token->getByTokensAndCustomer($input['tokens'], $customerId);

        foreach ($tokens as $token)
        {
            $tokenId = $token->getId();

            try
            {
                $this->repo->token->deleteOrFail($token);

                $deletedTokensList['success'][] = $tokenId;
            }
            catch(\Exception $e)
            {
                $this->trace->traceException($e);

                $deletedTokensList['errors'][] = $tokenId;
            }
        }

        $this->trace->info(TraceCode::TOKENS_DELETED, [
            $deletedTokensList
        ]);

        return $deletedTokensList;
    }

    /**
     * @param $input
     * @param $bulkRequestUniqueId
     * @param $batchId
     * @return Base\PublicCollection
     */
    public function bulkCreateLocalTokensFromConsents($input, $bulkRequestUniqueId, $batchId): Base\PublicCollection
    {
        $result = new Base\PublicCollection;

        $failedTokensCount = 0;
        $notApplicableTokensCount = 0;

        $tokenIds = array_unique(array_column($input, 'tokenId'));
        $merchantIds = array_unique(array_column($input, 'merchantId'));

        $tokensMap = [];
        $merchantsMap = [];

        $tokens = $this->repo->token->findManyOnReadReplica($tokenIds);
        $merchants = $this->repo->merchant->findManyOnReadReplica($merchantIds);

        unset($tokenIds);
        unset($merchantIds);

        foreach ($tokens as $token) {
            $tokensMap[$token->getId()] = $token;
        }

        foreach ($merchants as $merchant) {
            $merchantsMap[$merchant->getId()] = $merchant;
        }

        foreach ($input as $consentData)
        {
            $idempotencyKey = $consentData[TokenConstants::BATCH_IDEMPOTENCY_KEY] ?? '';

            $properties = [
                TokenConstants::BATCH_IDEMPOTENCY_KEY => $idempotencyKey,
                'batch_id'                            => $batchId,
                'bulk_request_unique_id'              => $bulkRequestUniqueId,
            ];

            try
            {
                $tokenId = $consentData['tokenId'];
                $merchantId = $consentData['merchantId'];

                $token = $tokensMap[$tokenId] ?? null;

                $merchant = $merchantsMap[$merchantId] ?? null;

                $validationData = (new Validator())->validateGlobalTokenToLocalTokenMigrationInput($token, $merchant);

                if ($validationData['valid'] === false)
                {
                    $output = $this->handleInvalidToken($merchantId, $tokenId, $validationData, $properties);

                    $result->push($output);

                    ++$notApplicableTokensCount;

                    continue;
                }

                $localToken = $this->createLocalTokenFromGlobalToken($token, $merchant);

                $output = $this->handleTokenCreateSuccess($tokenId, $localToken['id'], $merchantId, $properties);

                $result->push($output);
            }
            catch (\Throwable $e)
            {
                $output = $this->handleTokenCreateError($tokenId, $merchantId, $properties, $e);

                $result->push($output);

                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::CREATE_LOCAL_TOKEN_FROM_CONSENT_ERROR
                );

                ++$failedTokensCount;
            }
        }

        $this->trace->info(TraceCode::BULK_CREATE_LOCAL_TOKENS_FROM_CONSENT_SUCCESS, [
            'inputTokensCount'         => count($input),
            'notApplicableTokensCount' => $notApplicableTokensCount,
            'failedTokensCount'        => $failedTokensCount,
            'bulkRequestUniqueId'      => $bulkRequestUniqueId,
            'fileId'                   => $batchId,
        ]);

        return $result;
    }

    /**
     * @param string $merchantId
     * @param string $tokenId
     * @param array  $validationData
     * @param array  $properties
     *
     * @return array
     */
    protected function handleInvalidToken(
        string $merchantId,
        string $tokenId,
        array $validationData,
        array $properties
    ): array
    {
        $this->trace->info(TraceCode::CREATE_LOCAL_TOKEN_FROM_CONSENT_INVALID_TOKEN, [
            'tokenId'                => $tokenId,
            'merchantId'             => $merchantId,
            'Reason'                 => $validationData['reason'],
            'bulkRequestUniqueId'    => $properties['bulk_request_unique_id'],
            'fileId'                 => $properties['batch_id'],
            'duplicateTokenIfExists' => $validationData['existing_token_id'] ?? '',
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::ASYNC_TOKENISATION_CREATE_GLOBAL_CUSTOMER_LOCAL_TOKENS_INVALID,
            [
                'token_id'                  => $tokenId,
                'merchant_id'               => $merchantId,
                'bulk_request_unique_id'    => $properties['bulk_request_unique_id'],
                'file_id'                   => $properties['batch_id'],
                'reason'                    => $validationData['reason'],
                'duplicate_token_if_exists' => $validationData['existing_token_id'] ?? '',
            ]
        );

        return [
            'merchantId'                          => $merchantId,
            'tokenId'                             => $tokenId,
            TokenConstants::BATCH_SUCCESS         => false,
            TokenConstants::BATCH_IDEMPOTENCY_KEY => $properties[TokenConstants::BATCH_IDEMPOTENCY_KEY],
            TokenConstants::BATCH_ERROR           => [
                TokenConstants::BATCH_ERROR_DESCRIPTION => $validationData['reason'],
            ],
        ];
    }

    /**
     * @param string $tokenId
     * @param string $localTokenId
     * @param string $merchantId
     * @param array  $properties
     *
     * @return array
     */
    protected function handleTokenCreateSuccess(
        string $tokenId,
        string $localTokenId,
        string $merchantId,
        array $properties
    ): array
    {
        $this->trace->info(TraceCode::CREATE_LOCAL_TOKEN_FROM_CONSENT_SUCCESS, [
            'tokenId'             => $tokenId,
            'createdLocalTokenId' => $localTokenId,
            'merchantId'          => $merchantId,
            'bulkRequestUniqueId' => $properties['bulk_request_unique_id'],
            'fileId'              => $properties['batch_id'],
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::ASYNC_TOKENISATION_CREATE_GLOBAL_CUSTOMER_LOCAL_TOKENS_SUCCESS,
            [
                'token_id'               => $tokenId,
                'created_local_token_id' => $localTokenId,
                'merchant_id'            => $merchantId,
                'bulk_request_unique_id' => $properties['bulk_request_unique_id'],
                'file_id'                => $properties['batch_id'],
            ]
        );

        return [
            'merchantId'                          => $merchantId,
            'tokenId'                             => $tokenId,
            TokenConstants::BATCH_SUCCESS         => true,
            TokenConstants::BATCH_IDEMPOTENCY_KEY => $properties[TokenConstants::BATCH_IDEMPOTENCY_KEY],
        ];
    }

    /**
     * @param string    $tokenId
     * @param string    $merchantId
     * @param array     $properties
     * @param Throwable $error
     *
     * @return array
     */
    protected function handleTokenCreateError(
        string $tokenId,
        string $merchantId,
        array $properties,
        Throwable $error
    ): array
    {
        $this->trace->info(TraceCode::CREATE_LOCAL_TOKEN_FROM_CONSENT_FAILED, [
            'tokenId'             => $tokenId,
            'merchantId'          => $merchantId,
            'bulkRequestUniqueId' => $properties['bulk_request_unique_id'],
            'fileId'              => $properties['batch_id'],
        ]);

        app('diag')->trackTokenisationEvent(
            EVENTCODE::ASYNC_TOKENISATION_CREATE_GLOBAL_CUSTOMER_LOCAL_TOKENS_FAILED,
            [
                'token_id'               => $tokenId,
                'merchant_id'            => $merchantId,
                'bulk_request_unique_id' => $properties['bulk_request_unique_id'],
                'file_id'                => $properties['batch_id'],
                'error_details'          => json_encode(
                    [
                        'message' => $error->getMessage(),
                        'code'    => $error->getCode(),
                    ]
                ),
            ]
        );

        return [
            TokenConstants::BATCH_IDEMPOTENCY_KEY => $properties[TokenConstants::BATCH_IDEMPOTENCY_KEY],
            TokenConstants::BATCH_SUCCESS         => false,
            'merchantId'                          => $merchantId,
            'tokenId'                             => $tokenId,
            TokenConstants::BATCH_ERROR           => [
                TokenConstants::BATCH_ERROR_DESCRIPTION => $error->getMessage(),
                TokenConstants::BATCH_ERROR_CODE        => $error->getCode(),
            ],
        ];
    }

    /**
     * @param  Entity           $globalToken
     * @param  Merchant\Entity  $merchant    Merchant to be associated
     *
     * @return Entity
     * @throws \Exception
     */
    public function createLocalTokenFromGlobalToken(Entity $globalToken, Merchant\Entity $merchant , $gateway=null): Entity
    {
        $localToken = $this->createGlobalOrLocalTokenFromExistingToken($globalToken, $merchant,$gateway);

        return $localToken;
    }

    /**
     * @param  Entity  $localToken
     *
     * @return Entity
     * @throws \Exception
     */
    public function createGlobalTokenFromLocalToken(Entity $localToken , $gateway=null): Entity
    {
        $globalMerchant = $this->repo->merchant->getSharedAccount();

        $globalToken = $this->createGlobalOrLocalTokenFromExistingToken($localToken, $globalMerchant,$gateway);

        return $globalToken;
    }

    /**
     * Can be used to create dual vault token from existing global token
     * Or create global token from existing dual vault token.
     * Creation of global or local token is decided by the merchant entity
     * being passed in the function argument.
     *
     * Dual Vault Token - Token whose customer id is global customer id, but
     * merchant id is the actual merchant id instead of global merchant id.
     *
     * @param  Entity          $existingToken
     * @param  Merchant\Entity $merchantToBeAssociated - to decide creation of global or local token
     *
     * @return Entity
     * @throws \Exception
     */
    protected function createGlobalOrLocalTokenFromExistingToken(Entity $existingToken, Merchant\Entity $merchantToBeAssociated , $gateway=null): Entity
    {
        try
        {
            $this->trace->info(TraceCode::GLOBAL_LOCAL_TOKEN_CREATION_REQUEST, [
                'tokenId'   => $existingToken->getId(),
                'isGlobal'  => $existingToken->isGlobal(),
            ]);

            $token = $this->checkIfSimilarTokenCardAlreadyExistsOnCustomerAndMerchant(
                $existingToken,
                $existingToken->customer,
                $merchantToBeAssociated
            );

            if (isset($token))
            {
                $this->trace->info(TraceCode::GLOBAL_LOCAL_TOKEN_ALREADY_EXISTS, [
                    'tokenId'           => $existingToken->getId(),
                    'isGlobal'          => $existingToken->isGlobal(),
                    'existingTokenId'   => $token->getId(),
                ]);

                return $token;
            }

            $token = $existingToken->replicate();

            $token->merchant()->associate($merchantToBeAssociated);
            $token->generateToken([]);
            $token->setStatus(null);
            $token->setUsedCount(0);
            $token->setUsedAt(Carbon::now()->getTimestamp());

            $existingCard = $existingToken->card;

            $actualCardNumber = (new Card\CardVault)->getCardNumber($existingCard->getVaultToken(),$existingCard->toArray(),$gateway);

            $cardInput = [
                Card\Entity::NUMBER           => $actualCardNumber,
                Card\Entity::NAME             => $existingCard->getName(),
                Card\Entity::EXPIRY_MONTH     => $existingCard->getExpiryMonth(),
                Card\Entity::EXPIRY_YEAR      => $existingCard->getExpiryYear(),
                Card\Entity::CVV              => $input['card']['cvv'] ?? Card\Entity::getDummyCvv($existingCard->getNetworkCode()),
                Card\Entity::VAULT            => Card\Vault::RZP_VAULT,
            ];

            $cardCore = new Card\Core;

            $cardCore->createAndReturnWithSensitiveData($cardInput, $merchantToBeAssociated, false, false);

            $card = $cardCore->getCard();

            $token->card()->associate($card);

            $token->saveOrFail();

            $this->trace->info(TraceCode::GLOBAL_LOCAL_TOKEN_CREATION_SUCCESS, [
                'tokenId'       => $existingToken->getId(),
                'isGlobal'      => $existingToken->isGlobal(),
                'newTokenId'    => $token->getId(),
            ]);

            return $token;
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException($ex, Trace::ERROR, TraceCode::GLOBAL_LOCAL_TOKEN_CREATION_ERROR, [
                'tokenId'   => $existingToken->getId(),
                'isGlobal'  => $existingToken->isGlobal(),
            ]);

            throw $ex;
        }
    }

    /**
     * Check and return if a similar token already exists on the
     * provided merchant and customer
     *
     * @param $token    - Existing global/local token whose similar token we need to check
     * @param $customer - Customer whose tokens need to be checked
     * @param $merchant - Merchant along with above customer whose tokens need to be checked
     *
     * @return Entity|null
     */
    public function checkIfSimilarTokenCardAlreadyExistsOnCustomerAndMerchant($token, $customer, $merchant): ?Entity
    {
        $startTime = millitime();

        $existingTokens = $this->repo->token->getByMethodAndCustomerIdAndMerchantId(
            Method::CARD,
            $customer->getId(),
            $merchant->getId()
        );

        $existingToken = $this->validateExistingTokenCard($existingTokens, $token);

        $this->trace->info(TraceCode::TOKEN_DEDUPE_CHECK_RESPONSE_TIME, [
            'timeTaken'         => millitime() - $startTime,
            'isGlobalCustomer'  => $customer->isGlobal(),
            'tokenPresent'      => isset($existingToken),
        ]);

        return $existingToken;
    }

    /**
     * This method takes in the current token collection, removes the
     * card tokens which are network tokenised but status is not active
     *
     * @param Base\PublicCollection|array $tokens
     *
     * @return Base\PublicCollection|array
     */
    public function removeNonActiveTokenisedCardTokens($tokens)
    {
        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {
            return $tokens->filter(static function (Entity $token) {
                // If token has network tokenised card, and status is not active, remove it
                if (($token->hasCard()) &&
                    (!$token->card->isRzpTokenisedCard()) &&
                    ($token->getStatus() !== Entity::ACTIVE)) {
                    return false;
                }

                return true;
            })->values();
        }

        // Added this log for checking if this edge case occurs
        // If this gets logged, will have to handle that case as well
        // Else will remove it after 1 week
        $this->trace->warning(TraceCode::TOKENS_NOT_A_PUBLIC_COLLECTION, [
            'tokens_data_type' => gettype($tokens),
        ]);

        return $tokens;
    }

    /**
     * This method takes in the current token collection,
     * removes non-card tokens
     *
     * @param Base\PublicCollection|array $tokens
     *
     * @return Base\PublicCollection|array
     */
    public function removeNonCardTokens(Base\PublicCollection|array $tokens): Base\PublicCollection|array
    {
        return $tokens->filter(static function (Entity $token) {
            if ($token->isCard()) {
                return true;
            }

            return false;
        })->values();
    }

    /**
     * Removes all the card tokens which are not compliant with the
     * Reserve Bank of India's (RBI) tokenisation guidelines.
     *
     * @param Base\PublicCollection|array $tokens
     *
     * @return Base\PublicCollection|array
     */
    public function removeNonCompliantCardTokens($tokens)
    {
        if (Environment::isEnvironmentQA($this->app['env']) ||
            Environment::isLowerEnvironment($this->app['env'])
        )
        {
            return $tokens;
        }

        if (!Base\PublicCollection::isPublicCollection($tokens)) {
            $this->trace->warning(TraceCode::TOKENS_NOT_A_PUBLIC_COLLECTION, [
                'tokens_data_type' => gettype($tokens),
            ]);

            $tokenItems = &$tokens['items'];

            $tokenItems = array_filter($tokenItems, static function ($token) {
                if (
                    Arr::has($token, [Entity::CARD, Entity::COMPLIANT_WITH_TOKENISATION_GUIDELINES]) &&
                    $token[Entity::COMPLIANT_WITH_TOKENISATION_GUIDELINES] === false
                ) {
                    return false;
                }

                return true;
            });

            return $tokens;
        }

        $tokensWithoutNonCompliantCards = $tokens->filter(static function (Entity $token) {
            // removed all non-tokenised saved cards
            if ($token->hasCard() && ($token->card->isTokenisationCompliant($token->merchant) === false))
            {
                return false;
            }

            return true;
        })->values();

        $this->trace->info(TraceCode::REMOVE_NON_COMPLIANT_TOKENS, [
            'totalNoOfTokens' => count($tokens),
            'noOfCompliantTokens' => count($tokensWithoutNonCompliantCards),
            'noOfNonCompliantTokens'  => count($tokens) - count($tokensWithoutNonCompliantCards),
        ]);

        return $tokensWithoutNonCompliantCards;
    }

    public function updateTokenStatus($tokenId , $status ,$error_code = null, $description = null)
    {
        $updateData[Token\Entity::STATUS] = $status;

        $updateData[Token\Entity::INTERNAL_ERROR_CODE] =$error_code;

        $updateData[Token\Entity::ERROR_DESCRIPTION] = $description;

        $this->trace->info(
            TraceCode::CUSTOMER_TOKEN_ACTION_ASYNC,
            [
                'payload'   => $updateData
            ]);

        $rowsAffected = (new Token\Repository)->updateById($tokenId, $updateData);
    }

    /**
     * @param Base\PublicCollection $tokens
     * @return Base\PublicCollection
     */
    public function filterTokensForCheckout(Base\PublicCollection $tokens): Base\PublicCollection
    {
        // TODO: Needs to be fixed later when we allow first recurring on old recurring nb token.
        // Currently, we do not expose any recurring NB tokens to the customer.
        // We do not handle the flow where a customer can use an existing token
        // to subscribe to another product.
        //
        $tokens = $this->removeEmandateRecurringTokens($tokens);

        $tokens = $this->removeDisabledNetworkTokens($tokens, $this->merchant->methods->getCardNetworks());

        $tokens = $this->removeDuplicateCardRecurringTokensIfAny($tokens, $this->merchant);

        $tokens = $this->removeNonCompliantCardTokens($tokens);

        $tokens = $this->removeNonActiveTokenisedCardTokens($tokens);

        return $this->addConsentFieldInTokens($tokens);
    }
}
