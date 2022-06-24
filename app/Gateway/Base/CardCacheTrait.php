<?php

namespace RZP\Gateway\Base;

use RZP\Models\Card;
use RZP\Trace\TraceCode;

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
    }

    /**
     * This method gets the cached card detail and sets it in the input.
     * @param array $input
     * @throws \Exception
     */
    protected function setCardNumberAndCvv(array & $input,$cardArray=[])
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

    /**
     * @param $paymentId
     * @return string
     */
    protected function getCacheKey($input)
    {
        return sprintf(static::CACHE_KEY, $input['payment']['id']);
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
