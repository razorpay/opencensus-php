<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\P2p\Base\Libraries\Card;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;

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

    public function createTokenizedCard($tokenInput, $merchant, $iinInfo)
    {
        $input['card']     = $tokenInput['card'];
        $input['iin']      = $iinInfo;

        if (empty($tokenInput['authentication']) === false)
        {
            $input['authentication'] = $tokenInput['authentication'];
        }

        $input = $this->setMerchantDetails($input, $merchant);

        $input['features'] = $merchant->getEnabledFeatures();

        return $this->app['card.cardVault']->createTokenizedCard($input);
    }

    public function migrateToTokenizedCard($card, $merchant, $iinInfo, $cardInput)
    {
        $input['card'] = [
            'vault_token'                     => $card->getVaultToken(),
            'expiry_month'                    => strval($card->getExpiryMonth()),
            'expiry_year'                     => strval($card->getExpiryYear()),
            'cvv'                             => strval($cardInput['cvv']),
        ];

        if (empty($cardInput['authentication_reference_number']) === false)
        {
            $input['authentication'] = [
                'authentication_reference_number' => $cardInput['authentication_reference_number'],
            ];
        }

        $input['iin'] = $iinInfo;

        if (empty($cardInput['merchant_token']) === false)
        {
            $input['merchant_token'] = $cardInput['merchant_token'];
        }

        $input = $this->setMerchantDetails($input, $merchant);

        $input['features'] = $merchant->getEnabledFeatures();

        return $this->app['card.cardVault']->migrateToTokenizedCard($input);
    }

    public function fetchCryptogram($serviceProviderTokenId, $merchant)
    {
        $input = [
            'is_service_provider_token' => true,
            'service_provider_token'    => $serviceProviderTokenId,
        ];

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    public function fetchCryptogramFromVaultToken($vaultToken, $merchant)
    {
        $input['token'] = $vaultToken;

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    public function fetchCryptogramForPayment($cardVaultToken, $merchant)
    {
        $response = $this->fetchCryptogramFromVaultToken($cardVaultToken, $merchant);

        return $response['service_provider_tokens'][0]['provider_data'];
    }

    public function fetchToken($cardVaultToken)
    {
        $input['token'] = $cardVaultToken;

        return $this->app['card.cardVault']->fetchToken($input);
    }

    public function deleteNetworkToken($cardVaultToken)
    {
        $input['token'] = $cardVaultToken;

        return $this->app['card.cardVault']->deleteNetworkToken($input);
    }

    protected function setMerchantDetails($input, $merchant)
    {
        // todo: send required merchant attributes after api contract finalization
        $input['merchant'] = [
            'id' => $merchant->getId()
        ];

        return $input;
    }

    public function updateToken($vaultToken, $updateData)
    {
        $input = $updateData;

        $input['token'] = $vaultToken;

        return $this->app['card.cardVault']->updateToken($input);
    }
}
