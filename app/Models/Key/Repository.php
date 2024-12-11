<?php

namespace RZP\Models\Key;

use App;
use Exception;
use RZP\Exception\ServerErrorException;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;

class Repository extends Base\Repository
{
    const ROUTE_NAMES = array('merchant_fetch_keys');



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

        if (in_array($routeName, static::ROUTE_NAMES)) {
            $resolvedKeys = $this->getKeysForMerchantV2($apiDBKeys, $routeName, $merchantId, $mode, $expired);
            return $resolvedKeys;
        }

        return $apiDBKeys;
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
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
    }

    public function getKeysForMerchantForLiveAndTestMode($merchantId, $mode, $expired = false)
    {
        $query = $this->newQueryWithConnection($mode)->merchantId($merchantId);

        if ($expired === false) {
            $query->notExpired();
        }

        return $query->get();
    }
}
