<?php

namespace RZP\Models\Card\IIN;

use RZP\Base\ConnectionType;
use RZP\Constants\Environment;
use RZP\Models\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Card;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Services\BinService;
use RZP\Http\Request\Requests;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use CacheQueries;

    const BIN_SERVICE_PRIMARY_READ_MODE = 'primary';
    const BIN_SERVICE_SHADOW_READ_MODE  = 'shadow';

    protected $entity = 'iin';

    protected $appFetchParamRules = array(
        Entity::IIN             => 'sometimes|integer|digits:6',
        Entity::NETWORK         => 'sometimes|alpha_space',
        Entity::INTERNATIONAL   => 'sometimes|in:0,1',
        Entity::EMI             => 'sometimes|in:0,1',
        Entity::TYPE            => 'sometimes|string|in:debit,credit,prepaid,unknown',
        Entity::OTP_READ        => 'sometimes|in:0,1',
        Entity::ISSUER          => 'sometimes|string',
    );

    protected function addQueryParamInternational($query, $params)
    {
        $international = $params[Entity::INTERNATIONAL];

        if ($international === '1')
        {
            $query->where(Entity::COUNTRY, '!=', 'IN');
        }
        else
        {
            $query->where(Entity::COUNTRY, '=', 'IN');
        }
    }

    protected function addQueryOrder($query)
    {
        $query->orderBy(Entity::CREATED_AT, 'desc')
              ->orderBy(Entity::IIN, 'desc');
    }

    public function fetch(array $params,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        $iinService = (new Service());
        $entities = null;

        if(isset($params['iin']) && $iinService->shouldFetchDashboardIINFromBinService($params['iin']) === true)
        {
            $binService = (new BinService());

            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($params['iin'], self::BIN_SERVICE_PRIMARY_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                $entities = new PublicCollection([$this->fillIinEntity($binServiceIINEntity)]);
            }
        }
        else
        {
            $entities = $this->entity->fetch($params, null, ConnectionType::REPLICA);
        }

        return $entities;
    }
    public function isMerchantIdRequiredForFetch()
    {
        return false;
    }

    /**
     * @param int    $iin
     * @param string $issuer
     *
     * @return mixed
     */
    public function findByIinWithIssuerAndType(int $iin, string $issuer, string $type)
    {
        $iin = $this->newQuery()
                    ->where(Entity::IIN, $iin)
                    ->where(Entity::ISSUER, $issuer)
                    ->where(Entity::TYPE, $type)
                    ->first();

        return $iin;
    }

    public function findIinsByFlows(int $val)
    {
        $iin = $this->newQuery()
                    ->whereRaw(Entity::FLOWS .' & ' . $val . ' = ' . $val)
                    ->select(Entity::IIN)
                    ->get()
                    ->pluck(Entity::IIN)
                    ->toArray();

        return $iin;
    }

    public function findIinsBySubType(string $val)
    {
        $iin = $this->newQuery()
            ->where(Entity::SUBTYPE, $val)
            ->select(Entity::IIN)
            ->get()
            ->pluck(Entity::IIN)
            ->toArray();

        return $iin;
    }

    public function findOtpEnabledIins()
    {
        $otpVal = Flow::$flows[Flow::OTP];

        $headlessOtpVal = Flow::$flows[Flow::HEADLESS_OTP];

        $iin = $this->newQuery()
                    ->whereRaw(Entity::FLOWS .' & ' . $otpVal . ' = ' . $otpVal)
                    ->orWhereRaw(Entity::FLOWS .' & ' . $headlessOtpVal . ' = ' . $headlessOtpVal)
                    ->select(Entity::IIN)
                    ->get()
                    ->pluck(Entity::IIN)
                    ->toArray();

        return $iin;
    }

    public function find($iin, $columns = array('*'), string $connectionType = null)
    {
        $iinService = (new Service());
        $binService = (new BinService());

        $apiServiceIINEntity = parent::find(substr($iin, 0, 6), $columns, $connectionType);

        if (!empty($iin) && $iinService->shouldReadBinServiceInPrimaryMode($iin) === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_PRIMARY_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                return $this->fillIinEntity($binServiceIINEntity);
            }
        }
        else if(!empty($iin) && $iinService->shouldReadFromBinServiceInShadowMode() === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_SHADOW_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                $iinService->compareBinServiceEntityAndApiServiceEntity($apiServiceIINEntity, $binServiceIINEntity, ['iin' => $iin, 'method_name' => __FUNCTION__]);
            }
        }

        return $apiServiceIINEntity;
    }

    public function fillIinEntity($binServiceIINEntity)
    {
        $iinEntity = new Entity();

        //remove bin service attributes that are not a part of IIN entity
        $fillableAttributes = $iinEntity->getFillableAttributes();

        foreach ($binServiceIINEntity as $attribute => $value)
        {
            if (!in_array($attribute, $fillableAttributes)) {
                unset($binServiceIINEntity[$attribute]);
            }
        }

        return $iinEntity->forceFill($binServiceIINEntity);
    }

    public function findOrFail($iin, $columns = array('*'), string $connectionType = null)
    {
        $iinService = (new Service());
        $binService = (new BinService());

        $apiServiceIINEntity = parent::findOrFail($iin, $columns, $connectionType);

        if (!empty($iin) && $iinService->shouldReadBinServiceInPrimaryMode($iin) === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_PRIMARY_READ_MODE);

            $iinEntity = new Entity();

            return $iinEntity->forceFill($binServiceIINEntity);
        }
        else if(!empty($iin) && $iinService->shouldReadFromBinServiceInShadowMode() === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_SHADOW_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                $iinService->compareBinServiceEntityAndApiServiceEntity($apiServiceIINEntity, $binServiceIINEntity, ['iin' => $iin, 'method_name' => __FUNCTION__]);
            }
        }

        return $apiServiceIINEntity;
    }

    public function findOrFailPublic($iin, $columns = array('*'), string $connectionType = null)
    {
        $iinService = (new Service());
        $binService = (new BinService());

        $apiServiceIINEntity = parent::findOrFailPublic($iin, $columns, $connectionType);

        if (!empty($iin) && $iinService->shouldReadBinServiceInPrimaryMode($iin) === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_PRIMARY_READ_MODE);

            $iinEntity = new Entity();

            return $iinEntity->forceFill($binServiceIINEntity);
        }
        else if(!empty($iin) && $iinService->shouldReadFromBinServiceInShadowMode() === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($iin, self::BIN_SERVICE_SHADOW_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                $iinService->compareBinServiceEntityAndApiServiceEntity($apiServiceIINEntity, $binServiceIINEntity, ['iin' => $iin, 'method_name' => __FUNCTION__]);
            }
        }

        return $apiServiceIINEntity;
    }


    public function findOrFailAPIEntity($iin, $columns = array('*'), string $connectionType = null)
    {
        return parent::find($iin, $columns, $connectionType);
    }

    public function findOrFailByPublicIdWithParams(
        string $id,
        array  $params,
        string $connectionType = null
    ) : Base\PublicEntity
    {
        $iinService = (new Service());
        $binService = (new BinService());

        if (!empty($id) && $iinService->shouldFetchDashboardIINFromBinService($id) === true)
        {
            $binServiceIINEntity = $binService->fetchEntityByIINFromBinService($id, self::BIN_SERVICE_PRIMARY_READ_MODE);

            if (isset($binServiceIINEntity) && !empty($binServiceIINEntity))
            {
                $entity = $this->fillIinEntity($binServiceIINEntity);
                $entity['source'] = 'bin-service';
                return $entity;
            }
        }

        //fallback to APi DB

        $id = substr($id, 0, 6);
        $id = Entity::verifyIdAndSilentlyStripSign($id);

        $entity = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
        $entity['source'] = 'api';
        return $entity;

    }
}
