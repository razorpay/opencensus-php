<?php

namespace RZP\Models\Customer\Token;

use RZP\Models\Base;
use RZP\Models\Card;
use RZP\Models\Customer;
use RZP\Models\Terminal;
use RZP\Models\Customer\AppToken;
use RZP\Models\Customer\Token;
use RZP\Models\Merchant\Account;
use RZP\Error\ErrorCode;
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
        }

        if (isset($input[Token\Entity::TERMINAL_ID]))
        {
            $terminal = $this->repo->terminal->findOrFail($input[Token\Entity::TERMINAL_ID]);

            $token->terminal()->associate($terminal);

            unset($input[Token\Entity::TERMINAL_ID]);
        }

        $token->customer()->associate($customer);
        $token->merchant()->associate($customer->merchant);

        $token->build($input);
        $existingToken = $this->validateExistingToken($token);

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

    public function fetchTokensByCustomer($customer)
    {
        $tokens = $this->repo->token->getByCustomerId($customer->getId());

        return $tokens;
    }

    protected function validateExistingToken($token)
    {
        $existingTokens = $this->repo->token->getByMethodAndCustomerId(
                                $token->getMethod(), $token->customer);

        $func = 'validateExistingToken'.$token->getMethod();

        return $this->$func($existingTokens, $token);
    }

    protected function validateExistingTokenCard($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if ($token->card->getId() === $newToken->card->getId())
            {
                return $token;
            }
        }
    }

    protected function validateExistingTokenNetbanking($existingTokens, $newToken)
    {
        foreach ($existingTokens as $token)
        {
            if (($token->getBank()  === $newToken->getBank()) and
                ($token->getGatewayToken() === $newToken->getGatewayToken()))
            {
                return $token;
            }
        }
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
    }
}
