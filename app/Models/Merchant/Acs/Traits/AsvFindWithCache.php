<?php

namespace RZP\Models\Merchant\Acs\Traits;

use Redis;
use Cache;
use RZP\Constants\Entity as E;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\Constants;
use RZP\Models\Base\QueryCache\CacheQueries;
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

    use CacheQueries {
        CacheQueries::Find as FindUsingCacheQueries;
    }

    use AsvFindEntity {
        AsvFindEntity::Find as AsvFind;
    }


    public function find($id, $columns = array('*'), string $connectionType = null)
    {
        $shouldCallAsv = $this->asvRouter->shouldRouteFindToAccountService($id, $columns, $connectionType, get_class($this), FunctionConstant::FIND);

        if ($shouldCallAsv === true)
        {
            return Cache::store('query_cache_live')->tags(strtolower($this->entity) . '_' . $id)->remember($this->getCacheKey($id, $columns, $connectionType), $this->getCacheTtl(), function () use ($id, $columns, $connectionType) {
                return $this->AsvFind($id, $columns, $connectionType);
            });
        }

        return $this->FindUsingCacheQueries($id, $columns, $connectionType);
    }

    public function getCacheKey($id, $columns, string $connectionType = null)
    {
        $tag = 'asv_'.strtolower($this->entity).'_'.$id;
        $columnsString = md5(serialize($columns));
        $connectionSuffix = $connectionType ? ':' . $connectionType : '';
        return "tag:{$tag}:{$columnsString}{$connectionSuffix}:key";
    }
}

