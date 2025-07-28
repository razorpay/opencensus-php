<?php

namespace RZP\Models\Key;

use App;
use Exception;
use RZP\Constants\Mode;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Merchant\Acs\Traits\AsvCacheKeys;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use Cache;

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
        $app = App::getFacadeRoot();
        $prefix = Pricing\Repository::getQueryCachePrefixForDistributingLoad();
        $mode = $app['rzp.mode'] ?? null;
        $store = ($mode === Mode::TEST) ? 'query_cache_test' : 'query_cache_live';
        $tag = $prefix . '_' . $this->entity . '_' . $id;
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($id, $mode, $routeName, 'findKey', "find_read");

        $keyArray = Cache::store($store)
            ->tags($tag)
            ->remember(
                $this->getCacheKey($id, $columns, $connectionType),
                $cacheTtl,
                function () use ($id, $routeName, $tag, $enabled, $columns, $connectionType) {
                    $this->trace->count(Metric::KEY_API_DB_RETRIEVAL_COUNT, ['function' => 'find']);
                    $key = $this->findV2($id, $routeName, $tag, $enabled, $columns, $connectionType);
                    return $this->buildArrayAttributeMapFromKey($key);
                }
            );
        $hydratedItems = \RZP\Models\Key\Entity::hydrate($keyArray->all());
        return $hydratedItems->first();
    }

    public function findV2($id, $routeName, $tag, $enabled, $columns = ['*'], string $connectionType = null)
    {
        $mode = $this->app[Constants::REQUEST_CTX]->getMode();

        if (!$enabled) {
            return $this->getKeyFromDB($id, $columns, $connectionType);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED, [['id' => $id], ['tag' => $tag]]);
            $credcaseKey = $this->app[Constants::CREDCASE_API]->findById($id, true, true);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, null);
        } catch (Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'id' => $id,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'findKey',
                'error' => true
            ]);
            // Fallback to DB query on Credcase failure
            return $this->getKeyFromDB($id, $columns, $connectionType);
        }
    }

    private function getKeyFromDB($id, $columns = ['*'], string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));
        return $query->find($id, $columns);
    }

    public function getKeysForMerchant($merchantId, $mode, $expired = false)
    {
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getKeysForMerchant', "read");
        return $this->getKeysForMerchantV2($merchantId, $routeName, $mode, $enabled, $expired);
    }

    public function getKeysForMerchantV2($merchantId, $routeName, $mode, $enabled, $expired = false)
    {
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchant', $enabled);

        if (!$enabled) {
            return $this->getKeysForMerchantFromDB($merchantId, $mode, $expired);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->list($merchantId, $mode, $expired);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantId' => $merchantId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'getKeysForMerchant',
                'error' => true
            ]);
            return $this->getKeysForMerchantFromDB($merchantId, $mode, $expired);
        }
    }

    private function getKeysForMerchantFromDB($merchantId, $mode, $expired = false)
    {
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);
        if ($expired === false) {
            $query->notExpired();
        }
        return $query->get();
    }

    public function getFirstActiveKeyForMerchant(string $merchantId, bool $includeSecret = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getFirstActiveKeyForMerchant', "admin_read");
        return $this->getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $enabled, $includeSecret);
    }

    public function getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $enabled, bool $includeSecret = false)
    {
        $this->logKeysRepoCall($mode, $routeName, 'getFirstActiveKeyForMerchant', $enabled);

        if (!$enabled) {
            return $this->getFirstActiveKeyForMerchantFromDB($merchantId);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 1, false, $includeSecret);
            $credcaseKey = null;
            if ($credcaseKeys->getCount() > 0) {
                $credcaseKey = $credcaseKeys->getItems()->offsetGet(0);
            }
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantId' => $merchantId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'getFirstActiveKeyForMerchant',
                'error' => true
            ]);
            return $this->getFirstActiveKeyForMerchantFromDB($merchantId);
        }
    }

    private function getFirstActiveKeyForMerchantFromDB(string $merchantId)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->notExpired()
            ->first();
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
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($mode, $mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', "admin_read");
        return $this->getActiveKeysForMerchantsV2($merchantIds, $routeName, $mode, $enabled);
    }

    public function getActiveKeysForMerchantsV2($merchantIds, $routeName, $mode, $enabled, $expired = false)
    {
        $this->logKeysRepoCall($mode, $routeName, 'getActiveKeysForMerchants', $enabled);

        if (!$enabled) {
            return $this->getActiveKeysForMerchantsFromDB($merchantIds);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants($merchantIds, $mode, count($merchantIds), $expired, false);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantIds' => $merchantIds,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'getActiveKeysForMerchants',
                'error' => true
            ]);
            return $this->getActiveKeysForMerchantsFromDB($merchantIds);
        }
    }

    private function getActiveKeysForMerchantsFromDB(array $merchantIds): ?Base\PublicCollection
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->notExpired()
            ->get();
    }

    /**
     * @param string $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId, bool $includeSecret = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getLatestActiveKeyForMerchant', "admin_read");
        return $this->getLatestActiveKeyForMerchantV2($merchantId, $routeName, $mode, $enabled, $includeSecret);
    }

    public function getLatestActiveKeyForMerchantV2($merchantId, $routeName, $mode, $enabled, bool $includeSecret = false)
    {
        $this->logKeysRepoCall($mode, $routeName, 'getLatestActiveKeyForMerchant', $enabled);

        if (!$enabled) {
            return $this->getLatestActiveKeyForMerchantFromDB($merchantId);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 1, false, $includeSecret);
            $credcaseKey = null;
            if ($credcaseKeys->getCount() > 0) {
                $credcaseKey = $credcaseKeys->getItems()->offsetGet(0);
            }
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantId' => $merchantId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'getLatestActiveKeyForMerchant',
                'error' => true
            ]);
            return $this->getLatestActiveKeyForMerchantFromDB($merchantId);
        }
    }

    private function getLatestActiveKeyForMerchantFromDB(string $merchantId)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->notExpired()
            ->latest()
            ->first();
    }

    public function findNotExpired($keyId, $includeApiResponse = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($keyId, $mode, $routeName, 'findNotExpired', "find_read_v2");
        return $this->findNotExpiredV2($keyId, $routeName, $mode, $enabled, $includeApiResponse);
    }

    public function findNotExpiredV2($keyId, $routeName, $mode, $enabled, $includeApiResponse)
    {
        $this->logKeysRepoCall($mode, $routeName, 'findNotExpired', $enabled);

        if (!$enabled || $includeApiResponse) {
            return $this->findNotExpiredFromDB($keyId);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKey = $this->app[Constants::CREDCASE_API]->findById($keyId, false, true);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, null);
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'keyId' => $keyId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'findNotExpired',
                'error' => true
            ]);
            return $this->findNotExpiredFromDB($keyId);
        }
    }

    private function findNotExpiredFromDB($keyId)
    {
        return $this->newQuery()
            ->notExpired()
            ->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId, $enabled = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'findByMerchantIdAndKeyId', "admin_read");
        return $this->findByMerchantIdAndKeyIdV2($merchantId, $keyId, $routeName, $mode, $enabled);
    }

    public function findByMerchantIdAndKeyIdV2($merchantId, $keyId, $routeName, $mode, $enabled)
    {
        $this->logKeysRepoCall($mode, $routeName, 'findByMerchantIdAndKeyId', $enabled);

        if (!$enabled) {
            return $this->findByMerchantIdAndKeyIdFromDB($merchantId, $keyId);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKey = $this->app[Constants::CREDCASE_API]->getKeyByMerchantAndId($merchantId, $keyId);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantId' => $merchantId,
                'keyId' => $keyId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'findByMerchantIdAndKeyId',
                'error' => true
            ]);
            return $this->findByMerchantIdAndKeyIdFromDB($merchantId, $keyId);
        }
    }

    private function findByMerchantIdAndKeyIdFromDB($merchantId, $keyId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::ID, '=', $keyId)
            ->first();
    }

    public function getKeysForMerchantForLiveAndTestMode($merchantId, $mode, $expired = false)
    {
        $routeName = Utils::getRoute();
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', "admin_read");
        return $this->getKeysForMerchantForLiveAndTestModeV2($merchantId, $routeName, $mode, $enabled, $expired);
    }

    public function getKeysForMerchantForLiveAndTestModeV2($merchantId, $routeName, $mode, $enabled, $expired = false)
    {
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchantForLiveAndTestMode', $enabled);

        if (!$enabled) {
            return $this->getKeysForMerchantForLiveAndTestModeFromDB($merchantId, $mode, $expired);
        }

        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 10, $expired, false);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, null);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::CREDCASE_REQUEST_FAILED, [
                'merchantId' => $merchantId,
                'exception' => $e,
                'message' => $e->getMessage()
            ]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                'route_name' => $routeName,
                'mode' => $mode,
                'function' => 'getKeysForMerchantForLiveAndTestMode',
                'error' => true
            ]);
            return $this->getKeysForMerchantForLiveAndTestModeFromDB($merchantId, $mode, $expired);
        }
    }

    private function getKeysForMerchantForLiveAndTestModeFromDB($merchantId, $mode, $expired = false)
    {
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);
        if ($expired === false) {
            $query->notExpired();
        }
        return $query->get();
    }

    public function logKeysRepoCall($mode, $routeName, $functionName, $enable = false) {
        try {
            $this->trace->count(Metric::CREDCASE_KEY_READ_ROUTE_COUNT, ['route' => $routeName, 'function' => $functionName, 'mode' => $mode, 'enable' => $enable]);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::API_KEY_TRACE_ERROR, ['exception' => $e, 'route' => $routeName, 'function' => $functionName, 'mode' => $mode]);
        }
    }

    private function buildArrayAttributeMapFromKey(?Entity $key): \Illuminate\Support\Collection
    {
        if(empty($key)){
            return collect([]);
        }
        $keyMap = $key->attributesToArray();
        $keyMap[Entity::SECRET] = $key->getSecret();
        return collect([$keyMap]);
    }

    public function getCacheKey($id, $columns, string $connectionType = null)
    {
        $tag              = 'credcase:{' . strtolower($this->entity) . '_' . $id . '}';
        $columnsString    = md5(serialize($columns));
        $connectionSuffix = $connectionType ? ':' . $connectionType : '';
        return "tag:{$tag}:{$columnsString}{$connectionSuffix}:key";
    }
}
