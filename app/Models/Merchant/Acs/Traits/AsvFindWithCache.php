<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Redis;
use Cache;
use RZP\Constants\Entity as E;
use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\Constants;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\Traits\AsvFindEntity;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Entity;
use RZP\Models\Merchant\Repository as MerchantRepository;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\RepoToSdkWrapperMap;
use RZP\Exception;
use Database\Connection;


trait AsvFindWithCache
{
    use AsvCacheKeys {
        AsvCacheKeys::getCacheKey as getAsvCacheKey;
    }

    use CacheQueries {
        CacheQueries::Find as FindUsingCacheQueries;
    }

    use AsvFindEntity {
        AsvFindEntity::Find as AsvFindEntity;
        AsvFindEntity::findOrFail as AsvFindEntityOrFail;
    }


    public function find($id, $columns = array('*'), string $connectionType = null)
    {
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND);
        if ($shouldCallAsv === true)
        {
            return Cache::store('query_cache_live')
                ->tags(strtolower($this->entity) . '_' . $id)
                ->remember(
                    $this->getAsvCacheKey($id, $columns, $connectionType),
                    $this->getCacheTtl(),
                    function () use ($id, $columns, $connectionType) {
                        return $this->AsvFindEntity($id, $columns, $connectionType);
                    });
        }

        $this->trace->count(Metric::ASV_READ_REQUEST_ROUTING_RESULT, [
            'source' => $connectionType ?? \RZP\Models\Merchant\Constants::API_DB,
            'route' => (new AsvRouter())->getRouteOrJobName(),
        ]);

        return $this->FindUsingCacheQueries($id, $columns, $connectionType);
    }


    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND_OR_FAIL);
        if ($shouldCallAsv === true)
        {
            return Cache::store('query_cache_live')
                ->tags(strtolower($this->entity) . '_' . $id)
                ->remember(
                    $this->getAsvCacheKey($id, $columns, $connectionType),
                    $this->getCacheTtl(),
                    function () use ($id, $columns, $connectionType) {
                        return $this->AsvFindEntityOrFail($id, $columns, $connectionType);
                    });
        }

        return$this->findOrFailDatabase($id, $columns, $connectionType);
    }
}

