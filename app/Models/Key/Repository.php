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

        $apiKey = $query
                    ->remember($cacheTtl)
                    ->cacheTags($prefix . '_' . $this->entity . '_' . $id)
                    ->find($id, $columns);
        return $this->findV2($id, $apiKey, 'find', true);
    }

    public function findV2($id, $apiKey, $functionName, $includeExpired){
        try {
            $mode = $this->app['rzp.mode'];
            $routeName = $this->app['request.ctx']->getRoute();
            $authenticateUsingPassport = false;
            if ($this->app['request.ctx.v2']->shouldAuthenticateUsingPassport) {
                $authenticateUsingPassport = true;
            }
            $this->trace->count(Metric::CREDCASE_FIND_KEY_READ_ROUTE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => $functionName,
                    'passport' => $authenticateUsingPassport
                ]
            );
//            if(!$authenticateUsingPassport) {
//                return $apiKey;
//            }
//            $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($id, $mode, $routeName, $functionName, "find_read");
//            $credcaseKey = $this->app[Constants::CREDCASE_API]->findById($id, $includeExpired);
//            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiKey);
            return $apiKey;
        } catch (Exception) {
            return $apiKey;
        }
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
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiKeys);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'getKeysForMerchant',
                ]
            );
            return $apiKeys;
        }
    }

    public function getFirstActiveKeyForMerchant(string $merchantId, bool $includeSecret = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiDBKey = $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->first();
        return $this->getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey, $includeSecret);
    }


    public function getFirstActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey, bool $includeSecret = false) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getFirstActiveKeyForMerchant', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getFirstActiveKeyForMerchant', $enabled);
        if(!$enabled) {
            return $apiDBKey;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 1, false, $includeSecret);
            $credcaseKey = null;
            if($credcaseKeys->getCount() > 0){
                $credcaseKey = $credcaseKeys->getItems()->offsetGet(0);
            }
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'getFirstActiveKeyForMerchant',
                ]
            );
            return $apiDBKey;
        }
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
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants($merchantIds, $mode, count($merchantIds), $expired, false);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiDBKeys);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantIds], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'getActiveKeysForMerchants',
                ]
            );
            return $apiDBKeys;
        }
    }


    /**
     * @param string $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId, bool $includeSecret = false)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $apiDBKey = $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->latest()
                    ->first();
        return $this->getLatestActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey, $includeSecret);
    }

    public function getLatestActiveKeyForMerchantV2($merchantId, $mode, $routeName, $apiDBKey, bool $includeSecret = false) {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, 'getLatestActiveKeyForMerchant', "admin_read");
        $this->logKeysRepoCall($mode, $routeName, 'getLatestActiveKeyForMerchant', $enabled);
        if(!$enabled) {
            return $apiDBKey;
        }
        try {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_INITIATED);
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 1, false, $includeSecret);
            $credcaseKey = null;
            if($credcaseKeys->getCount() > 0){
                $credcaseKey = $credcaseKeys->getItems()->offsetGet(0);
            }
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'getLatestActiveKeyForMerchant',
                ]
            );
            return $apiDBKey;
        }
    }

    public function findNotExpired($keyId)
    {
        $apiKey = $this->newQuery()->notExpired()->find($keyId);
        return $this->findV2($keyId, $apiKey, 'findNotExpired', false);
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
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKey($routeName, $credcaseKey, $apiDBKey);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'findByMerchantIdAndKeyId',
                ]
            );
            return $apiDBKey;
        }
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
            $credcaseKeys = $this->app[Constants::CREDCASE_API]->getKeysForMerchants(array($merchantId), $mode, 10, $expired, false);
            return $this->app[Constants::CREDCASE_SERVICE]->fetchKeys($routeName, $credcaseKeys, $apiDBKeys);
        } catch (\Exception $e) {
            $this->trace->info(TraceCode::CREDCASE_REQUEST_FAILED, [['merchantId' => $merchantId], ["exception" => $e]]);
            $this->trace->count(Metric::KEY_API_DB_RESPONSE_COUNT, [
                    'route_name' => $routeName,
                    'mode' => $mode,
                    'function' => 'getKeysForMerchantForLiveAndTestMode',
                ]
            );
            return $apiDBKeys;
        }
    }

    public function logKeysRepoCall($mode, $routeName, $functionName, $enable = false) {
        try {
            $this->trace->count(Metric::CREDCASE_KEY_READ_ROUTE_COUNT, ['route' => $routeName, 'function' => $functionName, 'mode' => $mode, 'enable' => $enable]);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::API_KEY_TRACE_ERROR, ['exception' => $e, 'route' => $routeName, 'function' => $functionName, 'mode' => $mode]);
        }
    }
}
