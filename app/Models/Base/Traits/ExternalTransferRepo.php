<?php

namespace RZP\Models\Base\Traits;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity as EntityConstants;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
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
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }

    public function findOrFailPublic($id, $columns = ['*'], string $connectionType = null)
    {
        $this->entityName = $this->entity;

        try
        {
            return parent::findOrFailPublic($id, $columns, $connectionType);
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
            catch(\Throwable $ex) {}

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
            catch(\Throwable $ex) {}

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
            catch(\Throwable $ex) {}

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
            catch(\Throwable $ex) {}

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
            catch(\Throwable $ex) {}

            // Throw original exception
            throw $e;
        }
    }

    protected function fetchExternalTransfer($transferId, $queryParams=[])
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entity);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            $entity = $class->fetchTransferById($transferId);

            if (empty($entity) === false)
            {
                $relations = $this->getExpandsForQueryFromInput($queryParams);

                $entity->loadMissing($relations);

                $this->traceSuccessMetrics($callerFunc, $startTime);

                return $entity;
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FETCH_TRANSFER_VIA_ROUTE_SERVICE_FAILURE,
                [
                    '$transfer_id' => $transferId,
                    'data'         => $e->getMessage(),
                    'from'         => $callerFunc,

                ]);

            $this->traceFailureMetrics($callerFunc, $startTime);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => $callerFunc,
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    protected function saveTransferViaRouteService($transferId, $params)
    {
        $class = EntityConstants::getExternalRepoSingleton($this->entity);

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            return $class->saveApiTransfer($transferId, $params);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SAVE_TRANSFER_VIA_ROUTE_SERVICE_FAILURE,
                [
                    'id'          => $transferId,
                    'data'        => $e->getMessage(),
                    'from'        => $callerFunc,
                ]);
        }

        $data = [
            'model'      => $this->entityName,
            'operation'  => $callerFunc,
        ];

        throw new BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }


    protected function validateIfExternalFetchIsEnabledForTransfer()
    {
        $keyName = EntityConstants::getExternalConfigKeyName($this->entityName);

        return (bool) ConfigKey::get($keyName, false);
    }

    public function getUpdatableTransfersFields($transfer)
    {
        $params = [];

//        $params[Transfer\Entity::ID] = $transfer->getId();

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

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,

        ]);

        $trace->histogram(Metric::EXTERNAL_TRANSFER_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

}
