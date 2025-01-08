<?php

namespace RZP\Models\Key;

use App;
use Exception;
use RZP\Constants\Mode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use function PHPUnit\Framework\isEmpty;

class Repository extends Base\Repository
{

    use CacheQueries;

    protected $entity = 'key';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
    ];

    public function find($id, $columns = ['*'], string $connectionType = null)
    {
        $cacheTtl = $this->getCacheTtl();

        $prefix = Pricing\Repository::getQueryCachePrefixForDistributingLoad();

        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->remember($cacheTtl)
                    ->cacheTags($prefix . '_' . $this->entity . '_' . $id)
                    ->find($id, $columns);
    }

    public function getKeysForMerchant($merchantId, $mode, $expired = false)
    {
        $routeName = $this->app['request.ctx']->getRoute();
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);

        if ($expired === false) {
            $query->notExpired();
        }
        $apiDBKeys = $query->get();
        return $this->getKeysForMerchantV2($apiDBKeys, $routeName, $merchantId, $mode, $expired);
    }

    /**
     * @throws ServerErrorException
     */
    public function getKeysForMerchantV2($apiKeys, $routeName, $merchantId, $mode, $expired = false)
    {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getKeysForMerchant', "read");
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchant', $enabled);
        if(!$enabled) {
            return $apiKeys;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->list($merchantId, $mode, $expired);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            return $apiKeys;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiKeys);
    }

    public function getFirstActiveKeyForMerchant(string $merchantId)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiDBKey = $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->first();
        return $this->getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey);
    }


    public function getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getFirstActiveKeyForMerchant', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getFirstActiveKeyForMerchant', $enabled);
        if(!$enabled) {
            return $apiDBKey;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants($merchantId, $mode, 1, false);
            $credcaseKey = isEmpty($credcaseKeys->getItems()) ? null : $credcaseKeys->getItems()->offsetGet(0);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            return $apiDBKey;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
    }

    /**
     * Get the active key entities for the given list of merchant ids.
     *
     * @param array $merchantIds
     *
     * @return Base\PublicCollection|null
     */
    public function getActiveKeysForMerchants(array $merchantIds): ?Base\PublicCollection
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiDBKeys =  $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->notExpired()
            ->get();
        return $this->getActiveKeysForMerchantsV2($apiDBKeys, $routeName, $merchantIds, $mode);
    }

    public function getActiveKeysForMerchantsV2($apiDBKeys, $routeName, $merchantIds, $mode, $expired = false) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($mode, $mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getActiveKeysForMerchants', $enabled);
        if(!$enabled) {
            return $apiDBKeys;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants($merchantIds, $mode, count($merchantIds), $expired);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantIds], ["exception" => $e]]);
            return $apiDBKeys;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiDBKeys);
    }


    /**
     * @param string $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiDBKey = $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->latest()
                    ->first();
        return $this->getLatestActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey);
    }

    public function getLatestActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getLatestActiveKeyForMerchant', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getLatestActiveKeyForMerchant', $enabled);
        if(!$enabled) {
            return $apiDBKey;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants($merchantId, $mode, 1, false);
            $credcaseKey = isEmpty($credcaseKeys->getItems()) ? null : $credcaseKeys->getItems()->offsetGet(0);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            return $apiDBKey;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
    }

    public function findNotExpired($keyId)
    {
        return $this->newQuery()->notExpired()->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiKey =  $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
        return $this->findByMerchantIdAndKeyIdV2($apiKey, $routeName, $merchantId, $mode, $keyId);
    }

    public function findByMerchantIdAndKeyIdV2($apiDBKey, $routeName, $merchantId, $mode, $keyId) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'findByMerchantIdAndKeyId', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'findByMerchantIdAndKeyId', $enabled);
        if(!$enabled) {
            return $apiDBKey;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKey = $this->app[Constants::CREDCASE_API]->getKeyByMerchantAndId($merchantId, $keyId);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            return $apiDBKey;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
    }

    public function getKeysForMerchantForLiveAndTestMode($merchantId, $mode, $expired = false)
    {
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);
        $routeName = $this->app['request.ctx']->getRoute();
        if ($expired === false) {
            $query->notExpired();
        }

        $apiDBKeys = $query->get();
        return $this->getKeysForMerchantForLiveAndTestModeV2($apiDBKeys, $routeName, $merchantId, $mode, $expired);
    }

    public function getKeysForMerchantForLiveAndTestModeV2($apiDBKeys, $routeName, $merchantId, $mode, $expired = false) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', $enabled);
        if(!$enabled) {
            return $apiDBKeys;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 10, $expired);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            return $apiDBKeys;
        }
        return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiDBKeys);
    }

    public function logKeysRepoCall($mode, $routeName, $functionName, $enable = false) {
        try {
            $this->trace->count(Metric::CREDCASE_KEY_READ_ROUTE_COUNT, ['route' => $routeName, 'function' => $functionName, 'mode' => $mode, 'enable' => $enable]);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::API_KEY_TRACE_ERROR, ['exception' => $e, 'route' => $routeName, 'function' => $functionName, 'mode' => $mode]);
        }
    }
}
