<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class CardVault extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->cardVault = $this->app['card.cardVault'];
    }

    public function getCardNumber($vaultToken)
    {
        try
        {
            $cardNumber = $this->cardVault->detokenize($vaultToken);

            assertTrue(empty($cardNumber) === false);

            $cardNumber = strval($cardNumber);

            return $cardNumber;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'vault_token'   => $vaultToken,
                    'message'       => 'Failed to detokenize data'
                ]
            );

            throw $e;
        }
    }

    public function getVaultToken($cardNumber)
    {
        try
        {
            $token = $this->cardVault->tokenize($cardNumber);

            return $token;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to tokenize data'
                ]
            );

            throw $e;
        }
    }

    public function getTokenexToken($token)
    {
        try
        {
            $token = $this->cardVault->getTokenexToken($token);

            return $token;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to get tokenex token'
                ]
            );

            throw $e;
        }
    }

    public function getVaultTokenFromTokenexToken($tokenexToken)
    {
        try
        {
            $token = $this->cardVault->getVaultTokenFromTokenexToken($tokenexToken);

            return $token;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to fetch vault tokens - getVaultTokenFromTokenexToken'
                ]
            );

            throw $e;
        }
    }
}
