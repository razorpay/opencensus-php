<?php

namespace RZP\Models\Card;

use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Exception;

class Tokenex
{
    public static function getCardNumber($vaultToken)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $cardNumber = $app['card.tokenex']->detokenize($vaultToken);

            rzpAssert(empty($cardNumber) === false);

            $cardNumber = strval($cardNumber);

            return $cardNumber;
        }
        catch (\Exception $e)
        {
            $app['trace']->error(
                TraceCode::TOKENEX_REQUEST,
                [
                    'vault_token'   => $vaultToken,
                    'message'       => 'Failed to detokenize data'
                ]
            );

            throw $e;
        }
    }

    public static function getVaultToken($cardNumber)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $token = $app['card.tokenex']->tokenize($cardNumber);

            return $token;
        }
        catch (Exception $e)
        {
            $app['trace']->error(
                TraceCode::TOKENEX_REQUEST,
                [
                    'message'       => 'Failed to tokenize data'
                ]
            );

            throw $e;
        }
    }
}
