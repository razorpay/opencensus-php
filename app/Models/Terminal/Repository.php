<?php

namespace RZP\Models\Terminal;

use DB;
use Carbon\Carbon;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
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
        Entity::PLAN_ID                 => 'sometimes|alpha_num|size:14',
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

        $shouldSaveOnApi = $this->shouldSaveEntityOnApi($entity);

        $client = $this->app['terminals_service'];

        if ($shouldSaveOnApi === false)
        {
            $res = $client->migrateTerminal($entity);

            return Terminal\Service::getEntityFromTerminalServiceResponse($res);
        }
        else if ($shouldSync === false)
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

            $variantFlag = $this->app->razorx->getTreatment($merchantIds[0], "ROUTE_PROXY_TS_2",  $mode);

            $data = ["function" => "getByTypeAndMerchantIds", "merchant_ids" => $merchantIds, "type" => $type];

            if ($variantFlag === 'on')
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content = ["merchant_ids" => $merchantIds];

                $content["status"] = Status::ACTIVATED;

                $content["enabled"] = true;

                $content["fetch_where_submerchant"] = false;

                $content["api_type"] = [$type];

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);


                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;

            }
        }
        catch (\Throwable $ex)
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


    public function getById($id, $withTrashed = true, $fromTerminalsService = true)
    {
        $query = $this->newQuery();

        if ($withTrashed === true)
        {
            $query->withTrashed();
        }

        $terminal = $query->findOrFailPublic($id);

        $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

        $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_BY_ID_2",  $mode);

        if ($variantFlag === 'on' and $fromTerminalsService === true)
        {
            $data = ["function" => "getById", "terminal_id" => $id, "with_trashed" => $withTrashed];

            try
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $path = "v1/terminals/" . $id ."?with_trashed=". ($withTrashed ? 'true' : 'false') ;

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminal2;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getTerminalsWithNullEnabledWallets($count)
    {
        $gateways = Payment\Gateway::$methodMap['wallet'];

        $terminals = $this->newQuery()
                          ->whereIn(Entity::GATEWAY, $gateways)
                          ->limit($count)
                          ->get();

        return $terminals;
    }

    public function findOrFail($id, $columns = array('*'))
    {
        $model = $this->find($id, $columns);

        if ( ! is_null($model))
        {
            return $model;
        }

        $data = array(
            'model' => 'terminal',
            'operation' => 'find',
            'attributes' => array('id' => $id, 'columns' => $columns));

        throw new Exception\DbQueryException($data);
    }

    public function findOrFailPublic($id, $columns = ['*'])
    {
        $model = $this->find($id, $columns);

        if (is_null($model) === false)
        {
            return $model;
        }

        $data = [
            'model' => 'terminal',
            'attributes' => $id,
            'operation' => 'find'
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function find($id, $columns = ['*'])
    {
        $terminal = parent::find($id, $columns);

        $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

        $variantFlag = $this->app->razorx->getTreatment($id, "ROUTE_PROXY_TS_FIND",  $mode);

        if ($variantFlag === 'terminals_find')
        {
            $data = ["function" => "find", "terminal_id" => $id];

            try
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $path = "v1/terminals/" . $id;

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminal2;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function findMany($ids, $columns = array('*'))
    {
        return $this->getByTerminalIds($ids);
    }

    public function fetch(array $params, string $merchantId = null, string $connectionType = null, $fromTerminalsService = true): PublicCollection
    {
        //In production all the terminals are fetched from the terminals service, a prod check included for unit testing cases
        if( in_array($this->app['env'], [Environment::PRODUCTION, Environment::AUTOMATION, Environment::BVT, Environment::BETA], true) === true ) {
            if ($merchantId != null)
            {
                $params["merchant_id"] = $merchantId;
            }

            $data = ["function" => "fetch", "params" => $params];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $path = "v1/admin/terminals/?";

            foreach ($params as $queryParam => $value)
            {
                $path .= $queryParam. '=' .$value. '&';
            }

            $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

            foreach ($response as $index => $value)
            {
                $response[$index]["id"] = str_replace("term_", "", $response[$index]["id"]);
            }

            $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

            return $tsTerminals;
        }
        else {

            $terminals = parent::fetch($params, $merchantId, $connectionType);
            return $terminals;
        }
    }

    public function getTerminalIdsByPlanIds($ids)
    {
        $path = "v1/plans/terminals?";

        foreach ($ids as $id)
        {
            $path .= 'plan_ids=' . $id . '&';
        }

        return $this->app['terminals_service']->proxyTerminalService('', "GET", $path);
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

        try
        {
            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($mId, "ROUTE_PROXY_TS_2", $mode);

            if ($variantFlag === 'on')
            {
                $data = ["function" => "getActivatedDirectSettlementTerminalsByMerchant", "merchant_id"=> $mId];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content["merchant_ids"] = [$mId];

                $content["status"] = "activated";

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                if (count($response) > 0)
                {
                    $terminals2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                    if (Terminal\Service::compareTerminalCollection($terminals, $terminals2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminals2->filter(function ($terminal) {
                        return (($terminal->isDirectSettlementWithoutRefund() === true) or ($terminal->isDirectSettlementWithRefund() === true));
                    });
                }
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

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
        $apiTerminals = $query->get();

        try
        {
            $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

            $randomId = (new Entity)->generateId();

            $variantFlag = $this->app->razorx->getTreatment($randomId, "ROUTE_PROXY_TS_5",  $mode);

            $data = ["function" => "findByGatewayAndTerminalData", "gateway"=> $gateway, "terminal_data" => $terminalData, "withTrashed" => $withTrashed];

            if ($variantFlag === 'proxy')
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content = Terminal\Service::getTerminalServiceRequestFromParam($terminalData);

                $content["gateway"] = $gateway;

                $content["deleted"] = $withTrashed;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals->first();
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $apiTerminals->first();
    }

    public function findByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        $terminal =  $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();

        try
        {
            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($gatewayMerchantId, "ROUTE_PROXY_TS_BY_IDENTIFIERS_2", $mode);

            if ($variantFlag === 'proxy')
            {
                $data = ["function" => "findByGatewayMerchantId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content["fetch_where_submerchant"] = false;

                $identifiers = ["gateway_merchant_id" => $gatewayMerchantId];

                $content["identifiers"] = $identifiers;

                $content["gateway"] = $gateway;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if ((empty($terminal) == true) or (empty($terminal2) == true))
                    {
                        // return from here only when in sync
                        $data["isTerminalNull"] = empty($terminal2);
                        $data["isTsTerminalNull"] = empty($tsTerminal);

                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }
                    elseif (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }
                    return $terminal2;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $terminal;
    }

    public function findActivatedTerminalByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        $terminal =  $this->newQuery()
            ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where(Entity::STATUS, '=', Terminal\Status::ACTIVATED)
            ->first();

        try
        {
            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($gatewayMerchantId, "ROUTE_PROXY_TS_BY_IDENTIFIERS_2", $mode);

            if ($variantFlag === 'proxy')
            {
                $data = ["function" => "findActivatedTerminalByGatewayMerchantId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content["fetch_where_submerchant"] = false;

                $identifiers = ["gateway_merchant_id" => $gatewayMerchantId];

                $content["identifiers"] = $identifiers;

                $content["gateway"] = $gateway;

                $content["status"] = Status::ACTIVATED;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);

                        return $terminal2;
                    }
                }
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $terminal;
    }

    public function findTerminalByGatewayMerchantIdAndGatewayTerminalId(string $gatewayMerchantId, string $gatewayTerminalId, string $gateway)
    {
        $terminal =  $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();

        try
        {
            $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

            $variantFlag = $this->app->razorx->getTreatment($gatewayMerchantId, "ROUTE_PROXY_TS_BY_IDENTIFIERS_2", $mode);

            if ($variantFlag === 'proxy')
            {
                $data = ["function" => "findTerminalByGatewayMerchantIdAndGatewayTerminalId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway_terminal_id"=> $gatewayTerminalId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $identifiers["gateway_merchant_id"] = $gatewayMerchantId;

                $identifiers["gateway_terminal_id"] = $gatewayTerminalId;

                $content["identifiers"] = $identifiers;

                $content["gateway"] = $gateway;

                $content["status"] = Status::ACTIVATED;

                $content["fetch_where_submerchant"] = false;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $terminal;
    }

    public function findEnabledTerminalByMpanAndGatewayMerchantId(string $gatewayMerchantId, string $gateway, string $mpan)
    {
        $terminal = $this->newQuery()
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

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gatewayMerchantId, "ROUTE_PROXY_TS_4", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = [
                "function" => "findEnabledTerminalByMpanAndGatewayMerchantId",
                "gateway" => $gateway,
                "gateway_merchant_id" => $gatewayMerchantId,
                "mpan" => $mpan
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/mpan/".$mpan."/terminals";

                $input = [
                    'gateway' => $gateway,
                    'identifiers' => [
                        'gateway_merchant_id' => $gatewayMerchantId
                    ],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getByParams(array $params, bool $fetchWhereSubmerchant = false)
    {
        $query = $this->buildFetchByParamsQuery($params);

        $terminals = $query->get();

        try
        {
            $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

            $randomId = (new Entity)->generateId();

            $variantFlag = $this->app->razorx->getTreatment($randomId, "ROUTE_PROXY_TS_6",  $mode);

            $data = ["function" => "getByParams", "params" => $params];

            if ($variantFlag === 'proxy')
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content = Terminal\Service::getTerminalServiceRequestFromParam($params);

                $path = "v1/admin/terminals_with_secrets";

                // edge case handling for wallet_paypal
                if (($content["gateway"] === Payment\Gateway::WALLET_PAYPAL) and (isset($content["status"]) === false))
                {
                    $content["status"] = Status::ACTIVATED;
                }

                if ($fetchWhereSubmerchant === true)
                {
                    $content["fetch_where_submerchant"] = true;
                }

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($terminals, $tsTerminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $tsTerminals;
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $terminals;
    }

    public function getNonFailedNonDeactivatedByParams(array $params, $proxyTs = true)
    {

        $query = $this->buildFetchByParamsQuery($params);

        $terminals = $query->where(Entity::STATUS, '!=', Status::FAILED)
                     ->where(Entity::STATUS, '!=', Status::DEACTIVATED)
                     ->get();

        if ($proxyTs === true)
        {
            try
            {
                $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

                $randomId = (new Entity)->generateId();

                $variantFlag = $this->app->razorx->getTreatment($randomId, "ROUTE_PROXY_TS_4", $mode);

                $data = ["function" => "getNonFailedNonDeactivatedByParams", "params" => $params];

                if ($variantFlag === 'proxy')
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                    $content = Terminal\Service::getTerminalServiceRequestFromParam($params);

                    $content['statuses'] = [Status::ACTIVATED, Status::CREATED, Status::PENDING];

                    $path = "v1/merchants/terminals";

                    $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                    $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                    if (Terminal\Service::compareTerminalCollection($terminals, $tsTerminals) === false) {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $tsTerminals;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminals;
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

        $gateways = Payment\Gateway::getEmandateGatewaysForAuthType($authType);

        //
        // Emandate terminals have type 6 (recurring 3ds + recurring non 3ds)
        // This is because we don't have different terminals for the first
        // auth transaction and then subsequent recurring transactions.
        //


        $query = $this->newQuery()
                      ->enabled()
                      ->where(Entity::EMANDATE, true)
                      ->whereIn(Entity::TYPE, [6, 32774])
                      ->whereIn(Entity::GATEWAY, $gateways);

        $this->addMerchantWhereCondition($query, $merchantIds);

        $terminals = $query->get();

        try
        {
            $mode = $this->app['rzp.mode'] ??  Mode::LIVE ;

            $variantFlag = $this->app->razorx->getTreatment($merchant->getId(), "ROUTE_PROXY_TS_5",  $mode);

            if ($variantFlag === 'proxy')
            {
                $data = ["function" => "getEmandateTerminalsForMerchantAndSharedMerchant", "gateways" => $gateways];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $content['merchant_ids'] = $merchantIds;

                $content['gateways'] = $gateways;

                $content['enabled'] = true;

                $content['api_type'] = [Type::RECURRING_3DS, Type::RECURRING_NON_3DS];

                $content['methods'] = [Entity::EMANDATE];

                $path = "v1/merchants/terminals";

                if (count($gateways) > 0)
                {
                    $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);
                }
                else
                {
                    $response = [];
                }

                $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($terminals, $tsTerminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $tsTerminals;
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
        }

        return $terminals;
    }

    public function getHitachiTerminalsForCurrencyOrStatusUpdate($limit): PublicCollection
    {
        $currencyLength = strlen(json_encode(Currency::SUPPORTED_CURRENCIES));

        return $this->newQuery()
                     ->where(Entity::GATEWAY, 'hitachi')
                     ->where(Entity::STATUS, Status::ACTIVATED)
                     ->whereRaw('LENGTH(currency) < ?', [$currencyLength])
                     ->limit($limit)
                     ->get();
    }

    public function getAllBankTransferTerminals($gateway, $merchantIds = []): PublicCollection
    {
        $query = $this->newQuery()
                      ->select([Entity::ID, Entity::GATEWAY_MERCHANT_ID, Entity::GATEWAY_MERCHANT_ID2, Entity::MERCHANT_ID, Entity::ACCOUNT_TYPE])
                      ->where(Entity::BANK_TRANSFER, true)
                      ->where(Entity::GATEWAY, $gateway);

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
        }

        $apiTerminals = $query->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gateway, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getAllBankTransferTerminals", "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'gateway' => $gateway,
                    'methods' => [Entity::BANK_TRANSFER],
                    'fetch_where_submerchant' => false,
                ];

                if (empty($merchantIds) === false)
                {
                    $input['merchant_ids'] = $merchantIds;
                }

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                $compareMethods = ["getId", "getMerchantId", "getGatewayMerchantId", "getGatewayMerchantId2"];

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals, $compareMethods) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
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
        $terminal = $this->newQuery()
                    ->withTrashed()
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereNotNull(Entity::GATEWAY_RECON_PASSWORD)
                    ->first();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gatewayTerminalId, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = [
                "function" => "getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull",
                "gateway" => $gateway,
                "gateway_terminal_id" => $gatewayTerminalId
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/recon_password_not_null/terminals";

                $input = [
                    'gateway' => $gateway,
                    'identifiers' => [
                        'gateway_terminal_id' => $gatewayTerminalId
                    ],
                    'deleted' => true
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function getByIdAndMerchantId($mid, $tid)
    {
        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        $terminal = $query->findOrFailPublic($tid);

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_4", $mode);

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

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
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

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_4", $mode);

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

                // edge case handling for wallet_paypal
                if ($gateway=== Payment\Gateway::WALLET_PAYPAL)
                {
                    $content["status"] = Status::ACTIVATED;
                }

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0) {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
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

        $apiTerminalIds = $query->pluck(Entity::ID)->all();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gateway, "ROUTE_PROXY_TS_2", $mode);

        if ($variantFlag === 'on')
        {
            $data = ["function" => "getIdsByMerchantIdsAndGateway", "mids" => $mids, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'gateway' => $gateway,
                    'merchant_ids' => $mids,
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                $terminalIds = $tsTerminals->pluck(Entity::ID)->all();


                if ((sizeof($terminalIds) === sizeof($apiTerminalIds)) and (count($apiTerminalIds, $terminalIds) > 0)
                    and (count($terminalIds, $apiTerminalIds) > 0))
                {
                    $data["api_temrinal_ids"] = $apiTerminalIds;
                    $data["terminal_ids"] = $terminalIds;
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminalIds;

            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminalIds;
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

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_5", $mode);

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
                    'api_type' => [Terminal\Type::RECURRING_3DS],
                    'enabled' => true,
                    'fetch_where_submerchant' => true,

                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else{
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
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

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_5", $mode);

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
                    'api_type' => [Terminal\Type::RECURRING_3DS],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
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

    public function getByTerminalIds(array $ids, bool $proxy = true)
    {
        $apiTerminals = $this->newQuery()
            ->whereIn(Entity::ID, $ids)
            ->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($ids[0], "ROUTE_PROXY_TS_6", $mode);

        if (($variantFlag === 'proxy') and ($proxy === true))
        {
            $data = ["function" => "getByTerminalIds", "ids" => $ids];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/admin/terminals_with_secrets";

                $input = [
                    'terminal_ids' => $ids,
                    'fetch_where_submerchant' => false,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
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
        $apiTerminals = $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->where(Entity::MERCHANT_ID, '!=', Account::SHARED_ACCOUNT)
                    ->enabled()
                    ->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gateway, "ROUTE_PROXY_TS_4", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = [
                "function" => "getDirectTerminalsForGateway",
                "gateway" => $gateway,
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/direct_terminals";

                $input = [
                    'gateway' => $gateway,
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
    }

    public function findByGatewayMpan(string $mpan, string $gateway)
    {
        $terminal = $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(function ($query) use ($mpan)
                    {
                        $query->where(Entity::VISA_MPAN, '=', $mpan)
                              ->orWhere(Entity::MC_MPAN, '=', $mpan)
                              ->orWhere(Entity::RUPAY_MPAN, '=', $mpan);
                    })
                    ->first();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($gateway, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "findByGatewayMpan", "gateway" => $gateway, "mpan" => $mpan];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/mpan/".$mpan."/terminals";

                $input = [
                    'gateway' => $gateway,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function deleteOrFail($entity)
    {
        return $this->transaction(function() use ($entity)
        {
            $sync = $this->app['config']->get('applications.terminals_service.sync');

            if ($sync === true)
            {
                (new Terminal\Service)->migrateTerminalDelete($entity->getId());

                $entity->setSyncStatus(SyncStatus::SYNC_SUCCESS);

                parent::saveOrFail($entity);
            }

            $entity->deleteOrFail();

            return $entity;
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

        $terminal = $query->firstOrFail();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "getByMerchantProviderAndMethod", "mid" => $merchantId, "gateway_acquirer" => $provider];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    Entity::GATEWAY_ACQUIRER => $provider,
                    'merchant_ids' => [$merchantId, Account::SHARED_ACCOUNT],
                    'methods' => [$method],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                if (count($response) > 0)
                {
                    $terminal2 = Terminal\Service::getEntityFromTerminalServiceResponse($response[0]);

                    if (Terminal\Service::compareTerminalEntity($terminal, $terminal2) === false)
                    {
                        $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                    }

                    return $terminal2;
                }
                else
                {
                    return null;
                }
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $terminal;
    }

    public function findByMerchantIdAndMethod(string $merchantId, string $method)
    {
        $query = $this->newQuery()
                      ->where($method, '=', 1)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        $apiTerminals = $query->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "findByMerchantIdAndMethod", "mid" => $merchantId, "method" => $method];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'merchant_ids' => [$merchantId, Account::SHARED_ACCOUNT],
                    'methods' => [$method],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
    }

    public function findManyEnabledByIds($ids)
    {
        $apiTerminals = $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->enabled()
                    ->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($ids[0], "ROUTE_PROXY_TS_3", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "findManyEnabledByIds", "ids" => $ids];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'terminal_ids' => $ids,
                    'enabled' => true,
                    'fetch_where_submerchant' => false,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }
                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
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

        $apiTerminal = $query->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($merchantId, "ROUTE_PROXY_TS_3", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "findByMerchantIdGatewayAndCurrency", "mid" => $merchantId, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'merchant_ids' => [$merchantId, Account::SHARED_ACCOUNT],
                    'gateway' => $gateway,
                    'currency' => [$currency],
                    'enabled' => true,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $tsTerminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminal, $tsTerminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $tsTerminals->first();
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminal->first();
    }


    public function fetchByMerchantIdGatewayAndStatus(string $mid, string $gateway, array $status)
    {
        $apiTerminals = $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(Entity::MERCHANT_ID, '=', $mid)
                    ->whereIn(Entity::STATUS, $status)
                    ->get();

        $mode = $this->app['rzp.mode'] ?? Mode::LIVE;

        $variantFlag = $this->app->razorx->getTreatment($mid, "ROUTE_PROXY_TS_5", $mode);

        if ($variantFlag === 'proxy')
        {
            $data = ["function" => "fetchByMerchantIdGatewayAndStatus", "mid" => $mid, "gateway" => $gateway, "status" => $status];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            try
            {
                $path = "v1/merchants/terminals";

                $input = [
                    'merchant_ids' => [$mid],
                    'gateway' => $gateway,
                    'status' => $status,
                    'fetch_where_submerchant' => false,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                if (Terminal\Service::compareTerminalCollection($apiTerminals, $terminals) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_TERMINAL_MISMATCH_FUNCTION, $data);
                }

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);
            }
        }

        return $apiTerminals;
    }

    public function findMerchantIdByGatewayMerchantID(string $gatewayMerchantId)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                      ->enabled();

        return $query->first();
    }

    public function findMerchantIdByGatewayMerchantIDAll(string $gatewayMerchantId)
    {
        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId);

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

    private function shouldSaveEntityOnApi(Entity $entity) : bool
    {
        $gateway = $entity->getGateway();

        if (in_array($gateway, Payment\Gateway::TOKENISATION_GATEWAYS) === true)
        {
            return false;
        }

        return true;
    }
}
