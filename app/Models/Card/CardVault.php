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
        $vaultEx = null;

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

            $vaultEx = $e;
        }

        try
        {
          return $this->cardVault->decrypt($vaultToken);
        }
        catch(\Throwable $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'vault_token'   => $vaultToken,
                    'message'       => 'Failed to decrypt data'
                ]
            );

            throw $vaultEx;
        }
    }

    public function getVaultToken($input)
    {
        try
        {
            $cardNumber = preg_replace('/[^0-9]/', '', $input['card']);

            $input['card'] = $cardNumber;

            $token = $this->cardVault->tokenize($input);

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

    public function getVaultTokenOrEncryptionToken($input)
    {
        try
        {
            return $this->getVaultToken($input);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to tokenize data'
                ]
            );

        }

        return $this->cardVault->encrypt($input);
    }



    public function getVaultTokenFromTempToken($tempVaultToken)
    {
        try
        {
            return $this->cardVault->getVaultTokenFromTempToken($tempVaultToken);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to get hashicorp vault token data'
                ]
            );

            throw $e;
        }
    }

    public function deleteToken($tempVaultToken)
    {
        try
        {
            return $this->cardVault->deleteToken($tempVaultToken);
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_REQUEST,
                [
                    'message' => 'Failed to delete the token'
                ]
            );
        }
    }

    public function getTokenAndFingerprint($input)
    {
        try
        {
            $cardNumber = preg_replace('/[^0-9]/', '', $input['card']);

            $input['card'] = $cardNumber;

            return $this->cardVault->getTokenAndFingerprint($input);
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

    public function createTokenizedCard($card, $merchant, $providerInfo)
    {
        $input['card'] = $card;
        $input['provider'] = $providerInfo;
        $input['merchant'] = [
            'id' => $merchant->getId()
        ];

        $input['features'] = $merchant->getEnabledFeatures();

        return $this->app['card.cardVault']->createTokenizedCard($input);
    }

    public function fetchCryptogram($cardVaultToken, $merchant)
    {
        $input['token'] = $cardVaultToken;

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    protected function setMerchantDetails($input, $merchant)
    {
        // todo: send required merchant attributes after api contract finalization
        $input['merchant'] = [
            'id' => $merchant->getId()
        ];

        return $input;
    }
}
