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
        return $this->newQuery()
            ->where(function($query) use ($tokenIin, $tokenIin_8len)
            {
                $query->where(Entity::LOW_RANGE, '<=', $tokenIin)
                    ->where(Entity::HIGH_RANGE, '>=', $tokenIin)
                    ->orwhere(Entity::LOW_RANGE, '<=', $tokenIin_8len)
                    ->where(Entity::HIGH_RANGE, '>=', $tokenIin_8len);
            })
            ->first();
    }
}
