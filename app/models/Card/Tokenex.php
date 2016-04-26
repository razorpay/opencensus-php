<?php

namespace Models\Card;

use EE\Exception;
use Trace\Trace;
use Trace\TraceCode;

class Tokenex
{
    public static function getCardNumber($vaultToken)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $cardNumber = $app['card.tokenex']->detokenize($vaultToken);

            assert(empty($cardNumber) === false);

            $cardNumber = strval($cardNumber);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::TOKENEX_REQUEST,
                [
                    'vault_token'   => $vaultToken,
                    'message'       => 'Failed to detokenize data'
                ]
            );

            $this->trace->traceException($e);
        }

        return $cardNumber;
    }

    public static function getVaultToken($cardNumber)
    {
        $app = \App::getFacadeRoot();

        try
        {
            $token = $app['card.tokenex']->tokenize($cardNumber);
        }
        catch (Exception $e)
        {
            $this->trace->error(
                TraceCode::TOKENEX_REQUEST,
                [
                    'message'       => 'Failed to tokenize data'
                ]
            );

            $this->trace->traceException($e);
        }

        return $token;
    }
}