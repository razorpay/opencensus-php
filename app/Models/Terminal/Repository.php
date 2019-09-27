<?php

namespace RZP\Models\Terminal;

use DB;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Account;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use CacheQueries;

    protected $entity = 'terminal';

    protected $appFetchParamRules = array(
        Entity::GATEWAY                 => 'sometimes',
        Entity::ORG_ID                  => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID             => 'sometimes|alpha_num|size:14',
        Entity::CARD                    => 'sometimes|boolean',
        Entity::NETBANKING              => 'sometimes|boolean',
        Entity::SHARED                  => 'sometimes|boolean',
        Entity::CATEGORY                => 'sometimes|integer|digits:4',
        Entity::DELETED                 => 'sometimes|boolean',
        Entity::GATEWAY_MERCHANT_ID     => 'sometimes|string|max:50',
        Entity::GATEWAY_MERCHANT_ID2    => 'sometimes',
        Entity::GATEWAY_ACQUIRER        => 'sometimes|string',
        Entity::GATEWAY_TERMINAL_ID     => 'sometimes|alpha_num',
        Entity::EMI                     => 'sometimes|in:0,1',
        Entity::ENABLED                 => 'sometimes|in:0,1',
        Entity::NETWORK_CATEGORY        => 'sometimes|string|max:50',
        Entity::MC_MPAN                 => 'sometimes|string|size:16',
        Entity::VISA_MPAN               => 'sometimes|string|size:16',
        Entity::RUPAY_MPAN              => 'sometimes|string|size:16',
        Entity::STATUS                  => 'sometimes|string|custom',
        Entity::VPA                     => 'sometimes|string|max:255',
    );

    protected function validateStatus($attribute, $value)
    {
        if (Status::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid status '. $value);
        }
    }

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

    public function getByTypeAndMerchantIds($type, $merchantIds)
    {
        $terminalMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $terminalAllColumn = $this->dbColumn('*');

        // IF(terminals.merchant_id != '100000Razorpay', 1, 0) AS direct
        $queryDirectCol = 'IF(' . $terminalMerchantIdColumn . ' != "' . Account::SHARED_ACCOUNT . '", 1, 0) AS direct';

        return $this->newQuery()
                    ->select($terminalAllColumn, DB::raw($queryDirectCol))
                    ->type([$type])
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->enabled()
                    ->get();
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

    public function findByGatewayAndTerminalData(string $gateway, array $terminalData = [], bool $withTrashed = false)
    {
        $query =  $this->newQuery()
                       ->where(Entity::GATEWAY, '=', $gateway);

        foreach ($terminalData as $key => $value)
        {
            $query->where($key, $value);
        }

        if ($withTrashed === true)
        {
            $query->withTrashed();
        }

        return $query->first();
    }

    public function findByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();
    }

    public function getByParams(array $params)
    {
        $params = $this->unsetEmptyParams($params);

        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        return $query->get();
    }

    public function getTerminalsForMerchantAndSharedMerchant(Merchant\Entity $merchant)
    {
        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT];

        $cacheTag = Entity::getCacheTag($merchant->getId());

        $query = $this->newQuery();

        $this->addMerchantWhereCondition($query, $merchantIds);

        $query->remember($this->getCacheTtl())
              ->cachetags($cacheTag);

        return $query->get();
    }

    public function getEmandateTerminalsForMerchantAndSharedMerchant(
        Merchant\Entity $merchant, string $authType): PublicCollection
    {
        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT];

        //
        // Emandate terminals have type 6 (recurring 3ds + recurring non 3ds)
        // This is because we don't have different terminals for the first
        // auth transaction and then subsequent recurring transactions.
        //

        $query = $this->newQuery()
                      ->enabled()
                      ->where(Entity::EMANDATE, true)
                      ->where(Entity::TYPE, 6)
                      ->whereIn(Entity::GATEWAY, Payment\Gateway::getEmandateGatewaysForAuthType($authType));

        $this->addMerchantWhereCondition($query, $merchantIds);

        return $query->get();
    }

    public function getAllBankTransferTerminals(): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSFER, true)
                      ->withTrashed();

        return $query->get();
    }

    protected function addMerchantWhereCondition($query, array $merchantIds)
    {
        //
        // TODO: If a shared terminal has sub-merchants, this query would return
        // back the same terminal twice. Once as shared and second time as direct.
        // This will increase the number of terminals to filter and sort through
        // unnecessarily. We should be only taking the direct terminal. A unique
        // has to be done on this, ensuring that only the direct terminal is used!
        //

        $newQuery = clone $query;

        $query->whereIn(Entity::MERCHANT_ID, $merchantIds);

        $terminalMerchantIdColumn = $this->dbColumn(Entity::MERCHANT_ID);
        $terminalAllColumn = $this->dbColumn('*');

        // IF(terminals.merchant_id != '100000Razorpay', 1, 0) AS direct
        $queryDirectCol = 'IF(' . $terminalMerchantIdColumn . ' != "' . Account::SHARED_ACCOUNT . '", 1, 0) AS direct';

        $query->select($terminalAllColumn, DB::raw($queryDirectCol));

        $newQueryDirectCol = '1 AS direct';

        $unionQuery = $newQuery->select($terminalAllColumn, DB::raw($newQueryDirectCol))
                               ->join(Table::MERCHANT_TERMINAL, Entity::TERMINAL_ID, Entity::ID)
                               ->where(function ($q) use ($merchantIds)
                               {
                                    $q->whereIn(Table::MERCHANT_TERMINAL . '.' . Entity::MERCHANT_ID, $merchantIds);
                               }
                           );

        $query->union($unionQuery);
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
                      ->where(Entity::GATEWAY, '=', $gateway)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->first();
    }

    public function getIdsByMerchantIdsAndGateway($mids, $gateway)
    {
        $query = $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->enabled();

         $query->where(
            function ($query) use ($mids)
            {
                // Condition for the merchant id being directly in the terminal
                $query->whereIn(Entity::MERCHANT_ID, $mids);
            });

        return $query->pluck(Entity::ID)->all();
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

    public function findByGatewayMpan(string $mpan, string $gateway)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(function ($query) use ($mpan)
                    {
                        $query->where(Entity::VISA_MPAN, '=', $mpan)
                              ->orWhere(Entity::MC_MPAN, '=', $mpan)
                              ->orWhere(Entity::RUPAY_MPAN, '=', $mpan);
                    })
                    ->first();
    }

    public function deleteOrFail($entity)
    {
        $count = $this->repo->payment->getTotalUsedCountForTerminal(
                    $entity->getId());

        return $this->transaction(function() use ($entity, $count)
        {
            if ($count === 0)
            {
                $entity->forceDelete();

                return null;
            }
            else
            {
                $entity->deleteOrFail();

                return $entity;
            }
        });
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

    public function addMerchantToTerminal(Entity $terminal, Merchant\Entity $merchant)
    {
        $terminal->merchants()->attach($merchant);
    }

    public function removeMerchantFromTerminal(Entity $terminal, Merchant\Entity $merchant)
    {
        $terminal->merchants()->detach($merchant);
    }

    public function getByMerchantProviderAndMethod(string $provider, string $merchantId, string $method)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_ACQUIRER, '=', $provider)
                    ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
                    ->where($method, '=', 1)
                    ->enabled()
                    ->firstOrFail();
    }

    public function findByMerchantIdAndMethod(string $merchantId, string $method)
    {
        return $this->newQuery()
                    ->where($method, '=', 1)
                    ->whereIn(Entity::MERCHANT_ID, [$merchantId, Account::SHARED_ACCOUNT])
                    ->enabled()
                    ->get();
    }

    public function findManyEnabledByIds($ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->enabled()
                    ->get();
    }

    public function findByMerchantIdGatewayAndCurrency(string $merchantId, string $gateway, string $currency)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY, '=', $gateway)
                      ->where(Entity::CURRENCY, 'LIKE', '%'.$currency.'%')
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        return $query->first();
    }
}
