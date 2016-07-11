<?php

namespace RZP\Models\Terminal;

use RZP\Models\Base;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Exception;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Terminal';

    protected $appFetchParamRules = array(
        Entity::GATEWAY             => 'sometimes',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::CARD                => 'sometimes|boolean',
        Entity::NETBANKING          => 'sometimes|boolean',
        Entity::SHARED              => 'sometimes|boolean',
        Entity::CATEGORY            => 'sometimes|integer|digits:4',
        'deleted'                   => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID => 'sometimes|string|max:50',
        Entity::GATEWAY_TERMINAL_ID => 'sometimes|alpha_num',
    );

    public function addQueryParamDeleted($query, $params)
    {
        if ($params['deleted'] === '1')
        {
            $query->withTrashed();
        }
    }

    public function getById($id)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                    ->findOrFailPublic($id);
    }

    public function getByMerchantId($mid)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                    ->where(Terminal\Entity::MERCHANT_ID, '=', $mid)
                    ->get();
    }

    public function getTerminalsForMerchantAndSharedMerchant($mid)
    {
        $merchantIds = [$mid, Merchant\Account::SHARED_ACCOUNT];

        return $this->newQuery()
                    ->whereIn(Terminal\Entity::MERCHANT_ID, $merchantIds)
                    ->get();
    }

    public function getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull($gatewayTerminalId, $gateway)
    {
        $repo = $this->repo;

        return $repo::withTrashed()
                    ->where(Terminal\Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->whereNotNull(Terminal\Entity::GATEWAY_RECON_PASSWORD)
                    ->first();
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

    public function getEmiTerminal($mId, $gateway, $duration)
    {
        $repo = $this->repo;

        return $repo::where(Terminal\Entity::MERCHANT_ID, '=', $mId)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->where(Terminal\Entity::SHARED, '=', '1')
                    ->where(Terminal\Entity::EMI, '=', '1')
                    ->where(Terminal\Entity::EMI_DURATION, '=', $duration)
                    ->first();
    }

    public function getSharedTerminalsOnCommonAccount()
    {
        return $this->newQuery()
                    ->where(Terminal\Entity::MERCHANT_ID, '=', Merchant\Account::SHARED_ACCOUNT)
                    ->get();
    }

    public function getAllSharedTerminals()
    {
        $repo = $this->repo;

        $map = Terminal\Shared::getSharedTerminalMapping();

        $sharedTerminalIds = array_keys($map);

        return $repo::whereIn(Terminal\Entity::ID, $sharedTerminalIds)
                    ->get();
    }

    public function deleteOrFail($entity)
    {
        $successCount = $entity->getUsedCount();
        $count = $this->getTotalUsedCount($entity);

        if (($count === 0) and
            ($successCount === 0))
        {
            $entity->forceDelete();

            return null;
        }
        else
        {
            $entity->deleteOrFail();

            return $this->newQuery()
                        ->withTrashed()
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
