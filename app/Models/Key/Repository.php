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
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchant');
        return $this->getKeysForMerchantV2($apiDBKeys, $routeName, $merchantId, $mode, $expired);
    }

    /**
     * @throws ServerErrorException
     */
    public function getKeysForMerchantV2($apiKeys, $routeName, $merchantId, $mode, $expired = false)
    {
        $enabled = $this->app[Constants::CREDCASE_API]->getKeysDualwriteVariant($merchantId, $mode, $routeName, "read");
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
        $this->logKeysRepoCall($mode, $routeName, 'getFirstActiveKeyForMerchant');
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
        $routeName = $this->app['request.ctx']->getRoute();
        $this->logKeysRepoCall($mode, $routeName, 'getActiveKeysForMerchants');
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->notExpired()
            ->get();
    }

    /**
     * @param string $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $this->logKeysRepoCall($mode, $routeName, 'getLatestActiveKeyForMerchant');
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->latest()
                    ->first();
    }

    public function findNotExpired($keyId)
    {
        return $this->newQuery()->notExpired()->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId)
    {
        $mode = $this->app['rzp.mode'];
        $routeName = $this->app['request.ctx']->getRoute();
        $this->logKeysRepoCall($mode, $routeName, 'findByMerchantIdAndKeyId');
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
    }

    public function getKeysForMerchantForLiveAndTestMode($merchantId, $mode, $expired = false)
    {
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);
        $routeName = $this->app['request.ctx']->getRoute();
        $this->logKeysRepoCall($mode, $routeName, 'getKeysForMerchantForLiveAndTestMode');
        if ($expired === false) {
            $query->notExpired();
        }

        return $query->get();
    }

    public function logKeysRepoCall($mode, $routeName, $functionName) {
        try {
            $this->trace->count(Metric::CREDCASE_KEY_READ_ROUTE_COUNT, ['route' => $routeName, 'function' => $functionName, 'mode' => $mode]);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::API_KEY_TRACE_ERROR, ['exception' => $e, 'route' => $routeName, 'function' => $functionName, 'mode' => $mode]);
        }
    }
}
