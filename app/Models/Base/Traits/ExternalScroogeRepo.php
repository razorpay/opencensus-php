<?php

namespace RZP\Models\Base\Traits;

use RZP\Constants\Entity;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\UniqueIdEntity;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Merchant\Entity as MerchantEntity;

trait ExternalScroogeRepo
{
    protected $entityName;

    public function findByPublicId($id)
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                        'method_name' => 'findByPublicId',
                        'id'          => $id,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id' => $id,
                ]);
        }

        return parent::findByPublicId($id);
    }

    public function findByPublicIdAndMerchant(string $id, MerchantEntity $merchant, array $params = []): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findByPublicIdAndMerchant',
                    'id'          => $id,
                    'merchant_id' => $merchant->getId(),
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id, $merchant->getId());
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

        return parent::findByPublicIdAndMerchant($id, $merchant, $params);
    }

    public function findByIdAndMerchant(string $id, MerchantEntity $merchant, array $params = []): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findByIdAndMerchant',
                    'id'          => $id,
                    'merchant_id' => $merchant->getId(),
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id, $merchant->getId());
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

        return parent::findByIdAndMerchant($id, $merchant, $params);
    }

    public function findByIdAndMerchantId($id, $merchantId)
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findByIdAndMerchantId',
                    'id'          => $id,
                    'merchant_id' => $merchantId,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id, $merchantId);
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

        return parent::findByIdAndMerchantId($id, $merchantId);
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findOrFailByPublicIdWithParams',
                    'id'          => $id,
                    'params'      => $params,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id, '', $params);
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

    public function findOrFailPublic($id, $columns = array('*'))
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findOrFailPublic',
                    'id'          => $id,
                    'columns'     => $columns,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id' => $id,
                ]);
        }

        return parent::findOrFailPublic($id);
    }

    public function findOrFail($id, $columns = array('*'))
    {
        $this->entityName = $this->entity;

        try
        {
            $this->trace->info(
                TraceCode::SCROOGE_RELATIONAL_LOAD_METHOD_CALL,
                [
                    'method_name' => 'findOrFail',
                    'id'          => $id,
                    'columns'     => $columns,
                ]);

            if (($this->validateExternalFetchEnabledForScrooge() == true) and (Entity::validateExternalRepoEntity($this->entityName) === true))
            {
                return $this->fetchExternalRefundById($id);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SCROOGE_ENTITY_FETCH_FAILURE,
                [
                    'id' => $id,
                ]);
        }

        return parent::findOrFail($id);
    }

    public function validateExternalFetchEnabledForScrooge()
    {
        $keyName = Entity::getExternalConfigKeyName($this->entityName);

        $keyStatus = (bool) ConfigKey::get($keyName, false);

        if ($keyStatus === true)
        {
            $mode = $this->app['rzp.mode'] ?? 'live';

            // ramp up will be based on percentage so it will not be having any merchant_id
            $result = app('razorx')->getTreatment(
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

            return $result == 'on';
        }

        return false;
    }

    private function fetchExternalRefundById($id, $merchantId = '', $input = [])
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
                    'data' => $e->getMessage()
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
                    'data' => $e->getMessage()
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
}
