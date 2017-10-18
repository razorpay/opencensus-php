<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Exception;

class Core extends Base\Core
{
    public function create($customer, $input)
    {
        $token = new Token\Entity;

        if (isset($input[Token\Entity::CARD_ID]))
        {
            $card = $this->repo->card->findOrFailPublic($input[Token\Entity::CARD_ID]);

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
}
