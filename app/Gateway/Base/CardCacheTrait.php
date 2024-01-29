<?php

namespace RZP\Gateway\Base;

use RZP\Models\CapitalVirtualCards\Core;
use RZP\Models\Card;
use RZP\Trace\TraceCode;
use RZP\Models\Payment;

/*                                                            *\
|-------------------------------------------------------------|
| This trait adds caching functionality to a card gateway.    |
| The card gateway needs to instantiate its secureCacheDriver |
| property for this trait to be usable.                       |
|-------------------------------------------------------------|
\*                                                            */

trait CardCacheTrait
{
    /**
     * Stores card details in the cache.
     * @param array $input
     * @param bool $storeCvv
     * @throws \Exception
     */
    protected function persistCardDetailsTemporarily(array $input, $storeCvv = true)
    {
        $cvv = $input['card']['cvv'];

        $vaultToken = null;

        if (empty($input['card']['vault_token']) === false)
        {
            $vaultToken = $input['card']['vault_token'];
        }
        else
        {
            $tempInput['card'] = $input['card']['number'];
            $cardArray = $input['card'];
            $vaultToken = (new Card\CardVault)->getVaultTokenOrEncryptionToken($tempInput , $cardArray);
        }

        $key = $this->getCacheKey($input);

        $data = [
            'vault_token' => $vaultToken
        ];

        if ($storeCvv === true)
        {
            $data['cvv'] = $this->app['encrypter']->encrypt($cvv);

            if (empty($input['card'][Card\Entity::CRYPTOGRAM_VALUE]) === false)
            {
                $data[Card\Entity::CRYPTOGRAM_VALUE] = $this->app['encrypter']->encrypt($input['card'][Card\Entity::CRYPTOGRAM_VALUE]);
                $data[Card\Entity::TOKENISED]        = $input['card'][Card\Entity::TOKENISED];
                $data[CARD\Entity::TOKEN_PROVIDER]   = $input['card'][CARD\Entity::TOKEN_PROVIDER];
            }
        }

        $cacheTtl = $this->getCardCacheTtl($input);

        // If this is set to 0, set the cache forever
        if ($cacheTtl === 0)
        {
            $this->app['cache']->store($this->secureCacheDriver)->forever($key, $data);
        }
        else
        {
            // Multiplying by 60 since cache put() expect ttl in seconds
            $this->app['cache']->store($this->secureCacheDriver)->put($key, $data, $cacheTtl * 60);
        }

        // Storing alt id data in cache. 2 is value for alt id
        if ($input['card']['trivia'] === '2'  && isset($input['alt_id_data']))
        {
            $keyAltId = $this->getAltIdCacheKey($input);

            $this->app['cache']->store($this->secureCacheDriver)->put($keyAltId, $input['alt_id_data'], $cacheTtl * 60);
        }
    }

    /**
     * This method gets the cached card detail and sets it in the input.
     * @param array $input
     * @throws \Exception
     */
    protected function setCardNumberAndCvv(array & $input,$cardArray=[], $action = null)
    {
        $data = $this->getCardDetailsFromCache($input);

        $vaultToken = null;

        if (empty($input['card'][Card\Entity::VAULT_TOKEN]) === true)
        {
            $this->trace->warning(
                TraceCode::CARD_VAULT_TOKEN_MISSING,
                [
                   'message' => 'vault_token not present in card.vault_token'
                ]);

            $vaultToken = $data['vault_token'];
        }
        else
        {
            $vaultToken = $input['card'][Card\Entity::VAULT_TOKEN];
        }

        $gateway = $input['payment']['gateway'] ?? null;

        $input['card']['number'] = (new Card\CardVault)->getCardNumber($vaultToken, $cardArray, $gateway);

        if (isset($data['cvv']) === true)
        {
            $input['card']['cvv'] = $this->app['encrypter']->decrypt($data['cvv']);
        }

        if (empty($data[Card\Entity::CRYPTOGRAM_VALUE]) === false)
        {
            $input['card'][Card\Entity::CRYPTOGRAM_VALUE] = $this->app['encrypter']->decrypt($data[Card\Entity::CRYPTOGRAM_VALUE]);
            $input['card'][Card\Entity::TOKENISED]        = $data[Card\Entity::TOKENISED];
            $input['card'][Card\Entity::TOKEN_PROVIDER]   = $data[Card\Entity::TOKEN_PROVIDER];
        }

        if (($input['card'][Card\Entity::TRIVIA] === '2') && Card\Entity::isExternalAltIdPayment($input['card']) === false ){

            if ($gateway === Payment\Gateway::HDFC && strtolower($input['card']['network']) === "rupay")
            {
                return;
            }

            $altIdData = $this->getAltIdDetailsFromCache($input);

            if (empty($altIdData['alt_id_data']) === true && $action === Action::CAPTURE)
            {
                //for capture if we do not get data in cache we need to call detokenize api
                $altIdData['alt_id_data']['alt_id']['value'] = (new Card\CardVault)->detokenizeAltIdData($input['card'][Card\Entity::VAULT_TOKEN]);
                $altIdData['alt_id_data']['alt_id'][Card\Entity::EXPIRY_MONTH] = $input['card'][Card\Entity::TOKEN_EXPIRY_MONTH];
                $altIdData['alt_id_data']['alt_id'][Card\Entity::EXPIRY_YEAR] = $input['card'][Card\Entity::TOKEN_EXPIRY_YEAR];
            }

            if (empty($altIdData['alt_id']) === false)
            {
                $input['card'][Card\Entity::CRYPTOGRAM_VALUE] = $altIdData['alt_id'][Card\Entity::CRYPTOGRAM_VALUE];
                $input['card'][Card\Entity::NUMBER] = $altIdData['alt_id']['value'];
                $input['card'][Card\Entity::EXPIRY_MONTH] = $altIdData['alt_id'][Card\Entity::EXPIRY_MONTH];
                $input['card'][Card\Entity::EXPIRY_YEAR] = $altIdData['alt_id'][Card\Entity::EXPIRY_YEAR];
                $input['card'][Card\Entity::VAULT_TOKEN] = $altIdData[Card\Entity::TOKEN];
                $input['card']['card_vault_token'] = $altIdData['alt_id']['card_vault_token'];
                $input['card'][Card\Entity::TOKEN_REFERENCE_NUMBER] = $altIdData['alt_id'][Card\Entity::TOKEN_REFERENCE_NUMBER];
                $input['card'][Card\Entity::TOKEN_REFERENCE_ID] = $altIdData['alt_id'][Card\Entity::TOKEN_REFERENCE_ID];
            }
            else
            {
                $this->trace->warning(
                    TraceCode::VAULT_ALT_ID_REQUEST_FETCH_ERROR,
                    [
                        'message' => 'alt_id not present in cache'
                    ]);
            }
        }
    }

    /**
     * Gets the card details stored in the cache.
     * @param $input
     * @return array
     */
    protected function getCardDetailsFromCache($input)
    {
        $key = $this->getCacheKey($input);

        return $this->app['cache']->store($this->secureCacheDriver)->get($key) ?: [];
    }

    protected function getAltIdDetailsFromCache($input)
    {
        $key = $this->getAltIdCacheKey($input);

        return $this->app['cache']->store($this->secureCacheDriver)->get($key) ?: [];
    }

    /**
     * @param $paymentId
     * @return string
     */
    protected function getCacheKey($input)
    {
        return sprintf(static::CACHE_KEY, $input['payment']['id']);
    }

    protected function getAltIdCacheKey($input)
    {
        return sprintf(static::CACHE_KEY, 'alt_id_'.$input['payment']['id']);
    }

    protected function getDriver()
    {
        return $this->app['config']->get('cache.secure_default');
    }

    // Fetches the cache ttl
    // Added this in a function because, some gateways' would have
    // multiple cache TTLs based on the payment network
    protected function getCardCacheTtl($input)
    {
        return static::CARD_CACHE_TTL;
    }
}
