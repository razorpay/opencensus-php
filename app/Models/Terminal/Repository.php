<?php

namespace RZP\Models\Terminal;

use DB;
use Carbon\Carbon;
use RZP\Constants\Mode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Terminal;
use RZP\Models\Merchant\Account;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Base\QueryCache\CacheQueries;

class Repository extends Base\Repository
{
    use CacheQueries;

    protected $entity = 'terminal';

    protected $entityFetchParamRules = [
        Entity::GATEWAY                 => 'sometimes',
        Entity::CATEGORY                => 'sometimes|digits:4',
        Entity::ENABLED                 => 'sometimes',
        Entity::STATUS                  => 'sometimes',
    ];

    protected $appFetchParamRules = array(
        Entity::GATEWAY                 => 'sometimes',
        Entity::ORG_ID                  => 'sometimes|alpha_num|size:14',
        Entity::MERCHANT_ID             => 'sometimes|alpha_num|size:14',
        Entity::CARD                    => 'sometimes|boolean',
        Entity::NETBANKING              => 'sometimes|boolean',
        Entity::SHARED                  => 'sometimes|boolean',
        Entity::CATEGORY                => 'sometimes|digits:4',
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

    public function saveOrFail($entity, array $options = array())
    {
        $shouldSync = true;

        if (isset($options['shouldSync']) === true)
        {
            $shouldSync = $options['shouldSync'];

            unset($options['shouldSync']);
        }

        if ($shouldSync === false)
        {
            parent::saveOrFail($entity, $options);

        }
        else
        {
            $entity = $this->transaction(function () use (& $entity, $options, $shouldSync) {
                if ($shouldSync === true) {
                    $entity->setSyncStatus(SyncStatus::NOT_SYNCED);
                }

                // dont delete this line. this line is needed to generate id for a new terminal
                // id gets created on save
                parent::saveOrFail($entity, $options);

                $sync = $this->app['config']->get('applications.terminals_service.sync');

                if ($sync === true && $shouldSync === true)
                {
                    $entity = (new Terminal\Service)->migrateTerminalCreateOrUpdate($entity->getId());

                    $entity->setSyncStatus(SyncStatus::SYNC_SUCCESS);

                    parent::saveOrFail($entity, $options);
                }

                return $entity;

            });
        }

        return $entity;
    }

    public function fetchForPayment(Payment\Entity $payment)
    {
        $terminal = null;

        if ($payment->hasRelation('terminal'))
        {
            $terminal = $payment->terminal;
        }

        if (empty($terminal) === true)
        {
            $terminal = $this->getById($payment->getTerminalId());

            $payment->setRelation('terminal', $terminal);
        }

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

        $apiTerminals = $this->newQuery()
                    ->select($terminalAllColumn, DB::raw($queryDirectCol))
                    ->type([$type])
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->enabled()
                    ->get();

        try
        {
            $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

            $variantFlag = $this->app->razorx->getTreatment($merchantIds[0], "ROUTE_PROXY_TS",  $mode);

            $data = ["function" => "getByTypeAndMerchantIds", "merchant_ids" => $merchantIds, "type" => $type];

            if ($variantFlag === 'proxy')
            {
                $content = ["merchant_ids" => $merchantIds];

                $content["status"] = Status::ACTIVATED;

                $content["enabled"] = true;

                $content["fetch_where_submerchant"] = false;

                $content["api_type"] = $type;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
        }
        catch (\Exception $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

       return $apiTerminals;
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
        $terminal =  $this->newQuery()
                    ->withTrashed()
                    ->findOrFailPublic($id);

        $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

        $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_BY_ID",  $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getById", "terminal_id" => $id];

            try
            {
                $path = "v1/terminals/" . $id;

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
            catch (\Exception $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getByIdNonDeleted($id)
    {
        return $this->newQuery()
                    ->findOrFailPublic($id);
    }

    public function getByMerchantId($mid)
    {
        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->get();
    }

    public function getActivatedDirectSettlementTerminalsByMerchant(string $mId)
    {
        $query = $this->newQuery();

        $this->addMerchantWhereCondition($query, [$mId]);

        $query->where(Entity::STATUS, Status::ACTIVATED);

        $terminals = $query->get();

        return $terminals->filter(function ($terminal) {
            return (($terminal->isDirectSettlementWithoutRefund() === true) or ($terminal->isDirectSettlementWithRefund() === true));
        });
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

    public function findActivatedTerminalByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        return $this->newQuery()
            ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where(Entity::STATUS, '=', Terminal\Status::ACTIVATED)
            ->first();
    }

    public function findTerminalByGatewayMerchantIdAndGatewayTerminalId(string $gatewayMerchantId, string $gatewayTerminalId, string $gateway)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();
    }

    public function findEnabledTerminalByMpanAndGatewayMerchantId(string $gatewayMerchantId, string $gateway, string $mpan)
    {
        return $this->newQuery()
        ->where(Entity::GATEWAY, '=', $gateway)
        ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
        ->where(function ($query) use ($mpan)
        {
            $query->where(Entity::VISA_MPAN, '=', $mpan)
                  ->orWhere(Entity::MC_MPAN, '=', $mpan)
                  ->orWhere(Entity::RUPAY_MPAN, '=', $mpan);
        })
        ->enabled()
        ->first();
    }

    public function getByParams(array $params)
    {
        $query = $this->buildFetchByParamsQuery($params);

        return $query->get();
    }

    public function getNonFailedNonDeactivatedByParams(array $params)
    {
        $query = $this->buildFetchByParamsQuery($params);

        return $query->where(Entity::STATUS, '!=', Status::FAILED)
                     ->where(Entity::STATUS, '!=', Status::DEACTIVATED)
                     ->get();
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

    public function getTerminalForMerchantParentMerchantAndSharedMerchant(Merchant\Entity $merchant)
    {
        $merchantInheritanceMap = $merchant->merchantInheritanceMap;

        if (isset($merchantInheritanceMap) === false)
        {
            return $this->getTerminalsForMerchantAndSharedMerchant($merchant);
        }

        $parentMerchantId = $merchantInheritanceMap->parentMerchant->getId();

        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT, $parentMerchantId];

        $cacheTags = [Entity::getCacheTag($merchant->getId()), Entity::getCacheTag($parentMerchantId)];

        $query = $this->newQuery();

        $this->addMerchantWhereCondition($query, $merchantIds);

        $query->remember($this->getCacheTtl())
              ->cachetags($cacheTags);

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
                      ->whereIn(Entity::TYPE, [6, 32774])
                      ->whereIn(Entity::GATEWAY, Payment\Gateway::getEmandateGatewaysForAuthType($authType));

        $this->addMerchantWhereCondition($query, $merchantIds);

        return $query->get();
    }

    public function getAllBankTransferTerminals($gateway): PublicCollection
    {
        $query = $this->newQuery()
                      ->where(Entity::BANK_TRANSFER, true)
                      ->where(Entity::GATEWAY, $gateway)
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

        $terminal = $query->findOrFailPublic($tid);

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS",  $this->app['rzp.mode']);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getByIdAndMerchantId", "mid" => $mid, "tid" => $tid];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'merchant_ids' => [$mid],
                    'terminal_ids' => [$tid],
                    'deleted' =>true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminal2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response)->first();

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
            catch (\Exception $ex)
            {
                $data['message'] = $ex->getMessage();
                $data["function"] = "getByIdAndMerchantId";

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getByMerchantIdAndGateway($mid, $gateway)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY, '=', $gateway)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$mid]);

        $terminal = $query->first();

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS",  $this->app['rzp.mode']);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getByMerchantIdAndGateway", "mid" => $mid, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'gateway' => $gateway,
                    'merchant_ids' => [$mid],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminal2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response)->first();

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
            catch (\Exception $ex)
            {
                $data['message'] = $ex->getMessage();
                $data["function"] = "getByMerchantIdAndGateway";

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
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

    public function getRecurringTerminalsByMidAndGateway($mid, $gateway)
    {
        $query = $this->newQuery()
            ->where(Entity::GATEWAY, $gateway)
            ->type([Terminal\Type::RECURRING_3DS])
            ->enabled();

        $cacheTag = Entity::getCacheTag($mid);
        $query->remember($this->getCacheTtl())
            ->cachetags($cacheTag);

        $this->addMerchantWhereCondition($query, [$mid]);
        $terminal = $query->first();

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS",  $this->app['rzp.mode']);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getRecurringTerminalsByMidAndGateway", "mid" => $mid, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'gateway' => $gateway,
                    'merchant_ids' => [$mid],
                    'api_type' => Terminal\Type::RECURRING_3DS,
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminal2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response)->first();

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
            catch (\Exception $ex)
            {
                $data['message'] = $ex->getMessage();
                $data["function"] = "getRecurringTerminalsByMidAndGateway";

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getUpiRecurringTerminalsByMid($mid)
    {
        $query = $this->newQuery()
                      ->whereIn(Entity::GATEWAY, Payment\Gateway::$upiRecurringGateways)
                      ->type([Terminal\Type::RECURRING_3DS])
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$mid, Account::SHARED_ACCOUNT]);

        $terminal = $query->first();

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS",  $this->app['rzp.mode']);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getUpiRecurringTerminalsByMid", "mid" => $mid];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'gateways' => Payment\Gateway::$upiRecurringGateways,
                    'merchant_ids' => [$mid, Account::SHARED_ACCOUNT],
                    'api_type' => Terminal\Type::RECURRING_3DS,
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminal2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response)->first();

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
            }
            catch (\Exception $ex)
            {
                $data['message'] = $ex->getMessage();
                $data["function"] = "getRecurringTerminalsByMidAndGateway";

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
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

    public function getByTerminalIds(array $ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
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
            $sync = $this->app['config']->get('applications.terminals_service.sync');

            if ($sync === true)
            {
                (new Terminal\Service)->migrateTerminalDelete($entity->getId());

                $entity->setSyncStatus(SyncStatus::SYNC_SUCCESS);

                parent::saveOrFail($entity);
            }

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
        $this->repo->transaction(function () use ($terminal, $merchant) {
            $terminal->merchants()->attach($merchant);

            $sync = $this->app['config']->get('applications.terminals_service.sync');
            if ($sync === true)
            {
                (new Terminal\Service)->migrateTerminalAddMerchant($terminal, $merchant);
            }
        });
    }

    public function removeMerchantFromTerminal(Entity $terminal, Merchant\Entity $merchant)
    {
        $this->repo->transaction(function () use ($terminal, $merchant) {
            $terminal->merchants()->detach($merchant);
            $sync = $this->app['config']->get('applications.terminals_service.sync');
            if ($sync === true)
            {
                (new Terminal\Service)->migrateTerminalRemoveMerchant($terminal, $merchant);
            }
        });

    }

    public function getByMerchantProviderAndMethod(string $provider, string $merchantId, string $method)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_ACQUIRER, '=', $provider)
                      ->where($method, '=', 1)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        return $query->firstOrFail();
    }

    public function findByMerchantIdAndMethod(string $merchantId, string $method)
    {
        $query = $this->newQuery()
                      ->where($method, '=', 1)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        return $query->get();
    }

    public function findManyEnabledByIds($ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->enabled()
                    ->get();
    }

    public function fetchForSyncToTerminalsService(array $input)
    {
        $query = $this->newQuery()
                      ->where(Entity::SYNC_STATUS, '=', SyncStatus::getValueForSyncStatusString($input[Entity::SYNC_STATUS]));

        if (isset($input['gateway']) === true){
            $query = $query->where(Entity::GATEWAY, '=', $input['gateway']);
        }

        return $query->limit($input['count'])
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


    public function fetchByMerchantIdGatewayAndStatus(string $mid, string $gateway, array $status)
    {
        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(Entity::MERCHANT_ID, '=', $mid)
                    ->whereIn(Entity::STATUS, $status)
                    ->get();
    }

    public function findMerchantIdByGatewayMerchantID(string $gatewayMerchantId)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                      ->enabled();

        return $query->first();
    }

    public function fetchTerminalsForTokenization(int $count, array $terminalIds = [])
    {
        $gatewayHavingMpans = [Payment\Gateway::WORLDLINE, Payment\Gateway::HITACHI, Payment\Gateway::ISG];

        $query = $this->newQuery()
                        ->take($count)
                        ->whereIn(Entity::GATEWAY, $gatewayHavingMpans)
                        ->whereRaw('(LENGTH(mc_mpan) = 16 or LENGTH(visa_mpan) = 16 or LENGTH(rupay_mpan) = 16)');

        if ($terminalIds != [])
        {
            $query = $query->whereIn(Entity::ID, $terminalIds);
        }

        return $query->get();
    }

    protected function buildFetchByParamsQuery(array $params)
    {
        $params = $this->unsetEmptyParams($params);

        $query = $this->newQuery();

        foreach ($params as $key => $value)
        {
            $query = $query->where($key, '=', $value);
        }

        return $query;
    }
}
