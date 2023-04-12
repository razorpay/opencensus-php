<?php

namespace RZP\Models\Base\Traits;

use RZP\Constants\Entity;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Payment\Refund\Service;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Entity as MerchantEntity;
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
                $apiResponse     = parent::findByPublicId($id, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }
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
                $apiResponse     = parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

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
                $apiResponse     = parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }
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
                $apiResponse     = parent::findByIdAndMerchantId($id, $merchantId, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

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
                $apiResponse     = parent::findOrFailByPublicIdWithParams($id, $params, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

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
                $apiResponse     = parent::findOrFailPublic($id, $columns, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }

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
                $apiResponse     = parent::findOrFail($id, $columns, $connectionType);

                (new Service())->compareRefundsAndLogDifference(
                    [$apiResponse->toArray()], [$scroogeResponse->toArray()], ['method_name' => __FUNCTION__]);

                if ($this->validateExternalFetchEnabledForScroogeNonShadow() == true)
                {
                    return $scroogeResponse;
                }
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
        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app['razorx']->getTreatment(
            UniqueIdEntity::generateUniqueId(),
            RazorxTreatment::ENTITY_RELATIONAL_LOAD_FROM_SCROOGE_NON_SHADOW,
            $mode);

        $this->trace->info(
            TraceCode::SCROOGE_ENTITY_FETCH_NON_SHADOW_RAZORX_RESPONSE,
            [
                'result'    => $result,
                'mode'      => $mode,
            ]);

        if ($result === 'on')
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
            $mode = $this->app['rzp.mode'] ?? 'live';

            // ramp up will be based on percentage so it will not be having any merchant_id
            $result = $this->app['razorx']->getTreatment(
                UniqueIdEntity::generateUniqueId(),
                RazorxTreatment::ENTITY_RELATIONAL_LOAD_FROM_SCROOGE,
                $mode);

            $this->trace->info(
                TraceCode::SCROOGE_ENTITY_FETCH_RAZORX_EXPERIMENT_RESPONSE,
                [
                    'result'    => $result,
                    'mode'      => $mode,
                    'key_status'=> $keyStatus,
                ]);

            if ($result === 'on')
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

    /*
     * it will verify and enable to add some routes to skip fetching data
     * from scrooge microservice
     */
    public function forceRefundLoadFromApi($routeName): bool
    {
        $routes = \RZP\Http\Route::$forceRefundsLoadFromApiRoutes;

        return (in_array($routeName, $routes, true) === true);
    }
}
