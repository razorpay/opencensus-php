<?php

namespace RZP\Models\Base\Traits;

use RZP\Constants\Entity;
use RZP\Constants\Environment;
use RZP\Error\ErrorCode;
use RZP\Services\Scrooge;
use RZP\Models\Base\PublicCollection;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Constants;
use RZP\Models\Payment\Refund\Service;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Payment\Refund\Constants as RefundConstants;

trait ExternalScroogeRepo
{
    protected $entityName;

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                        'method_name'     => __FUNCTION__,
                        'id'              => $id,
                        'route_name'      => $routeName,
                        'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse =  $this->fetchExternalRefundById($id);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findByPublicId($id, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);
                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                ]);
        }

        return parent::findByPublicId($id, $connectionType);
    }

    public function find($refundId, $columns = array('*'), string $connectionType = null)
    {
        if ($this->repo->refund->isScroogeReadMigrationEnabledForFetchById() == true) {
            $refunds = $this->repo->refund->findRefundById($refundId,$connectionType, $columns);
            if(empty($refunds) == true){
                return null;
            }
            return $refunds;
        }else{
            return parent::find($refundId,$columns, $connectionType);
        }
    }

    public function findByPublicIdAndMerchant(
        string $id,
        MerchantEntity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'merchant_id'     => $merchant->getId(),
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id, $merchant->getId());

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                    'merchant_id' => $merchant->getId(),
                ]);
        }

        return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function findByIdAndMerchant(
        string $id,
        MerchantEntity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'merchant_id'     => $merchant->getId(),
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id, $merchant->getId());

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);
                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                    'merchant_id' => $merchant->getId(),
                ]);
        }

        return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'merchant_id'     => $merchantId,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id, $merchantId);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findByIdAndMerchantId($id, $merchantId, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                    'merchant_id' => $merchantId,
                ]);
        }

        return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'params'          => $params,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id, '', $params);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                ]);
        }

        return parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'columns'         => $columns,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findOrFailPublic($id, $columns, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                ]);
        }

        return parent::findOrFailPublic($id, $columns, $connectionType);
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            $routeName = $this->route->getCurrentRouteName();

            $forceLoadFromApi = $this->forceRefundLoadFromApi($routeName);

            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name'     => __FUNCTION__,
                    'id'              => $id,
                    'columns'         => $columns,
                    'route_name'      => $routeName,
                    'force_route_api' => $forceLoadFromApi,
                ]);

            if (($forceLoadFromApi === false) and
                ($this->validateExternalFetchEnabledForScrooge() == true) and
                (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                $scroogeResponse = $this->fetchExternalRefundById($id);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

                $apiResponse     = parent::findOrFail($id, $columns, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                return $apiResponse;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id'          => $id,
                ]);
        }

        return parent::findOrFail($id, $columns, $connectionType);
    }

    public function validateExternalFetchEnabledForScroogeNonShadow($id = null)
    {
        return $this->isProdEnv();
    }

    public function isProdEnv(): bool
    {
        $env = $this->app->environment();

        if ($env === Environment::PRODUCTION)
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigration($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationForReversal($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationEnabled($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }
    public function isScroogeReadMigrationEnabledForReversal($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigration2($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationEnabled2($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationEnabledForFetchById($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationForFetchById($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationForIrctc($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationForGateways($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }


    public function isScroogeReadMigrationEnabledTidb($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationTidb($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationTidbForFetchCards($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationTidbForLaReversals($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function isScroogeReadMigrationTidbEnabledForLaReversals($id = null)
    {
        if ($this->isProdEnv())
        {
            return true;
        }

        return false;
    }

    public function validateExternalFetchEnabledForScrooge($id = null)
    {
        $keyName = Entity::getExternalConfigKeyName($this->entityName);

        $keyStatus = (bool) ConfigKey::get($keyName, false);

        if ($keyStatus === true)
        {
            try {
                // $mode = $this->app['rzp.mode'] ?? 'live'; // todo: to check how to handle this in splitz

                $experimentId = $this->app['config']->get('app.entity_relational_load_from_scrooge_experiment_id');

                if (empty($experimentId))
                {
                    return false;
                }

                $this->trace->info(TraceCode::ENTITY_RELATIONAL_LOAD_FROM_SCROOGE_EXPERIMENT_REQUEST_LOG, [
                    'experiment_id' => $experimentId,
                ]);
    
                $properties = [
                    'id'            => UniqueIdEntity::generateUniqueId(),
                    'experiment_id' => $experimentId,
                ];
        
                $response = $this->app['splitzService']->evaluateRequest($properties);
            
                $variant = $response['response']['variant']['name'] ?? '';
    
                $this->trace->info(TraceCode::ENTITY_RELATIONAL_LOAD_FROM_SCROOGE_EXPERIMENT_RESPONSE_LOG, [
                    'experiment_id' => $experimentId,
                    'splitz_output' => $response,
                ]);

                if ($variant === 'enabled')
                {
                    return true;
                }
    
                $ftaRoutes =  \RZP\Http\Route::$loadRefundsFromScroogeForFtaRoutes;
    
                $routeName = $this->route->getCurrentRouteName();
    
                if (empty($id) === false and in_array($routeName, $ftaRoutes, true) === true){
                    // fta source loading from scrooge for this route
                    return true;
                }
            }
            catch( \Throwable $ex)
            {
                $this->trace->error(TraceCode::ENTITY_RELATIONAL_LOAD_FROM_SCROOGE_EXPERIMENT_FAILURE, [
                    'message' => $ex->getMessage(),
                ]);

                return false;
            }
        }

        return false;
    }

    public function fetchExternalRefundById($id, $merchantId = '', $input = [], $fetchUnscoped = false)
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            $scroogeFetchParams = [
                'id' => $id
            ];

            if (empty($merchantId) == false)
            {
                $scroogeFetchParams['merchant_id'] = $merchantId;
            }

            // scrooge entity fetch input
            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => $scroogeFetchParams,
                ]
            ];

            if ($fetchUnscoped === true)
            {
                $scrooge_fetch_query['fetch_unscoped'] = true;
            }

            $entity = $class->fetchRefund($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'attributes' => $id,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function fetchExternalRefundForPayment($paymentId, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            // scrooge entity fetch input
            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'payment_id' => $paymentId
                    ]
                ]
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'attributes' => $paymentId,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function fetch(array $params,
                          string $merchantId = null,
                          string $connectionType = null): PublicCollection
    {
        if (!$this->isProdEnv())
        {
            return parent::fetch($params, $merchantId, $connectionType);
        }

        $class = Entity::getExternalRepoSingleton(Entity::REFUND);

        $scroogeInput = [];
        $count = 20;
        $skip = 0;

        $allowedParams = ['payment_id', 'gateway', 'merchant_id', 'notes'];

        foreach($params as $key => $value)
        {
            if (in_array($key, $allowedParams) === true)
            {
                $scroogeInput[$key] = $value;
            }
        }

        if (empty($merchantId) === false)
        {
            $scroogeInput['merchant_id'] = $merchantId;
        }

        if (key_exists('status', $params) && empty($params['status']) === false)
        {
            if ($params['status'] === 'created')
            {
                $scroogeInput['status'] = ['file_init', 'init', 'on_hold', 'fta_pending', 'debit_validation_pending', 'file_sent'];
            }
            else
            {
                $scroogeInput['status'] = $params['status'];
            }
        }

        if (key_exists('from', $params) && empty($params['from']) === false)
        {
            $scroogeInput['created_at']['gte'] = $params['from'];
        }

        if (key_exists('to', $params) && empty($params['to']) === false)
        {
            $scroogeInput['created_at']['lte'] = $params['to'];
        }

        if (key_exists('count', $params) && empty($params['count']) === false)
        {
            $count = $params['count'];
        }

        if (key_exists('skip', $params) && empty($params['skip']) === false)
        {
            $skip = $params['skip'];
        }

        $scrooge_fetch_query = [
            'count' => $count,
            'skip' => $skip
        ];

        if (empty($scroogeInput) === false)
        {
            $scrooge_fetch_query['query'] = [
                'refunds' => $scroogeInput
            ];
        }
        try
        {
            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                return $entity;
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $ex->getMessage(),
                ]);
        }

        return new PublicCollection();
    }

    private function fetchRefundForPaymentIdAndAmount($paymentId, $amount, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            // scrooge entity fetch input
            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'payment_id' => $paymentId,
                        'amount' => $amount

                    ]
                ]
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'attributes' => $paymentId,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function fetchRefundByIds($refundIds, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {
            // Construct the fetch query
            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'id' => $refundIds,
                    ]
                ]
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'attributes' => $refundIds,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function fetchRefundList($merchantIds, $params): array
    {
        // Fetch count of records to process per batch,
        $count =   $params[RefundConstants::SCROOGE_COUNT] ?? Constants::FETCH_FROM_SCROOGE_COUNT; // Safeguard against infinite loops

        // Initial offset for pagination
        $skip  =   $params[RefundConstants::SCROOGE_SKIP] ?? 0;

        $refundData = [];

        // Prepare the base input for the refund fetch query
        $scroogeInput = [
            RefundEntity::MERCHANT_ID => $merchantIds,
            RefundConstants::METHOD => $params[RefundConstants::METHOD],
            RefundConstants::STATUS => $params[RefundConstants::STATUS],
        ];

        // Add time range (start, end) filters to query input
        if (isset($params[RefundConstants::SCROOGE_GTE]) === true)
        {
            $scroogeInput[RefundConstants::SCROOGE_PROCESSED_AT][RefundConstants::SCROOGE_GTE] = $params[RefundConstants::SCROOGE_GTE];
        }

        if (isset($params[RefundConstants::SCROOGE_LTE]) === true)
        {
            $scroogeInput[RefundConstants::SCROOGE_PROCESSED_AT][RefundConstants::SCROOGE_LTE] = $params[RefundConstants::SCROOGE_LTE];
        }

        // Construct the Refund fetch query
        $scrooge_fetch_query = [
            RefundConstants::SCROOGE_COUNT =>  $count,
            RefundConstants::SCROOGE_QUERY =>  [
                RefundConstants::SCROOGE_REFUNDS => $scroogeInput
            ],
        ];

       // To fetch refunds in batches
        do
        {
            $scroogeRefunds = [];

            // Update the skip parameter for pagination
            $scrooge_fetch_query[RefundConstants::SCROOGE_SKIP] = $skip;

            try
            {
                $response = $this->app['scrooge']->getRefunds($scrooge_fetch_query);

                if ((in_array($response[RefundConstants::RESPONSE_CODE], Scrooge::RESPONSE_SUCCESS_CODES, true) === true)
                and (isset($response[RefundConstants::RESPONSE_BODY][RefundConstants::RESPONSE_DATA]) === true))
                {
                    $scroogeRefunds = $response[RefundConstants::RESPONSE_BODY][RefundConstants::RESPONSE_DATA];

                    $refundData = array_merge($refundData, $scroogeRefunds);

                    // Update the skip parameter to fetch the next batch
                    $skip += $count;
                }
                else
                {
                    return $refundData;
                }
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::EXTERNAL_REPO_FETCH_REQUEST_FAILURE,
                    [
                        'error'     => $e->getMessage(),
                        'input'     => $scrooge_fetch_query,
                    ]);
            }

        }while(count($scroogeRefunds) > 0);

        return $refundData;
    }

    /*
     * it will verify and enable to add some routes to skip fetching data
     * from scrooge microservice
     */
    public function forceRefundLoadFromApi($routeName): bool
    {
        if ($routeName === 'reconciliate_via_batch_service')
        {
            if ($this->isProdEnv())
            {
                return false;
            }
        }

        if ($routeName === 'admin_fetch_entity_by_id')
        {
            if ($this->isProdEnv())
            {
                return false;
            }
        }

        $routes = \RZP\Http\Route::$forceRefundsLoadFromApiRoutes;

        return (in_array($routeName, $routes, true) === true);
    }

    private function fetchRefundByReceiptAndMerchantId(string $receipt, string $merchantId, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {

            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'merchant_id'=> $merchantId,
                        'receipt'=> $receipt,
                    ]
                ],
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'receipt'    => $receipt,
            'merchant_id'=> $merchantId,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function fetchRefundByReversalIdAndMerchantId(string $reversalId, string $merchantId, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {

            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'reversal_id' => $reversalId,
                        'merchant_id'=> $merchantId

                    ]
                ]
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'reversal_id'    => $reversalId,
            'merchant_id'=> $merchantId,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function fetchRefundByPaymentAndBaseAmountFromScrooge(string $paymentId, $baseAmount, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {

            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'payment_id' => $paymentId,
                        'base_amount'=> $baseAmount

                    ]
                ]
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'payment_id'    => $paymentId,
            'base_amount'=> $baseAmount,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function fetchFirstRefundByPaymentFromScrooge(string $paymentId, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {

            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'payment_id' => $paymentId,
                    ]
                ],
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity->all()[0];
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'payment_id'    => $paymentId,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    private function findByPaymentIdAndReference3FromScrooge(string $paymentId, int $seqNo, $input = [])
    {
        $class = Entity::getExternalRepoSingleton($this->entity);

        try
        {

            $scrooge_fetch_query = [
                'query' => [
                    'refunds' => [
                        'payment_id'=> $paymentId,
                    ],
                    'gateway_keys'=>[
                        'name'=> 'sequence_no',
                        'value'=> $seqNo,
                    ],
                ],
            ];

            $entity = $class->fetchRefunds($scrooge_fetch_query);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::EXTERNAL_REPO_REQUEST_FAILURE,
                [
                    'data'        => $e->getMessage(),
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'payment_id'    => $paymentId,
            'sequence_no'=> $seqNo,
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }
}
