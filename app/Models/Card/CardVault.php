<?php

namespace RZP\Models\Card;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Customer\Token\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\P2p\Base\Libraries\Card;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Customer\Token\Core as TokenCore;

class CardVault extends Base\Core
{
    public function __construct()
    {
        parent::__construct();

        $this->cardVault = $this->app['card.cardVault'];
    }

    public function getCardNumber($vaultToken,array $input = [],$gateway=null)
    {
        $vaultEx = null;
        $buNamespace =null;
        $buNamespace = $this->getBuNamespaceIfApplicable($input,false,$gateway);
        try
        {
            $cardNumber = $this->cardVault->detokenize($vaultToken,$buNamespace);

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

    public function getVaultToken($input,$cardArray=[])
    {
        try
        {
            $cardNumber = preg_replace('/[^0-9]/', '', $input['card']);

            $input['card'] = $cardNumber;

            $buNamespace =null;
            $buNamespace = $this->getBuNamespaceIfApplicable($cardArray);
            $token = $this->cardVault->tokenize($input,$buNamespace);

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

    public function getVaultTokenOrEncryptionToken($input,$cardArray=[])
    {
        try
        {
            return $this->getVaultToken($input,$cardArray);
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



    public function getVaultTokenFromTempToken($tempVaultToken, $cardArray = [], $gateway = null)
    {
        try
        {
            $buNamespace = $this->getBuNamespaceIfApplicable($cardArray, false, $gateway);

            return $this->cardVault->getVaultTokenFromTempToken($tempVaultToken,$buNamespace);
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


    public function getBuNamespaceIfApplicable($input, $isRzpX = false ,$gateway=null)
    {
        $buNamespace =null;
        try
        {
            if($isRzpX === false)
            {
                if (empty($input['trivia']) === false) {
                    $buNamespace = 'payments_token_pan';
                }
                else if (empty($input['international']) === false) {
                    $buNamespace = 'payments_international';
                }
                else if (empty($input['network']) === false and $input['network'] === 'Bajaj Finserv'){
                    $buNamespace = 'payments_bajajfinserv';
                }
                else if (isset($gateway) === true and $gateway === 'paysecure'){
                    $buNamespace = 'payments_paysecure';
                }
            }
            else
            {
                if (empty($input[CardEntity::TRIVIA]) === false)
                {
                    $buNamespace = BuNamespace::RAZORPAYX_TOKEN_PAN;
                }
                else
                {
                    $buNamespace = BuNamespace::RAZORPAYX_NON_SAVED_CARDS;
                }
            }

            $this->trace->info(
                TraceCode::CARD_ENTIY_DETAILS_BEFORE_VAULT_REQUEST,
                [
                    'card_id'       => $input['id'] ?? "" ,
                    'trivia'        => $input['trivia'] ?? "",
                    'international' => $input['international'] ?? "",
                    'network'       => $input['network']??"",
                    'vault_token'   => $input['vault_token']?? "",
                    "bu_namespace"  => $buNamespace
                ]
            );
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_BU_NAMESPACE_EXCEPTION,
                [
                    'message' => $e
                ]
            );
        }

        return $buNamespace;
    }


    public function createTokenizedCard($tokenInput, $merchant, $iinInfo)
    {
        $input['card']     = $tokenInput['card'];
        $input['iin']      = $iinInfo;

        if (empty($tokenInput['authentication']) === false && $this->shouldPanSourceChange($tokenInput, $merchant)==true)
        {
            $this->trace->info(TraceCode::PANSOURCE_CHANGE_RAZORX_VARIANT, [
                'Activated'     => true,
            ]);
            $input['authentication_data'] = $tokenInput['authentication'];
        }

        $input = $this->setMerchantDetails($input, $merchant);

        if (empty($cardInput['customer_id']) === false) {
            $input['customer_id'] = $tokenInput['customer_id'];
        }

        $input['features'] = $merchant->getEnabledFeatures();

        return $this->app['card.cardVault']->createTokenizedCard($input);
    }

    public function shouldPanSourceChange($input, $merchant)
    {
        if((new TokenCore)->isNetworkRuPay($input[Entity::CARD]))
        {
            $variant = $this->app->razorx->getTreatment($merchant->getId(), RazorxTreatment::PANSOURCE_CHANGE_RUPAY, $this->mode);

            $this->trace->info(TraceCode::PANSOURCE_CHANGE_RAZORX_VARIANT, [
                'authentication_data'     => $input['authentication'],
                'razorx_variant' => $variant,
                'mode' => $this->mode,
                'merchant_id' => $merchant->getId(),
            ]);

            if (strtolower($variant) === 'on')
            {
                return true;
            }

            return false;
        }
    }

    public function migrateToTokenizedCard($card, $merchant, $iinInfo, $cardInput)
    {
        $input['card'] = [
            'vault_token'                     => $card->getVaultToken(),
            'expiry_month'                    => strval($card->getExpiryMonth()),
            'expiry_year'                     => strval($card->getExpiryYear()),
            'cvv'                             => strval($cardInput['cvv']),
        ];

        $input['async'] = isset($cardInput['async']) ? $cardInput['async'] : null;

        if ((empty($cardInput['authentication_reference_number']) === false) && (empty($input['async']) == true)
            && ($this->shouldPanSourceChange($input, $merchant)==true))
        {
            $input['authentication_data'] = [
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

        if (empty($cardInput['customer_id']) === false)
        {
            $input['customer_id'] = $cardInput['customer_id'];
        }

        return $this->app['card.cardVault']->migrateToTokenizedCard($input);
    }

    public function fetchCryptogram($serviceProviderTokenId, $merchant, $internalServiceRequest = false)
    {
        $input = [
            'is_service_provider_token' => true,
            'service_provider_token'    => $serviceProviderTokenId,
            'internal_service_request'  => $internalServiceRequest,
        ];

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    public function fetchCryptogramFromVaultToken($vaultToken, $merchant, $internalServiceRequest = false)
    {
        $input = [
            'token'                    => $vaultToken,
            'internal_service_request' => $internalServiceRequest,
        ];

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    public function fetchCryptogramForPayment($cardVaultToken, $merchant)
    {
        $response = $this->fetchCryptogramFromVaultToken($cardVaultToken, $merchant, true);

        return $response['service_provider_tokens'][0]['provider_data'];
    }

    public function fetchParValueFromVault($input){
        return $this->app['card.cardVault']->fetchParValue($input);
    }

    public function fetchToken($cardVaultToken, $internalServiceRequest)
    {
        $input = [
            'token'                    => $cardVaultToken,
            'internal_service_request' => $internalServiceRequest,
        ];

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
            'id' => $merchant->getId(),
            'category' => $merchant->getCategory()
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
