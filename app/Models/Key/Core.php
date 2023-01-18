<?php

namespace RZP\Models\Key;

use Crypt;
use Illuminate\Support\Facades\Redis;

use RZP\Exception;
use RZP\Models\Key;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Http\Throttle\Constant as Throttle;

class Core extends Base\Core
{
    public function createFirstKey($merchant, $mode)
    {
        $keys = $this->repo->key->getKeysForMerchant($merchant->getId());

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        return $this->repo->transaction(function() use ($merchant, $mode)
        {
            $key = $this->create($merchant, $mode);

            (new Credcase)->migrate($key, $mode);

            return $key->toArrayPublicWithSecret();
        });
    }

    /**
     * Creates a key and saves to db.
     * Returns an array with key data and secret
     * in plain text
     *
     * @param $merchant
     * @param $mode
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function create($merchant, $mode): Entity
    {
        $this->repo->assertTransactionActive();

        $key = new Key\Entity;

        if (($mode === Mode::LIVE) and
            ($merchant->isActivated() === false))
        {
            // We need to allow key generation in case merchant is (ca activated / va activated)  and request is coming from banking
            $isMerchantCaActivated = (new Merchant\Core())->isCurrentAccountActivated($merchant);

            $isMerchantVaActivated = (new Merchant\Core())->isXVaActivated($merchant);

            $isRequestOriginBanking = ($this->app->basicauth->getRequestOriginProduct() === Product::BANKING);

            if ($isRequestOriginBanking === false || (($isMerchantCaActivated === false) and ($isMerchantVaActivated === false)))
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED);
            }

            $this->trace->count(Metric::KEY_GENERATION_BY_CA_ACTIVATED_MERCHANT_COUNT);
        }

        $key->merchant()->associate($merchant);
        $key->build();

        // Generate secret which will be returned to merchant
        $secret = $key->generateSecret();

        $this->repo->saveOrFail($key);

        $this->writeCache($key);

        $this->app['drip']->sendDripMerchantInfo($merchant, $this->app['drip']::KEY_GENERATED);

        return $key;
    }

    public function expireKey(Key\Entity $key, $delay)
    {
        $this->repo->assertTransactionActive();

        $key->checkAndSetExpired($delay);

        $this->repo->key->saveOrFail($key);
    }

    public function rollKey($merchantId, $keyId, array $input, $mode)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        Key\Validator::checkForDemoKeys($keyId);

        $old = $this->repo->key->findByMerchantIdAndKeyId($merchantId, $keyId);

        if ($old === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }

        $delay = false;

        if (isset($input['delay_roll']))
        {
            $delay = ($input['delay_roll'] === '1') ? true : false;
        }

        unset($input['delay_roll']);

        return $this->repo->transaction(function() use ($old, $delay, $mode)
        {
            $this->expireKey($old, $delay);

            $key = $this->create($old->merchant, $mode);

            (new Credcase)->rotate($old, $key, $mode);

            $keysData['old'] = $old->toArrayPublic();

            $keysData['new'] = $key->toArrayPublicWithSecret();

            return $keysData;
        });
    }

    public function getKeySecret($keyId)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        $key = $this->repo->key->findOrFailPublic($keyId);

        $secret = $key->getDecryptedSecret();

        return [
            'secret'      => $secret,
            'merchant_id' => $key->getMerchantId(),
        ];
    }

    /**
     * Map of key id to mid is maintained in cache for use by throttling layer.
     * @param  Entity $key
     * @return void
     */
    protected function writeCache(Entity $key)
    {
        $keyId      = $key->getPublicId();
        $merchantId = $key->merchant->getId();

        try
        {
           Redis::connection('throttle')->client()->set(Throttle::KEYID_MID_KEY_PREFIX . $keyId, $merchantId);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, null, null, compact('keyId', 'merchantId'));
        }
    }
    public function getLatestActiveKeyForMerchant($merchantId)
    {
        $merchantKey = $this->repo->key->getLatestActiveKeyForMerchant($merchantId);

        if(isset($merchantKey) === true)
        {
            return $merchantKey->getPublickey();
        }
        else
        {
            return "";
        }
    }
}
