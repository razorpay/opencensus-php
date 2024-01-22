<?php

namespace RZP\Models\Card\TokenisedIIN;

use RZP\Models\Base;

class Repository extends Base\Repository
{
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

}

