<?php

namespace Models\Terminal;

use Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Terminal';

    public function getTerminalsByParams($params)
    {
        $repo = $this->repo;

        $query = (new $repo)->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        // When a merchant can have multiple terminals,
        // change this to get
        return $query->find();
    }

    public function getTerminalByMerchantId($id)
    {
        $repo = $this->repo;

        return $repo::where(Entity::MERCHANT_ID, '=', $id)->first();
    }
}