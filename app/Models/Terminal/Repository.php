<?php

namespace RZP\Models\Terminal;

use RZP\Models\Base;
use RZP\Models\Terminal;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Exception;

class Repository extends Base\Repository
{
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
        Entity::GATEWAY_ACQUIRER    => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID => 'sometimes|alpha_num',
        Entity::EMI                 => 'sometimes|in:0,1',
        Entity::ENABLED             => 'sometimes|in:0,1',
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
        return $this->newQuery()
                    ->withTrashed()
                    ->findOrFailPublic($id);
    }

    public function getByMerchantId($mid)
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->merchantId($mid)
                    ->get();
    }

    public function getTerminalsForMerchantAndSharedMerchant($mid)
    {
        $merchantIds = [$mid, Merchant\Account::SHARED_ACCOUNT];

        return $this->newQuery()
                    ->whereIn(Terminal\Entity::MERCHANT_ID, $merchantIds)
                    ->enabled()
                    ->get();
    }

    public function getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull($gatewayTerminalId, $gateway)
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->where(Terminal\Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->whereNotNull(Terminal\Entity::GATEWAY_RECON_PASSWORD)
                    ->enabled()
                    ->first();
    }

    public function getByIdAndMerchantId($mid, $tid)
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->merchantId($mid)
                    ->findOrFailPublic($tid);
    }

    public function getByMerchantIdAndGateway($mid, $gateway)
    {
        return $this->newQuery()
                    ->merchantId($mid)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->first();
    }

    public function getSharedTerminalForGateway($gateway)
    {
        return $this->newQuery()
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->enabled()
                    ->get();
    }

    public function getSharedTerminalForGatewayWithCategory($gateway, $category)
    {
        return $this->newQuery()
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->where(Terminal\Entity::CATEGORY, '=', $category)
                    ->enabled()
                    ->first();
    }

    public function getEmiTerminal($mId, $gateway, $duration)
    {
        return $this->newQuery()
                    ->merchantId($mId)
                    ->where(Terminal\Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->where(Terminal\Entity::EMI, '=', '1')
                    ->where(Terminal\Entity::EMI_DURATION, '=', $duration)
                    ->enabled()
                    ->first();
    }

    public function getSharedTerminalsOnCommonAccount()
    {
        return $this->newQuery()
                    ->merchantId(Merchant\Account::SHARED_ACCOUNT)
                    ->enabled()
                    ->get();
    }

    public function getAllSharedTerminals()
    {
        $map = Terminal\Shared::getSharedTerminalMapping();

        $sharedTerminalIds = array_keys($map);

        return $this->newQuery()
                    ->whereIn(Terminal\Entity::ID, $sharedTerminalIds)
                    ->enabled()
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
