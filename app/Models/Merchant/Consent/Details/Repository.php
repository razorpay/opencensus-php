<?php


namespace RZP\Models\Merchant\Consent\Details;

use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Base;

class Repository extends Base\Repository
{

    protected $entity = 'merchant_consent_details';

    public function getByUrl(string $url)
    {
        return $this->newQueryOnSlave()
                    ->where(Entity::URL, '=', $url)
                    ->first();

    }

    public function getById(string $id)
    {
        return $this->newQuery()
            ->where(Entity::ID, '=', $id)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->firstOrFail();
    }
}
