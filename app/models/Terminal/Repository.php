<?php

namespace Models\Terminal;

use Models\Base;
use Models\Terminal;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Terminal';

    protected $appFetchParamRules = array(
        Entity::GATEWAY         => 'sometimes',
    );

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

    public function getByMerchantId($mid)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                    ->where(Terminal\Entity::MERCHANT_ID, '=', $mid)
                    ->get();
    }

    public function getByIdAndMerchantId($mid, $tid)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                    ->where(Terminal\Entity::MERCHANT_ID, '=', $mid)
                    ->findOrFailPublic($tid);
    }

    public function getByMerchantIdAndGateway($id, $gateway)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::MERCHANT_ID, '=', $id)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->first();
    }

    public function getByGatewayTerminalId($gatewayTerminalId)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->findOrFail();
    }

    public function deleteOrFail($entity)
    {
        if ($entity->getUsedCount() === 0)
        {
            $entity->forceDelete();

            return null;
        }
        else
        {
            $entity->deleteOrFail();

            return $repo::withTrashed()
                        ->findOrFail($entity->getId());
        }
    }

    public function restoreOrFail($terminal)
    {
        $restored = $terminal->restore();

        if ($restored === true)
            return $terminal;

        throw new Exception\DbQueryException(
            'restore',
            'terminal',
            $terminal->getAttributes());
    }
}
