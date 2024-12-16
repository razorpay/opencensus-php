<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Models\Base;
use RZP\Services\BinService;

class Repository extends Base\Repository
{
    const BIN_SERVICE_PRIMARY_READ_MODE = 'primary';
    const BIN_SERVICE_SHADOW_READ_MODE = 'shadow';
    protected $entity = 'tokenised_iin';

    protected $appFetchParamRules = array(
        Entity::IIN => 'sometimes|integer|max:9|min:6',
        Entity::HIGH_RANGE => 'sometimes|integer|digits:9',
        Entity::LOW_RANGE => 'sometimes|integer|digits:9',
    );

    public function findByIin($iin)
    {
        $iin = $this->newQuery()
            ->where(Entity::IIN, $iin)
            ->first();

        return $iin;
    }

    public function findById($id)
    {
        $iin = $this->newQuery()
            ->where(Entity::ID, $id)
            ->first();

        return $iin;
    }

    public function findbyTokenIin($tokenIin)
    {

        $tokenisedIinService = (new Service());
        $binService = (new BinService());

        if (!empty($tokenIin) && $tokenisedIinService->shouldReadBinServiceInPrimaryMode($tokenIin) === true)
        {
            $adaptedBinServiceResponse = $binService->fetchTokenIINEntityFromBinService($tokenIin, self::BIN_SERVICE_PRIMARY_READ_MODE);

            if (isset($adaptedBinServiceResponse) && !empty($adaptedBinServiceResponse))
            {
                $tokenIinEntity = new Entity();

                return $tokenIinEntity->forceFill($adaptedBinServiceResponse);
            }
        }

        $repoTokenisedIINEntity = $this->fetchTokenIINMappingFromRepo($tokenIin);

        if(!empty($tokenIin) && $tokenisedIinService->shouldReadFromBinServiceInShadowMode($tokenIin) === true)
        {
            $adaptedBinServiceResponse = $binService->fetchTokenIINEntityFromBinService($tokenIin, self::BIN_SERVICE_SHADOW_READ_MODE);

            if (isset($adaptedBinServiceResponse) && !empty($adaptedBinServiceResponse))
            {
                $tokenisedIinService->compareBinServiceEntityAndApiServiceEntity($repoTokenisedIINEntity, $adaptedBinServiceResponse, ['iin' => $tokenIin, 'method_name' => __FUNCTION__]);
            }
        }

        return $repoTokenisedIINEntity;
    }

    public function findbyrange($tokenIin)
    {
        return $this->newQuery()
                    ->where(Entity::LOW_RANGE, 'like' , $tokenIin."%")
                    ->where(Entity::HIGH_RANGE, 'like' , $tokenIin."%")
                    ->first();
    }

    public function findbyLowRange($tokenIin)
    {
        return $this->newQuery()->where(Entity::LOW_RANGE,'=',$tokenIin)->first();
    }

    public function findbyHighRange($tokenIin)
    {
        return $this->newQuery()->where(Entity::HIGH_RANGE,'=',$tokenIin)->first();
    }

    public function findHighRange($tokenIin)
    {
        return $this->newQuery()->where(Entity::HIGH_RANGE,'=',$tokenIin)->first();
    }

    public function findLowRange($tokenIin)
    {
        return $this->newQuery()->where(Entity::LOW_RANGE,'=',$tokenIin)->first();
    }

    private function fetchTokenIINMappingFromRepo($tokenIin)
    {
        $tokenIin_8len = substr($tokenIin, 0, 8);
        $tokenIin_6len = substr($tokenIin, 0, 6);
        return $this->newQuery()
            ->where(function($query) use ($tokenIin, $tokenIin_8len,$tokenIin_6len)
            {
                $query->where(Entity::TOKEN_IIN_LENGTH , '=', 9)
                    ->where(Entity::LOW_RANGE, '<=', $tokenIin)
                    ->where(Entity::HIGH_RANGE, '>=', $tokenIin)
                    ->orwhere(Entity::TOKEN_IIN_LENGTH , '=', 8)
                    ->where(Entity::LOW_RANGE, '<=', $tokenIin_8len)
                    ->where(Entity::HIGH_RANGE, '>=', $tokenIin_8len)
                    ->orwhere(Entity::TOKEN_IIN_LENGTH , '=', 6)
                    ->where(Entity::LOW_RANGE, '<=', $tokenIin_6len)
                    ->where(Entity::HIGH_RANGE, '>=', $tokenIin_6len);
            })
            ->first();
    }

}

