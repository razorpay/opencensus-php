<?php

namespace RZP\Models\Pincode\ZipcodeDirectory;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'zipcode_directory';

    public function findByZipcodeAndCountry($zipcode, $country)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::ZIPCODE, '=', $zipcode)
            ->where(Entity::COUNTRY, '=', $country)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->first();
    }
}
