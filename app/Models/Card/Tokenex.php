<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Tokenex extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->tokenex = $this->app['card.tokenex'];
    }

    public function getCardNumber($vaultToken)
    {
        try
        {
            $cardNumber = $this->tokenex->detokenize($vaultToken);

            assertTrue(empty($cardNumber) === false);

            $cardNumber = strval($cardNumber);

            return $cardNumber;
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

            throw $e;
        }
    }

    public function getVaultToken($cardNumber)
    {
        try
        {
            $token = $this->tokenex->tokenize($cardNumber);

            return $token;
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::TOKENEX_REQUEST,
                [
                    'message' => 'Failed to tokenize data'
                ]
            );

            throw $e;
        }
    }
}
