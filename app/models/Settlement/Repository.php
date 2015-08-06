<?php

namespace Models\Settlement;

use Models\Base;
use Models\Settlement;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function fetchBetweenTimestamp($from, $to, $merchantId)
    {
        $repo = $this->repo;
        return $repo::whereBetween(Entity::CREATED_AT, [$from, $to])
            ->where(Base\Common::MERCHANT_ID, '=', $merchantId)
            ->get();
    }

    protected $entity = 'Settlement';
}
