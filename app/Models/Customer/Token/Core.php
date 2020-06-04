<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Merchant;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Exception;
use RZP\Trace\TraceCode;

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
     *
     * @return Entity
     */
    public function create($customer, $input, Card\Entity $card = null)
    {
        $traceInput = $input;
        unset($traceInput[Entity::AADHAAR_NUMBER]);

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
            $token->setExpiredAt($card->getExpiryTimestamp());

            $token->card()->associate($card);
        }

        if ($terminal !== null)
        {
            $token->terminal()->associate($terminal);
        }

        $token->customer()->associate($customer);

        $token->merchant()->associate($customer->merchant);

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
        else
        {
            $this->repo->saveOrFail($token);

            return $token;
        }
    }

    public function edit($token, $input)
    {
        $token->edit($input);

        $this->repo->saveOrFail($token);

        return $token;
    }

    /**
     * Get the token entity for local/global customer. $id can be token or
     * token id for now.
     * @param $id
     * @param $customer
     * @return Token\Entity
     */
    public function getByTokenIdAndCustomer($id, Customer\Entity $customer)
    {
        // TODO: remove this once merchants shifts to token_id
        $token = $this->repo->token->getByTokenAndCustomer($id, $customer);

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
     * @param string $id
     * @param string $customerId
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function getByTokenIdAndCustomerId(string $id, string $customerId)
    {
        $token = $this->repo->token->getByTokenAndCustomerId($id, $customerId);

        if ($token === null)
        {
            $token = $this->repo->token->getByTokenIdAndCustomerId($id, $customerId);
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

    public function updateTokenForUpi(Entity $token, array $gatewayData)
    {
        $token->setRecurringStatus($gatewayData['recurring_status']);

        if ($gatewayData['recurring_status'] === RecurringStatus::CONFIRMED)
        {
            $token->setRecurring(true);
        }

        $this->repo->saveOrFail($token);
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

    protected function validateExistingTokenCard($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if ($token->getCardId() === $newToken->getCardId())
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
}
