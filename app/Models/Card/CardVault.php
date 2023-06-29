<?php

namespace RZP\Models\Card;

use App;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Constants\Mode;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Card\IIN;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Customer\Token\Entity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Card\Entity as CardEntity;
use RZP\Models\Customer\Token\Core as TokenCore;

class CardVault extends Base\Core
{
    const NAME                             = 'name';
    const IIN                              = 'iin';
    const EXPIRY_MONTH                     = 'expiry_month';
    const EXPIRY_YEAR                      = 'expiry_year';
    const NUMBER                           = 'number';
    const TOKEN                            = 'token';
    const TEMP_VAULT_TOKEN_PREFIX          = 'pay_';
    const TEMP_VAULT_KMS_TOKEN_PREFIX      = 'pay2_';

    const TOKEN_NOT_FOUND = 'TOKEN_NOT_FOUND';

    const NON_RETRYABLE_CARD_META_DATA_FETCH_ERRORS = [
        self::TOKEN_NOT_FOUND,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->cardVault = $this->app['card.cardVault'];
    }

    public function getCardNumber($vaultToken,array $input = [],$gateway=null,$recurringPanStore = false)
    {
        $vaultEx = null;
        $buNamespace =null;
        if($recurringPanStore === true)
        {
            $buNamespace = 'payments_token_pan';
        }
        else
        {
            $buNamespace = $this->getBuNamespaceIfApplicable($input,false,$gateway);
        }
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

    public function getVaultToken($input,$cardArray=[], $recurringPanStore = false)
    {
        try
        {
            $cardNumber = preg_replace('/[^0-9]/', '', $input['card']);

            $input['card'] = $cardNumber;

            $buNamespace = null;

            if($recurringPanStore === true)
            {
                $buNamespace = 'payments_token_pan';
            }
            else
            {
                $buNamespace = $this->getBuNamespaceIfApplicable($cardArray);
            }

            $token = $this->cardVault->tokenize($input, $buNamespace);

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

    public function saveCardMetaData($card, $input, $isRzpX = false)
    {
        try
        {
           if ((str_contains($card->getVaultToken(), self::TEMP_VAULT_TOKEN_PREFIX) === false) and
               (str_contains($card->getVaultToken(), self::TEMP_VAULT_KMS_TOKEN_PREFIX) === false))
           {
               return [];
           }

            $expiryYear  = "";
            $expiryMonth = "";

            if ((isset($input[self::EXPIRY_YEAR])) and
                (strlen($input[self::EXPIRY_YEAR]) === 2))
            {
                $expiryYear = '20' . $input[self::EXPIRY_YEAR];
            }
            else
            {
                $expiryYear = $input[self::EXPIRY_YEAR];
            }

            if (isset($input[self::EXPIRY_MONTH]))
            {
                $expiryMonth = ltrim($input[self::EXPIRY_MONTH], '0');
            }

            // tokenised payment will not have the vault token with pay_ hence we are reading 6 digit iin

            $payload = [
                self::NAME         => $input[self::NAME] ?? "",
                self::EXPIRY_MONTH => $expiryMonth,
                self::EXPIRY_YEAR  => $expiryYear,
                self::IIN          => substr($input[self::NUMBER], 0, 6),
                self::TOKEN        => $card->getVaultToken(),
            ];

            $this->cardVault->saveCardMetaData($payload);

            // returning payload so that we don't have to call first time and we will have these details in memory
            return $payload;
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::VAULT_CARD_METADATA_SAVE_FAILED,
                [
                    'message' => 'Failed to save card meta data'
                ]
            );

            if ( $isRzpX === true)
            {
                $this->trace->count(Metric::VAULT_CARD_METADATA_SAVE_FAILED,
                                    [
                                        'message' => 'Failed to save card meta data'
                                    ]);
            }

        }

        return [];
    }

    public function getCardMetaData($card, $routeName=null)
    {
        try
        {
            if ((str_contains($card->getVaultToken(), self::TEMP_VAULT_TOKEN_PREFIX) === false) and
                (str_contains($card->getVaultToken(), self::TEMP_VAULT_KMS_TOKEN_PREFIX) === false))
            {
                return [];
            }

            $createdtAt = array_key_exists(CardEntity::CREATED_AT, $card->getAttributes()) ?
                $card[CardEntity::CREATED_AT] : Carbon::now(Timezone::IST)->getTimestamp();

            $diff = Carbon::now(Timezone::IST)->getTimestamp() - $createdtAt;

            // checking if we are hitting card meta data fetch api after 3 days (3*24*60*60 seconds)
            if ($diff > 259200)
            {
                $this->trace->info(TraceCode::CARD_METADATA_FETCH_AFTER_3_DAYS, [
                    'card_id'               => $card->getId(),
                    'created_at'            => $createdtAt,
                    'difference'            => $diff,
                    'route'                 => $routeName
                ]);

                $this->trace->count(Metric::CARD_METADATA_FETCH_AFTER_3_DAYS, ["route" => $routeName]);

                return [];
            }
            else
            {
                $this->trace->count(Metric::CARD_METADATA_FETCH_BEFORE_OR_ON_3RD_DAY, ["route" => $routeName]);
            }

            $this->trace->count(Metric::CARD_METADATA_FETCH ,["route" => $routeName]);

            $input = [
                self::TOKEN => $card->getVaultToken()
            ];

            return $this->cardVault->getCardMetaData($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(
                TraceCode::VAULT_CARD_METADATA_FETCH_FAILED,
                [
                    'message' => 'Failed to fetch card meta data'
                ]
            );

            $response = [];

            if (method_exists($e, 'getData') === true)
            {
                $response = $e->getData();
            }

            if ((isset($response['error']) === true) and
                (in_array($response['error'], self::NON_RETRYABLE_CARD_META_DATA_FETCH_ERRORS, true) === true))
            {
                return null;
            }
        }

        return [];
    }

    public function getBuNamespaceIfApplicable($input, $isRzpX = false ,$gateway=null)
    {
        $buNamespace =null;

        $merchantCountry = $this->merchant != null ? $this->merchant->getCountry() : 'IN';

        try
        {
            if($isRzpX === false)
            {
                if (empty($input['trivia']) === false)
                {
                    if (empty($input['network']) === true || $input['network'] !== NetworkName::DICL)
                    {
                        $buNamespace = 'payments_token_pan';
                    }
                }
                else if (empty($input['international']) === false)
                {
                    $buNamespace = 'payments_international';
                }
                else if (empty($input['network']) === false and $input['network'] === 'Bajaj Finserv')
                {
                    $buNamespace = 'payments_bajajfinserv';
                }
                else if (isset($gateway) === true and $gateway === 'paysecure')
                {
                    $buNamespace = 'payments_paysecure';

                }
                else if (empty(BuNamespace::BU_NAMESPACE[$merchantCountry]) === false)
                {
                    return BuNamespace::BU_NAMESPACE[$merchantCountry];
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
                TraceCode::CARD_ENTITY_DETAILS_BEFORE_VAULT_REQUEST,
                [
                    'card_id'       => $input['id'] ?? "" ,
                    'trivia'        => $input['trivia'] ?? "",
                    'international' => $input['international'] ?? "",
                    'network'       => $input['network']??"",
                    'vault_token'   => $input['vault_token']?? "",
                    "bu_namespace"  => $buNamespace
                ]
            );

            if (empty($input['iin']) === false)
            {
                $iin = $this->repo->card->retrieveIinDetails($input['iin']);

                if (empty($iin) == false)
                {
                    $this->trace->info(
                        TraceCode::INPUT_DETAILS_BEFORE_VAULT_REQUEST,
                        [
                            'card_id'       => $input['id'] ?? "" ,
                            'iinInfo'       => [
                                'issuer'        => $iin->getIssuer(),
                                'network'       => $iin->getNetwork(),
                                'category'      => $iin->getCategory(),
                                'type'          => $iin->getType(),
                                'country'       => $iin->getCountry(),
                                'international' => IIN\IIN::isInternational($iin->getCountry(), $merchantCountry),
                            ],
                            'trivia'        => $input['trivia'] ?? "",
                            'international' => $input['international'] ?? "",
                            'network'       => $input['network'] ?? "",
                            'vault_token'   => $input['vault_token'] ?? "",
                            "bu_namespace"  => $buNamespace
                        ]
                    );
                }
            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_VAULT_BU_NAMESPACE_EXCEPTION,
                [
                    'message' => $e,
                    'bu_namespace' => $buNamespace
                ]
            );
        }

        return $buNamespace;
    }


    public function createTokenizedCard($tokenInput, $merchant, $iinInfo)
    {
        $input['card']     = $tokenInput['card'];
        $input['iin']      = $iinInfo;

        $input['via_push_provisioning'] = $tokenInput['via_push_provisioning'] ?? null;

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

    public function shouldPanSourceChangeForMigration($cardInput, $merchant, $card)
    {
        if($card->isRupay() === false)
        {
            return false;
        }
            $variant = $this->app->razorx->getTreatment($merchant->getId(), RazorxTreatment::PANSOURCE_CHANGE_MIGRATION_RUPAY, $this->mode);

            $this->trace->info(TraceCode::PANSOURCE_CHANGE_MIGRATION_RAZORX_VARIANT, [
                'authentication_reference_number'     => $cardInput['authentication_reference_number'],
                'razorx_variant' => $variant,
                'mode' => $this->mode,
                'merchant_id' => $merchant->getId(),
            ]);

        return (strtolower($variant) === 'on');
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

        if ((empty($cardInput['authentication_reference_number']) === false)
            && ($this->shouldPanSourceChangeForMigration($cardInput, $merchant, $card)==true))
        {
            $input['authentication_data'] = [
                'authentication_reference_number' => $cardInput['authentication_reference_number'],
            ];
        }

        if ((empty($cardInput['mandate_id'] === false)) &&
            (empty($cardInput['end_date']) === false) &&
            (empty($cardInput['rupay_recurring']) === false) &&
            (empty($cardInput['authentication_reference_number']) === false))
        {
            $input['card_mandate'] = [
                'mandate_id' => $cardInput['mandate_id'],
                'end_date'   => $cardInput['end_date'],
                'recurring'  => $cardInput['rupay_recurring']
            ];

            $input['authentication_data'] = [
                'authentication_reference_number' => $cardInput['authentication_reference_number'],
            ];
        }

        $input['iin'] = $iinInfo;

        if (empty($cardInput['merchant_token']) === false)
        {
            $input['merchant_token'] = $cardInput['merchant_token'];
        }

        if ($cardInput['via_push_provisioning'] === true){
            $input['via_push_provisioning'] = true;
        }

        if (empty($cardInput['email']) === false)
        {
            $input['email'] = $cardInput['email'];
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

    public function fetchCryptogramFromVaultToken($vaultToken, $merchant, $internalServiceRequest = false, $token_type = 'null')
    {
        $input = [
            'token'                    => $vaultToken,
            'internal_service_request' => $internalServiceRequest,
            'token_type'               => $token_type
        ];

        $input = $this->setMerchantDetails($input, $merchant);

        return $this->app['card.cardVault']->fetchCryptogram($input);
    }

    public function fetchCryptogramForPayment($cardVaultToken, $merchant, $token_type = 'null')
    {
        $response = $this->fetchCryptogramFromVaultToken($cardVaultToken, $merchant, true, $token_type);

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
            'category' => $merchant->getCategory(),
            'features' => $merchant->getEnabledFeatures()
        ];

        return $input;
    }

    //This function is for fetching par value for each card entity using provider reference id from vault/network
    public function getParValueForCard($card)
    {
        $input = [
            "card" => [
                "id"       => $card->getProviderReferenceId(),
            ],
            "token"    => $card->getCardVaultToken(),
            "provider" => $card->getNetwork()
        ];

        $response = $this->app['card.cardVault']->fetchFingerprint($input);

        $cardFingerprint = null ;

        try
        {
            if(isset($response['service_provider_tokens'][0]['provider_data']))
            {
                $cardFingerprint = $response["service_provider_tokens"][0]["provider_data"]["payment_account_reference"]
                    ?? $response["service_provider_tokens"][0]["provider_data"]["network_reference_id"];

            }
        }
        catch (\Exception $e)
        {
            $this->trace->error(
                TraceCode::CARD_FETCH_FINGERPRINT_EXCEPTION,
                [
                    'message' => $e
                ]
            );
        }

        return $cardFingerprint;
    }

    public function updateToken($vaultToken, $updateData)
    {
        $input = $updateData;

        $input['token'] = $vaultToken;

        return $this->app['card.cardVault']->updateToken($input);
    }
}
