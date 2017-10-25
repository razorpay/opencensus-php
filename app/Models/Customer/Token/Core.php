<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
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

                $this->create($customer, $input, $card);
            });
    }

    /**
     * @param  Customer\Entity $customer
     * @param  array           $input
     * @param Card\Entity|null $card
     *
     * @return Entity Below function is used to create token in payment flow where we
     *
     * Below function is used to create token in payment flow where we
     * already have a card_id
     */
    public function create($customer, $input, Card\Entity $card = null)
    {
        $token = new Token\Entity;

        if ((isset($input[Token\Entity::CARD_ID]) === true) or
            ($card !== null))
        {
            if ($card === null)
            {
                $card = $this->repo->card->findOrFailPublic($input[Token\Entity::CARD_ID]);
            }

            $token->card()->associate($card);

            $token->setExpiredAt($card->getExpiryTimestamp());
        }

        if (isset($input[Token\Entity::TERMINAL_ID]))
        {
            $terminal = $this->repo->terminal->findOrFail($input[Token\Entity::TERMINAL_ID]);

            //
            // This if block gets run only in case of wallet currently.
            //
            $token->terminal()->associate($terminal);

            unset($input[Token\Entity::TERMINAL_ID]);
        }

        $token->customer()->associate($customer);

        $token->merchant()->associate($customer->merchant);

        $token->build($input);

        $existingToken = $this->validateExistingToken($token);

        //
        // For cards, we check if there's already an existing
        // token with the same customer, and simply return that
        // instead of creating a new token altogether.
        // However, for netbanking, we don't do this check,
        // because netbanking tokens are newly created for each
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
        $token = $this->repo->token->getByTokenIdAndCustomer($id, $customer);

        if ($token === null)
        {
            $token = $this->repo->token->findByPublicIdAndMerchant($id, $customer->merchant);

            assertTrue($token->getCustomerId() === $customer->getId());
        }

        return $token;
    }

    /**
     * This method gives us all of the customer's saved tokens
     *
     * @param $customer
     * @return mixed
     */
    public function fetchTokensByCustomer($customer)
    {
        $tokens = $this->repo->token->getByCustomer($customer);

        return $tokens;
    }

    /**
     * This method takes in the current tokens collection, removes the netbanking
     * recurring tokens and returns the remaining tokens as an array
     *
     * @param $tokens
     * @return mixed
     */
    public function removeNetbankingRecurringTokens($tokens)
    {
        //
        // We are creating an array of all the items that do not pass the truth test
        // that the token is recurring and netbanking - as we do not want to show
        // recurring netbanking tokens to the merchant via preferences
        //

        if (Base\PublicCollection::isPublicCollection($tokens) === true)
        {
            $tokens = $tokens->reject(
                function($token)
                {
                    if (($token->getMethod() === 'netbanking') and
                        ($token->isRecurring() === true))
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
                            $netbankingRecurring = (($item['method'] === 'netbanking') and
                                                    ($item['recurring']));

                            return ($netbankingRecurring === false);
                        });
        }

        return $tokens;
    }

    public function updateTokenFromNetbankingGatewayData(Entity $token, array $gatewayData)
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

    protected function validateExistingToken($token)
    {
        $existingTokens = $this->repo->token->getByMethodAndCustomerId(
                                $token->getMethod(), $token->customer);

        $func = 'validateExistingToken' . $token->getMethod();

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

    protected function validateExistingTokenNetbanking($existingTokens, $newToken)
    {
        return null;
    }

    protected function validateExistingTokenWallet($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if (($token->getWallet()  === $newToken->getWallet()) and
                ($token->terminal() === $newToken->terminal()))
            {
                return $token;
            }
        }

        return null;
    }

    protected function getCardInputForDirectToken(array $input)
    {
        $cardInput = array_pull($input, Entity::CARD);

        $cardInput[Card\Entity::VAULT] = Card\Vault::TOKENEX;

        // TODO: Fix cvv based on network

        return $cardInput;
    }
}
