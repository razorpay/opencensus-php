<?php

namespace Models\Terminal;

use Models\Base;
use Models\Terminal;

class Repository extends Base\Repository
{
    protected $entity = 'Terminal';

    public function getByParams($params)
    {
        $repo = $this->repo;

        $query = (new $repo)->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        return $query->get();
    }

    public function getByMerchantId($id)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::MERCHANT_ID, '=', $id)->first();
    }

    public function getByGatewayTerminalId($gatewayTerminalId)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->findOrFail();
    }
}