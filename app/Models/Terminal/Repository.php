<?php

namespace RZP\Models\Terminal;

use DB;
use App;
use Carbon\Carbon;
use Razorpay\Spine\Exception\DbQueryException;
use RZP\Base\Common;
use RZP\Constants\Environment;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Currency\Currency;
use RZP\Models\Payment;
use RZP\Models\Payment\Gateway;
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

    public function fetchRouteName()
    {
        $app = App::getFacadeRoot();

        $ba = $app['basicauth'];

        $routeName =  $app['request.ctx']->getRoute();

        return $routeName;
    }

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
            $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'saveOrFail', 'route_name' => $this->fetchRouteName()]);

            parent::saveOrFail($entity, $options);
        }
        else
        {
            $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'saveOrFail', 'route_name' => $this->fetchRouteName()]);

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
                    $entity = (new Terminal\Service)->migrateTerminalCreateOrUpdate($entity->getId(), $options);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv()) {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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

            return $apiTerminals;

        }

        try
        {
            $data = ["function" => "getByTypeAndMerchantIds", "merchant_ids" => $merchantIds, "type" => $type];

            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

                $content = ["merchant_ids" => $merchantIds];

                $content["status"] = Status::ACTIVATED;

                $content["enabled"] = true;

                $content["fetch_where_submerchant"] = false;

                $content["api_type"] = [$type];

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                return $terminals;

            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

            if(!$this->isTestEnv()) {
                throw $ex;
            }
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


    public function getById($id, $withTrashed = true, $fromTerminalsService = true)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if (($this->app->runningUnitTests() === false) and $fromTerminalsService === true and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getById", "terminal_id" => $id, "with_trashed" => $withTrashed];

            try
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

                $path = "v1/terminals/" . $id ."?with_trashed=". ($withTrashed ? 'true' : 'false') ;

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                $terminalFromTs = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                return $terminalFromTs;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

        if($this->isTestEnv() or $fromTerminalsService === false)
        {

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery();

        if ($withTrashed === true)
        {
            $query->withTrashed();
        }

        return $query->findOrFailPublic($id);

        }
    }

    public function getTerminalsWithNullEnabledWallets($count)
    {
        $gateways = Payment\Gateway::$methodMap['wallet'];

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminals = $this->newQuery()
                          ->whereIn(Entity::GATEWAY, $gateways)
                          ->limit($count)
                          ->get();

        return $terminals;
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $model = $this->find($id, $columns, $connectionType);

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

    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null)
    {

        $model = $this->find($id, $columns, $connectionType);

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

    public function find($id, $columns = ['*'], string $connectionType = null)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "find", "terminal_id" => $id];

            try
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

                $path = "v1/terminals/" . $id;

                $response = $this->app['terminals_service']->proxyTerminalService('', "GET", $path);

                $terminalEntityByTS = Terminal\Service::getEntityFromTerminalServiceResponse($response);

                return $terminalEntityByTS;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();

                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

        if($this->isTestEnv())
        {

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);


        /*
         * For Fpx downtime cron, we need to pass the terminal in term_<terminal_id> format. In test and live mode in production,
         * terminal data is being fetched from terminal service, hence this format works for that. But in test cases,
         * Data is being fetched from api db and it is not able to fetch the data from db with the above format. Hence we
         * need to strip this id before making a db call.
         */
        if ( strlen($id) === 19 &&
            (strpos($id, "_") !== false))
        {
            Entity::verifyIdAndStripSign($id);
        }

        return parent::find($id, $columns, $connectionType);

        }
    }

    public function findMany($ids, $columns = array('*'))
    {
        return $this->getByTerminalIds($ids);
    }

    public function fetch(array $params, string $merchantId = null, string $connectionType = null, $fromTerminalsService = true): PublicCollection
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        //In production all the terminals are fetched from the terminals service, a prod check included for unit testing cases
        if( in_array($this->app['env'], [Environment::PRODUCTION, Environment::AUTOMATION, Environment::BVT, Environment::BETA], true) === true ) {
            if ($merchantId != null)
            {
                $params["merchant_id"] = $merchantId;
            }

            $data = ["function" => "fetch", "params" => $params];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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
            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->get();
    }

    public function getEnabledTerminalsByMerchantId($mid)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
            ->enabled();

        $this->addMerchantWhereCondition($query, [$mid]);

        return $query->get();
    }

    public function getActivatedDirectSettlementTerminalsByMerchant(string $mId)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        try
        {
            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $data = ["function" => "getActivatedDirectSettlementTerminalsByMerchant", "merchant_id"=> $mId];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

                $content["merchant_ids"] = [$mId];

                $content["status"] = "activated";

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                if (count($response) > 0)
                {
                    $terminals2 = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

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

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
        }

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery();

        $this->addMerchantWhereCondition($query, [$mId]);

        $query->where(Entity::STATUS, Status::ACTIVATED);

        $terminals = $query->get();

        return $terminals->filter(function ($terminal) {
            return (($terminal->isDirectSettlementWithoutRefund() === true) or ($terminal->isDirectSettlementWithRefund() === true));
        });
    }

    public function findByGatewayAndTerminalData(string $gateway, array $terminalData = [], bool $withTrashed = false, $mode = null)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        try
        {
            $data = ["function" => "findByGatewayAndTerminalData", "gateway"=> $gateway, "terminal_data" => $terminalData, "withTrashed" => $withTrashed];

            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

                $content = Terminal\Service::getTerminalServiceRequestFromParam($terminalData);

                $content["gateway"] = $gateway;

                $content["deleted"] = $withTrashed;

                $path = "v1/merchants/terminals";

                $response = $this->app['terminals_service']->proxyTerminalService($content, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                return $terminals->first();
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
        }

        $query =  $this->newQueryWithConnection($this->getSlaveConnection($mode))
            ->where(Entity::GATEWAY, '=', $gateway);

        foreach ($terminalData as $key => $value)
        {
            $query->where($key, $value);
        }

        if ($withTrashed === true)
        {
            $query->withTrashed();
        }

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $apiTerminals = $query->get();

        return $apiTerminals->first();
    }

    public function findByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminal =  $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();

        try
        {
            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $data = ["function" => "findByGatewayMerchantId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
        }

        return $terminal;
    }

    public function findActivatedTerminalByGatewayMerchantId(string $gatewayMerchantId, string $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminal =  $this->newQuery()
            ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where(Entity::STATUS, '=', Terminal\Status::ACTIVATED)
            ->first();

            return $terminal;

        }

        try
        {
            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $data = ["function" => "findActivatedTerminalByGatewayMerchantId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                        return $terminal2;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

            if(!$this->isTestEnv())
            {
                throw $ex;
            }
        }

    }

    public function findTerminalByGatewayMerchantIdAndGatewayTerminalId(string $gatewayMerchantId, string $gatewayTerminalId, string $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminal =  $this->newQuery()
                    ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->first();

        try
        {
            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $data = ["function" => "findTerminalByGatewayMerchantIdAndGatewayTerminalId", "gateway_merchant_id"=> $gatewayMerchantId, "gateway_terminal_id"=> $gatewayTerminalId, "gateway"=> $gateway];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
        }

        return $terminal;
    }

    public function findEnabledTerminalByMpanAndGatewayMerchantId(string $gatewayMerchantId, string $gateway, string $mpan)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = [
                "function" => "findEnabledTerminalByMpanAndGatewayMerchantId",
                "gateway" => $gateway,
                "gateway_merchant_id" => $gatewayMerchantId,
                "mpan" => $mpan
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $terminal;
    }

    public function getByParams(array $params, bool $fetchWhereSubmerchant = false)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->buildFetchByParamsQuery($params);

        $terminals = $query->get();

            return $terminals;
        }

        try
        {
            $data = ["function" => "getByParams", "params" => $params];

            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                return $tsTerminals;
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

            if(!$this->isTestEnv())
            {
                throw $ex;
            }
        }

    }

    public function getNonFailedNonDeactivatedByParams(array $params, $proxyTs = true)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->buildFetchByParamsQuery($params);

        $terminals = $query->where(Entity::STATUS, '!=', Status::FAILED)
                     ->where(Entity::STATUS, '!=', Status::DEACTIVATED)
                     ->get();

        if ($proxyTs === true)
        {
            try
            {
                $data = ["function" => "getNonFailedNonDeactivatedByParams", "params" => $params];

                if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
                {
                    $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                    $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $terminals;
    }

    public function getTerminalsForMerchantAndSharedMerchant(Merchant\Entity $merchant)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        return $query->get();
    }

    public function getEmandateTerminalsForMerchantAndSharedMerchant(
        Merchant\Entity $merchant, string $authType): PublicCollection
    {
        $merchantIds = [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT];

        $gateways = Payment\Gateway::getEmandateGatewaysForAuthType($authType);

        $terminals = new PublicCollection();

        //
        // Emandate terminals have type 6 (recurring 3ds + recurring non 3ds)
        // This is because we don't have different terminals for the first
        // auth transaction and then subsequent recurring transactions.
        //
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->enabled()
                      ->where(Entity::EMANDATE, true)
                      ->whereIn(Entity::TYPE, [6, 32774])
                      ->whereIn(Entity::GATEWAY, $gateways);

        $this->addMerchantWhereCondition($query, $merchantIds);

        $terminals = $query->get();

            return $terminals;

        }

        try
        {
            if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
            {
                $data = ["function" => "getEmandateTerminalsForMerchantAndSharedMerchant", "gateways" => $gateways];

                $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                return $terminals;
            }
        }
        catch (\Throwable $ex)
        {
            $data['message'] = $ex->getMessage();

            $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

            if(!$this->isTestEnv())
            {
                throw $ex;
            }
        }

        return $terminals;
    }

    public function getHitachiTerminalsForCurrencyOrStatusUpdate($limit): PublicCollection
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->select([Entity::ID, Entity::GATEWAY_MERCHANT_ID, Entity::GATEWAY, Entity::GATEWAY_MERCHANT_ID2, Entity::MERCHANT_ID, Entity::ACCOUNT_TYPE])
                      ->where(Entity::BANK_TRANSFER, true)
                      ->where(Entity::GATEWAY, $gateway);

        if (empty($merchantIds) === false)
        {
            $query->whereIn(Entity::MERCHANT_ID, $merchantIds);
        }

        $apiTerminals = $query->get();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getAllBankTransferTerminals", "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminal = $this->newQuery()
                    ->withTrashed()
                    ->where(Entity::GATEWAY_TERMINAL_ID, '=', $gatewayTerminalId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->whereNotNull(Entity::GATEWAY_RECON_PASSWORD)
                    ->first();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = [
                "function" => "getByGatewayTerminalIdAndGatewayAndReconPasswordNotNull",
                "gateway" => $gateway,
                "gateway_terminal_id" => $gatewayTerminalId
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $terminal;
    }

    public function getByIdAndMerchantId($mid, $tid)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->withTrashed();

        $this->addMerchantWhereCondition($query, [$mid]);

        $terminal = $query->findOrFailPublic($tid);

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getByIdAndMerchantId", "mid" => $mid, "tid" => $tid];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $terminal;
    }

    public function getByMerchantIdAndGateway($mid, $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where(Entity::GATEWAY, '=', $gateway)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$mid]);

        $terminal = $query->first();

            return $terminal;

        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getByMerchantIdAndGateway", "mid" => $mid, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function getIdsByMerchantIdsAndGateway($mids, $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getIdsByMerchantIdsAndGateway", "mids" => $mids, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $apiTerminalIds;
    }

    public function getRecurringTerminalsByMidAndGateway($mid, $gateway)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
            ->where(Entity::GATEWAY, $gateway)
            ->type([Terminal\Type::RECURRING_3DS])
            ->enabled();

        $cacheTag = Entity::getCacheTag($mid);
        $query->remember($this->getCacheTtl())
            ->cachetags($cacheTag);

        $this->addMerchantWhereCondition($query, [$mid]);
        $terminal = $query->first();

            return $terminal;

        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getRecurringTerminalsByMidAndGateway", "mid" => $mid, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function getUpiRecurringTerminalsByMid($mid)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->whereIn(Entity::GATEWAY, Payment\Gateway::$upiRecurringGateways)
                      ->type([Terminal\Type::RECURRING_3DS])
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$mid, Account::SHARED_ACCOUNT]);

        $terminal = $query->first();

        return $terminal;

        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getUpiRecurringTerminalsByMid", "mid" => $mid];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function getSharedTerminalForGateway($gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->enabled()
                    ->get();
    }

    public function getSharedTerminalForGatewayWithCategory($gateway, $category)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        return $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->shared()
                    ->where(Entity::CATEGORY, '=', $category)
                    ->enabled()
                    ->first();
    }

    public function getEmiTerminal($mId, $gateway, $duration)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        return $this->newQuery()
                    ->merchantId(Merchant\Account::SHARED_ACCOUNT)
                    ->enabled()
                    ->get();
    }

    public function getAllSharedTerminals()
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $map = Terminal\Shared::getSharedTerminalMapping();

        $sharedTerminalIds = array_keys($map);

        return $this->newQuery()
                    ->whereIn(Entity::ID, $sharedTerminalIds)
                    ->enabled()
                    ->get();
    }

    public function getByTerminalIds(array $ids, bool $proxy = true)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $apiTerminals = $this->newQuery()
            ->whereIn(Entity::ID, $ids)
            ->get();

            return $apiTerminals;

        }

        if (($this->app->runningUnitTests() === false) and ($proxy === true) and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getByTerminalIds", "ids" => $ids];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

            try
            {
                $path = "v1/admin/terminals_with_secrets";

                $input = [
                    'terminal_ids' => $ids,
                    'fetch_where_submerchant' => false,
                ];

                $response = $this->app['terminals_service']->proxyTerminalService($input, "POST", $path);

                $terminals = Terminal\Service::getEntityCollectionFromTerminalServiceResponse($response);

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function mockGetByTerminalIds(array $ids)
    {
        $apiTerminals = $this->newQuery()
            ->whereIn(Entity::ID, $ids)
            ->get();

        return $apiTerminals;
    }

    public function getTpvTerminalIdsForGateway($gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $tpvCategories = Category::getTPVCategories();

        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->whereIn(Entity::NETWORK_CATEGORY, $tpvCategories)
                    ->enabled()
                    ->get([Entity::ID]);
    }

    public function getTerminalIdsForGateway($gateway, $exclude = [])
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        return $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->whereNotIn(Entity::ID, $exclude)
                    ->enabled()
                    ->get([Entity::ID]);
    }

    public function getDirectTerminalsForGateway(string $gateway): PublicCollection
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $apiTerminals = $this->newQuery()
                    ->where(Entity::GATEWAY, $gateway)
                    ->where(Entity::MERCHANT_ID, '!=', Account::SHARED_ACCOUNT)
                    ->enabled()
                    ->get();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = [
                "function" => "getDirectTerminalsForGateway",
                "gateway" => $gateway,
            ];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $apiTerminals;
    }

    public function findByGatewayMpan(string $mpan, string $gateway)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $terminal = $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(function ($query) use ($mpan)
                    {
                        $query->where(Entity::VISA_MPAN, '=', $mpan)
                              ->orWhere(Entity::MC_MPAN, '=', $mpan)
                              ->orWhere(Entity::RUPAY_MPAN, '=', $mpan);
                    })
                    ->first();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "findByGatewayMpan", "gateway" => $gateway, "mpan" => $mpan];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
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

                try{
                    $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'deleteOrFail', 'route_name' => $this->fetchRouteName()]);

                    parent::saveOrFail($entity);
                }catch (DbQueryException $e){
                    $this->trace->traceException($e, Trace::ERROR, TraceCode::DB_QUERY_EXCEPTION);
                    //We only need to delete terminals on API service if its present on both TS and API service
                    if(!$entity->isTerminalOnlyOnTerminalsService()){
                        throw $e;
                    }
                }
            }
            $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'deleteOrFail', 'route_name' => $this->fetchRouteName()]);

            $entity->deleteOrFail();

            return $entity;
        });
    }

    public function restoreOrFail($terminal)
    {
        $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'restoreOrFail', 'route_name' => $this->fetchRouteName()]);

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
            $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'addMerchantToTerminal', 'route_name' => $this->fetchRouteName()]);

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
            $this->trace->info(TraceCode::TERMINALS_REPO_WRITE_CALL_RECEIVED, ['method' => 'removeMerchantFromTerminal', 'route_name' => $this->fetchRouteName()]);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_ACQUIRER, '=', $provider)
                      ->where($method, '=', 1)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        $terminal = $query->firstOrFail();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "getByMerchantProviderAndMethod", "mid" => $merchantId, "gateway_acquirer" => $provider];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $terminal;
    }

    public function findByMerchantIdAndMethod(string $merchantId, string $method)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where($method, '=', 1)
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        $apiTerminals = $query->get();

            return $apiTerminals;

        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "findByMerchantIdAndMethod", "mid" => $merchantId, "method" => $method];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if($this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function findManyEnabledByIds($ids)
    {

        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        if($this->isTestEnv())
        {

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $apiTerminals = $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->enabled()
                    ->get();

            return $apiTerminals;

        }

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "findManyEnabledByIds", "ids" => $ids];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                return $terminals;
            }
            catch (\Throwable $ex)
            {
                $data['message'] = $ex->getMessage();
                $this->trace->traceException($ex, Trace::ERROR, TraceCode::TERMINALS_SERVICE_PROXY_CALL_ERROR, $data);

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);

                if(!$this->isTestEnv())
                {
                    throw $ex;
                }
            }
        }

    }

    public function fetchForSyncToTerminalsService(array $input)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where(Entity::GATEWAY, '=', $gateway)
                      ->where(Entity::CURRENCY, 'LIKE', '%'.$currency.'%')
                      ->enabled();

        $this->addMerchantWhereCondition($query, [$merchantId, Account::SHARED_ACCOUNT]);

        $apiTerminal = $query->get();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "findByMerchantIdGatewayAndCurrency", "mid" => $merchantId, "gateway" => $gateway];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $apiTerminal->first();
    }


    public function fetchByMerchantIdGatewayAndStatus(string $mid, string $gateway, array $status)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $apiTerminals = $this->newQuery()
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where(Entity::MERCHANT_ID, '=', $mid)
                    ->whereIn(Entity::STATUS, $status)
                    ->get();

        if ($this->app->runningUnitTests() === false and Environment::isEnvironmentQA($this->app['env']) === false)
        {
            $data = ["function" => "fetchByMerchantIdGatewayAndStatus", "mid" => $mid, "gateway" => $gateway, "status" => $status];

            $this->trace->info(TraceCode::TERMINALS_SERVICE_PROXY_V1, $data);

            $this->trace->count(Terminal\Metric::TERMINAL_REPO_PROXY_V1, $metricData);

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

                $this->trace->count(Terminal\Metric::TERMINAL_PROXY_CALL_ERROR, $metricData);
            }
        }

        return $apiTerminals;
    }

    public function findMerchantIdByGatewayMerchantID(string $gatewayMerchantId)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId)
                      ->enabled();

        return $query->first();
    }

    public function findMerchantIdByGatewayMerchantIDAll(string $gatewayMerchantId)
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

        $query = $this->newQuery()
                      ->where(Entity::GATEWAY_MERCHANT_ID, '=', $gatewayMerchantId);

        return $query->first();
    }

    public function fetchTerminalsForTokenization(int $count, array $terminalIds = [])
    {
        $metricData = [
            'route' => $this->fetchRouteName(),
            "function" => __FUNCTION__
        ];

        $this->trace->count(Terminal\Metric::TERMINAL_REPO_READ, $metricData);

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

    public function getTerminalsforAffordabilityMethods($method,$from,$count)
    {

        $gateway = [];

        if($method == 'credit_emi')
        {
            $gateway = ['emi_sbi','bajajfinserv'];
        }
        else
        {
            $gateway[] = $method;
        }

        return $this->newQuery()
            ->take($count)
            ->whereIn(Entity::GATEWAY, $gateway)
            ->where(Common::CREATED_AT, '>', $from)
            ->orderBy(Common::CREATED_AT,'asc')
            ->enabled()
            ->get();

    }

    public function getSubMerchantsTerminalsforAffordabilityMethods($method)
    {

        $gateway = [];

        if($method == 'credit_emi')
        {
            $gateway = ['emi_sbi','bajajfinserv'];
        }
        else
        {
            $gateway[] = $method;
        }

        $query = \Illuminate\Support\Facades\DB::table(Table::MERCHANT_TERMINAL)
            ->whereIn(Terminal\Entity::TERMINAL_ID, function($query) use($gateway) {
                $query->select(Table::TERMINAL.'.'.(Entity::ID))
                    ->from(TABLE::TERMINAL)
                    ->where(Entity::ENABLED,'=',1)
                    ->whereIn(Entity::GATEWAY, $gateway);
            })
            ->whereNotIn(Terminal\Entity::TERMINAL_ID, ['IB8vRg8y8RyyLC', 'I97PuJq0ieb9fp', 'HQ4cD44yZrTodv','EDQZCWqRJbrHmr','H2ELDFayq4gw6i']);

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

    private function isTestEnv() {
        if ($this->app->runningUnitTests() === true or Environment::isEnvironmentQA($this->app['env']) === true) {
            return true;
        } else {
            return false;
        }
    }

}
