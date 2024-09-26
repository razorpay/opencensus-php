<?php

namespace RZP\Models\Base\Traits;

use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Trace\TraceCode;
use RZP\Models\Transfer;
use RZP\Models\Merchant;

// TODO:
//  - fetchBySourceTypeAndIdAndMerchant
//  - fetchByPublicIdAndLinkedAccountMerchant
//  Add the above for order/payment transfers and reversal use-case

trait ExternalTransferRepo
{
    protected $entityName;

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findOrFail($id, $columns, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransfer() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalTransfer($id);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }

            // Throw original exception
            throw $e;
        }
    }

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByPublicId($id, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransfer() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    Transfer\Entity::stripSignWithoutValidation($id);

                    return $this->fetchExternalTransfer($id);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }

            // Throw original exception
            throw $e;
        }
    }


    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransfer() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    Transfer\Entity::stripSignWithoutValidation($id);

                    return $this->fetchExternalTransfer($id);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }

            // Throw original exception
            throw $e;
        }
    }

    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransfer() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalTransfer($id);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }

            // Throw original exception
            throw $e;
        }
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findByIdAndMerchantId($id, $merchantId, $connectionType);
        }
        catch (\Throwable $e)
        {
            try
            {
                if ($this->validateIfExternalFetchIsEnabledForTransfer() and
                    (EntityConstants::validateExternalRepoEntity($this->entityName) === true))
                {
                    return $this->fetchExternalTransfer($id);
                }
            }
            catch(\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
            }

            // Throw original exception
            throw $e;
        }
    }

    public function saveOrFail(Transfer\Entity $transfer, array $options = array())
    {
        if (($transfer !== null)
            and ($transfer->isExternal() === true)
            and ($this->validateIfExternalFetchIsEnabledForTransfer() === true))
        {
            try
            {
                $this->entityName = $this->entity;

                if (EntityConstants::validateExternalRepoEntity($this->entityName) === true)
                {
                    $params = $this->getUpdatableTransfersFields($transfer);

                    $this->saveTransferViaRouteService($params);
                }

                return;
            }
            catch (\Throwable $ex)
            {
                $this->trace->traceException(
                    $ex,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $ex->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);
                throw $ex;
            }
        }

        parent::saveOrFail($transfer, $options);
    }

    protected function fetchExternalTransfer($transferId, $queryParams=[])
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entity);

        try
        {
            $entity = $class->fetchTransferById($transferId);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($queryParams);

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
            'operation'  => 'find'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    protected function saveTransferViaRouteService($params)
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entity);

        try
        {
            return $class->saveApiTransfer($params);
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
            'operation'  => 'saveTransferViaRouteService'
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }


    protected function validateIfExternalFetchIsEnabledForTransfer()
    {
        if (app()->runningUnitTests() === true)
        {
            $keyName = EntityConstants::getExternalConfigKeyName($this->entityName);

            return (bool) ConfigKey::get($keyName, false);
        }

        return true;
    }

    protected function getUpdatableTransfersFields($transfer)
    {
        $params = [];

        $params[Transfer\Entity::ID] = $transfer->getId();

        $params[Transfer\Entity::STATUS] = $transfer->getStatus();

        $params[Transfer\Entity::AMOUNT_REVERSED] = $transfer->getAmountReversed();

        $params[Transfer\Entity::ON_HOLD] = $transfer->getOnHold();

        $params[Transfer\Entity::ON_HOLD_UNTIL] = $transfer->getOnHoldUntil();

        $params[Transfer\Entity::TRANSACTION_ID] = $transfer->getAttribute(Transfer\Entity::TRANSACTION_ID);

        $params[Transfer\Entity::RECIPIENT_SETTLEMENT_ID] = $transfer->getRecipientSettlementId();

        $params[Transfer\Entity::SETTLEMENT_STATUS] = $transfer->getSettlementStatus();

        $params[Transfer\Entity::UPDATED_AT] = $transfer->getUpdatedAt();

        $params[Transfer\Entity::PROCESSED_AT] = $transfer->getProcessedAt();

        $params[Transfer\Entity::ERROR_CODE] = $transfer->getErrorCode();

        $params[Transfer\Entity::MESSAGE] = $transfer->getMessage();

        return $params;
    }
}
