<?php

namespace RZP\Models\Terminal;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;

class Repository extends Base\Repository
{
    protected $entity = 'terminal';

    protected $appFetchParamRules = array(
        Entity::GATEWAY             => 'sometimes',
        Entity::MERCHANT_ID         => 'sometimes|alpha_num',
        Entity::CARD                => 'sometimes|boolean',
        Entity::NETBANKING          => 'sometimes|boolean',
        Entity::SHARED              => 'sometimes|boolean',
        Entity::CATEGORY            => 'sometimes|integer|digits:4',
        Entity::DELETED             => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID => 'sometimes|string|max:50',
        Entity::GATEWAY_ACQUIRER    => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID => 'sometimes|alpha_num',
        Entity::EMI                 => 'sometimes|in:0,1',
        Entity::ENABLED             => 'sometimes|in:0,1',
        Entity::NETWORK_CATEGORY    => 'sometimes|string|max:50',
    );

    public function fetchForPayment(Payment\Entity $payment)
    {
        if ($payment->hasRelation('terminal'))
        {
            return $payment->terminal;
        }

        $terminal = $this->getById($payment->getTerminalId());

        $payment->setRelation('terminal', $terminal);

        return $terminal;
    }

    public function addQueryParamDeleted($query, $params)
    {
        if ($params[Entity::DELETED] === '1')
        {
            $query->withTrashed();
        }
    }

    protected function addQueryParamShared($query, $params)
    {
        if ($params[Entity::SHARED] === '1')
        {
            $query->where(Entity::MERCHANT_ID, '=', Merchant\Account::SHARED_ACCOUNT);
        }
        else if ($params[Entity::SHARED] === '0')
        {
            $query->where(Entity::MERCHANT_ID, '!=', Merchant\Account::SHARED_ACCOUNT);
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
        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->get();
    }

    public function getTerminalsForMerchantAndSharedMerchant(Merchant\Entity $merchant)
    {
        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT];

        $query = $this->newQuery()
                      ->enabled();

        $this->addMerchantWhereCondition($query, $merchantIds);

        return $query->get();
    }

    public function getEmandateNetbankingTerminalsForMerchantAndSharedMerchant(Merchant\Entity $merchant)
    {
        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT];

        //
        // Emandate terminals have type 6 (recurring 3ds + recurring non 3ds)
        // This is because we don't have different terminals for the first
        // auth transaction and then subsequent recurring transactions.
        //

        $query = $this->newQuery()
                      ->enabled()
                      ->where(Entity::NETBANKING, true)
                      ->where(Entity::TYPE, 6)
                      ->whereIn(Entity::GATEWAY, Payment\Gateway::$recurringGateways);

        $this->addMerchantWhereCondition($query, $merchantIds);

        return $query->get();
    }

    protected function addMerchantWhereCondition($query, array $merchantIds)
    {
        $query->where(
            function ($query) use ($merchantIds)
            {
                // Condition for the merchant id being directly in the terminal
                $query->whereIn(Entity::MERCHANT_ID, $merchantIds);

                //
                // Condition for getting terminals where merchant id is
                // associated through the many-to-many association in
                // merchant-terminal table.
                //
                $query->orWhereHas(
                    'merchants',
                    function ($query) use ($merchantIds)
                    {
                        $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
                    });
            });
    }

    public function getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull($gatewayTerminalId, $gateway)
    {
        return $this->newQuery()
                    ->withTrashed()
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereNotNull(Entity::GATEWAY_RECON_PASSWORD)
                    ->first();
    }

    public function getByIdAndMerchantId($mid, $tid)
    {
        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->findOrFailPublic($tid);
    }

    public function getByMerchantIdAndGateway($mid, $gateway)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY, '=', $gateway);

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->first();
    }

    public function getSharedTerminalForGateway($gateway)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->enabled()
                    ->get();
    }

    public function getSharedTerminalForGatewayWithCategory($gateway, $category)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->where(Entity::CATEGORY, '=', $category)
                    ->enabled()
                    ->first();
    }

    public function getEmiTerminal($mId, $gateway, $duration)
    {
        $query = $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->where(Entity::EMI, '=', '1')
                    ->where(Entity::EMI_DURATION, '=', $duration)
                    ->enabled();

        $this->addMerchantWhereCondition($query, [$mId]);

        return $query->first();
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
                    ->whereIn(Entity::ID, $sharedTerminalIds)
                    ->enabled()
                    ->get();
    }

    public function getTpvTerminalIdsForGateway($gateway)
    {
        $tpvCategories = Category::getTPVCategories();

        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->whereIn(Entity::NETWORK_CATEGORY, $tpvCategories)
                    ->enabled()
                    ->get([Entity::ID]);
    }

    public function getTerminalIdsForGateway($gateway, $exclude = [])
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->whereNotIn(Entity::ID, $exclude)
                    ->enabled()
                    ->get([Entity::ID]);
    }

    public function getDirectTerminalsForGateway(string $gateway): PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->where(Entity::MERCHANT_ID, '!=', Account::SHARED_ACCOUNT)
                    ->enabled()
                    ->get();
    }

    public function deleteOrFail($entity)
    {
        $count = $this->repo->payment->getTotalUsedCountForTerminal(
                    $entity->getId());

        if ($count === 0)
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

    public function addMerchantToTerminal(Entity $terminal, string $merchantId)
    {
        $terminal->merchants()->attach($merchantId);
    }

    public function removeMerchantFromTerminal(Entity $terminal, string $merchantId)
    {
        $terminal->merchants()->detach($merchantId);
    }
}
