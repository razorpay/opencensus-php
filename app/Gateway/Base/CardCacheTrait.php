<?php

namespace RZP\Gateway\Base;

use RZP\Models\Card;

trait CardCacheTrait
{
    protected function persistCardDetailsTemporarily(array $input)
    {
        $cvv = $input['card']['cvv'];

        $vaultToken = null;

        if (empty($input['card']['vault_token']) === false)
        {
            $vaultToken = $input['card']['vault_token'];
        }
        else
        {
            $vaultToken = (new Card\Tokenex)->getVaultToken($input['card']['number']);
        }

        $key = $this->getCacheKey($input['payment']['id']);

        $data = [
            'cvv'         => $this->app['encrypter']->encrypt($cvv),
            'vault_token' => $vaultToken
        ];

        $this->app['cache']->store($this->secureCacheDriver)->put($key, $data, static::CACHE_TTL);
    }

    protected function setCardNumberAndCvv(array & $input)
    {
        $data = $this->getCardDetailsFromCache($input);

        $input['card']['number'] = (new Card\Tokenex)->getCardNumber($data['vault_token']);

        $input['card']['cvv'] = $this->app['encrypter']->decrypt($data['cvv']);
    }

    protected function getCardDetailsFromCache($input)
    {
        $key = $this->getCacheKey($input['payment']['id']);

        return $this->app['cache']->store($this->secureCacheDriver)->get($key) ?: [];
    }

    protected function getCacheKey($paymentId)
    {
        $key = sprintf(static::CACHE_KEY, $paymentId);

        return $key;
    }
}
