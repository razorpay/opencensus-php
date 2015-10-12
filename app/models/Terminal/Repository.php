<?php

namespace Models\Terminal;

use Models\Base;
use Models\Terminal;
use Models\Payment;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Terminal';

    protected $appFetchParamRules = array(
        Entity::GATEWAY         => 'sometimes',
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::GATEWAY         => 'sometimes',
        Entity::CARD            => 'sometimes|boolean',
        Entity::NETBANKING      => 'sometimes|boolean',
        Entity::SHARED          => 'sometimes|boolean',
        Entity::CATEGORY        => 'sometimes|integer|digits:4',
        'deleted'               => 'sometimes|boolean',
    );

    public function addQueryParamDeleted($query, $params)
    {
        if ($params['deleted'] === '1')
        {
            $query->withTrashed();
        }
    }

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

    public function getSharedTerminalForGateway($gateway)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->where(Terminal\Entity::SHARED, '=', '1')
                    ->get();
    }

    public function getSharedTerminalForGatewayWithCategory($gateway, $category)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->where(Terminal\Entity::SHARED, '=', '1')
                    ->where(Terminal\Entity::CATEGORY, '=', $category)
                    ->first();
    }

    public function deleteOrFail($entity)
    {
        $repo = $this->repo;

        $count = $this->getTotalUsedCount($entity);

        if ($count === 0)
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

    public function getTotalUsedCount($terminal)
    {
        return (new Payment\Entity)->newQuery()
                    ->where(Payment\Entity::TERMINAL_ID, '=', $terminal->getId())
                    ->count();
    }
}
