<?php

namespace RZP\Models\Key;

use Crypt;
use Illuminate\Support\Facades\Redis;

use Razorpay\Spine\Exception\ValidationFailureException;
use RZP\Exception;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\ServerErrorException;
use RZP\Models\Key;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Error\ErrorCode;
use RZP\Constants\Product;
use RZP\Http\Throttle\Constant as Throttle;
use RZP\Trace\TraceCode;


class Core extends Base\Core
{
    public function createFirstKey($merchant, $mode)
    {
        $keys = $this->repo->key->getKeysForMerchant($merchant->getId(), $mode);

        if (count($keys) > 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_KEY_ALREADY_CREATED);
        }

        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchant->getId(), $mode, $routeName, 'createFirstKey', "create");

        return $this->repo->transaction(function() use ($enabled, $merchant, $mode)
        {
            $key = $this->create($merchant, $mode, $enabled);
            if(!$enabled) {
                (new Credcase)->migrate($key, $mode);
            }
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
     * @throws ServerErrorException
     */
    public function create($merchant, $mode, $enabled = false): Entity
    {
        $this->repo->assertTransactionActive();

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
        $key = null;
        $routeName = Utils::getRoute();
        try {
            if (!$enabled) {
                $key = new Key\Entity;
                $key->merchant()->associate($merchant);
                $key->build();

                // Generate secret which will be returned to merchant
                $secret = $key->generateSecret();
            } else {
                $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED, [['id' => $merchant->getId(), 'mode' => $mode, 'route_name' => $routeName]]);
                $credcaseKey = $this->app[Constants::CREDCASE_API]->create($mode, $merchant->getId());
                $key = $this->app[Constants::CREDCASE_SERVICE]->transformToApiKeyResponse($credcaseKey);
            }
        } catch (Exception\ServerErrorException $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [['id' => $merchant->getId(), 'mode' => $mode, 'error' => $e->getMessage(), 'route_name' => $routeName]]);
            throw $e;
        }

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

    /**
     * Expires a key and processes the expiration using outbox to credcase.
     *
     * @param Key\Entity $key The key entity to expire.
     * @return void
     */
    public function expireKeyWithOutbox(Key\Entity $key)
    {
        return $this->repo->transaction(function () use ($key) {
            $key->setExpired();

            $this->repo->key->saveOrFail($key);
            // TODO Replace a Credcase Call here instead of Expire Job
            (new Credcase)->expire($key);
        });
    }


    public function rollKey($merchantId, $keyId, $mode, $delay)
    {
        Key\Entity::verifyIdAndStripSign($keyId);

        Key\Validator::checkForDemoKeys($keyId);

        $old = $this->repo->key->findByMerchantIdAndKeyId($merchantId, $keyId, false);
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'rollKey', "rotate");

        if ($old === null)
        {
            throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_INVALID_ID);
        }
        try {
            if (!$enabled) {
                return $this->repo->transaction(function () use ($old, $delay, $mode) {
                    $this->expireKey($old, $delay);
                    $key = $this->create($old->merchant, $mode);
                    (new Credcase)->rotate($old, $key, $mode);
                    $keysData['old'] = $old->toArrayPublic();
                    $keysData['new'] = $key->toArrayPublicWithSecret();
                    return $keysData;
                });
            } else {
                $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED, [['id' => $merchantId, 'keyId' => $keyId, 'mode' => $mode, 'route_name' => $routeName]]);
                return $this->repo->transaction(function () use ($old, $delay, $merchantId, $keyId) {
                    $rotateResponse = $this->app[Constants::CREDCASE_API]->rotate($merchantId, $keyId, $delay);
                    $this->expireKey($old, $delay);
                    $newKey = $this->app[Constants::CREDCASE_SERVICE]->transformToApiKeyResponse($rotateResponse->getNewKey());
                    $this->repo->saveOrFail($newKey);
                    return $this->app[Constants::CREDCASE_SERVICE]->transformToRotateApiKeyResponse($rotateResponse);
                });
            }
        } catch (Exception\ServerErrorException $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [['id' => $merchantId, 'mode' => $mode, 'keyId' => $keyId, 'error' => $e->getMessage(), 'route_name' => $routeName]]);
            throw $e;
        }

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
