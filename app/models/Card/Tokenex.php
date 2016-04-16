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

            if (empty($cardNumber) === false)
            {
                $cardNumber = strval($cardNumber);
            }
        }
        catch (Exception $e)
        {
            $this->trace->info(
                TraceCode::TOKENEX_REQUEST,
                "failed to detokenize data");
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
            $this->trace->info(
                TraceCode::TOKENEX_REQUEST,
                "failed to tokenize data");
        }

        return $token;
    }
}