<?php

namespace RZP\Models\Base\Traits;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity;
use RZP\Constants\Metric;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

/**
 * This is applied to a new Repository created in Payment/Transfer/Repository.php
 * This trait fetches payment by directly calling the Payment/Repository.php bypassing the ExternalRepo.php
 * If payment is not found via Payment/Repository.php, then the payment is fetched via Route microservice.
 * This is to be used specifically in the flows where a linked account payment is fetched.
 *
 * Example usage:
 *  $this->repo->payment_method_transfer->findXXX();
 *
 */
trait ExternalLinkedAccountPaymentRepo
{
    use ArchivedCore;

    protected $entityName;

    public function findByPublicId($id, string $connectionType = null)
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findByPublicId($id, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true )
            {
                return $this->fetchExternalTransferTypePaymentById($id);
            }
        }
        catch (\Throwable $e) {}

        return $this->findByPublicIdArchived($id);
    }

    public function findByPublicIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findByPublicIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id, $params);
            }
        }
        catch (\Throwable $e) {}

        return $this->findByPublicIdAndMerchantArchived($id, $merchant, $params);
    }

    public function findByIdAndMerchant(
        string $id,
        Merchant\Entity $merchant,
        array $params = [],
        string $connectionType = null): PublicEntity
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findByIdAndMerchant($id, $merchant, $params, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id, $params);
            }
        }
        catch (\Throwable $e) {}

        return $this->findByIdAndMerchantArchived($id, $merchant, $params);
    }

    public function findByIdAndMerchantId($id, $merchantId, string $connectionType = null)
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findByIdAndMerchantId($id, $merchantId, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id);
            }
        }
        catch (\Throwable $e) {}

        return $this->findByIdAndMerchantIdArchived($id, $merchantId);
    }

    public function findOrFailByPublicIdWithParams($id, array $params, string $connectionType = null): PublicEntity
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findOrFailByPublicIdWithParams($id, $params, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id, $params);
            }
        }
        catch (\Throwable $e) {}

        return $this->findOrFailByPublicIdWithParamsArchived($id, $params);
    }

    public function findOrFailPublic($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findOrFailPublic($id, $columns, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id);
            }
        }
        catch (\Throwable $e) {}

        return $this->findOrFailPublicArchived($id, $columns);
    }

    public function findOrFail($id, $columns = array('*'), string $connectionType = null)
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findOrFail($id, $columns, $connectionType);
        }
        catch (\Throwable $e) {}

        try
        {
            if ($this->validateExternalFetchEnabled() === true)
            {
                return $this->fetchExternalTransferTypePaymentById($id);
            }
        }
        catch (\Throwable $e) {}

        return $this->findOrFailOnlyArchived($id, $columns);
    }

    public function findByTransferIdAndMerchant(string $transferId, string $accountId, array $relations = [], $connectionType = null)
    {
        $this->entityName = Entity::PAYMENT_METHOD_TRANSFER;

        try
        {
            return (new Payment\Repository())->findByTransferIdAndMerchant($transferId, $accountId, $relations, $connectionType);
        }
        catch (\Throwable $outerEx)
        {
            try
            {
                if ($this->validateExternalFetchEnabled() === true)
                {
                    return $this->fetchExternalTransferTypePaymentByTransferIdAndAccountId($transferId, $accountId, $relations);
                }
            }
            catch (\Throwable $innerEx) {}

            throw $outerEx;
        }
    }

    private function validateExternalFetchEnabled()
    {
        $keyName = Entity::getExternalConfigKeyName($this->entityName);

        return (bool) ConfigKey::get($keyName, false);
    }

    public function fetchExternalTransferTypePaymentById(string $id, $input=[]): Payment\Entity
    {
        $class = Entity::getExternalRepoSingleton(Entity::PAYMENT_METHOD_TRANSFER);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            $entity = $class->fetchPaymentById($id);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                $relations = $this->getExpandsForQueryFromInput($input);

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
                TraceCode::FETCH_PAYMENT_VIA_ROUTE_SERVICE,
                [
                    'id'         => $id,
                    'data'       => $e->getMessage(),
                    'from'       => $callerFunc,
                ]);

            $this->traceFailureMetrics($callerFunc, $startTime);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => [
                'id'       => $id,
                'input'    => $input,
            ],
            'operation' => $callerFunc,
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function fetchExternalTransferTypePaymentByTransferIdAndAccountId(
        string $transferId,
        string $accountId,
        $input=[]): Payment\Entity
    {
        $class = Entity::getExternalRepoSingleton(Entity::PAYMENT_METHOD_TRANSFER);

        $startTime = millitime();

        $callerFunc = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];

        try
        {
            $entity = $class->fetchPaymentByTransferIdAndAccountId($transferId, $accountId);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

                $relations = $this->getExpandsForQueryFromInput($input);

                $entity->loadMissing($relations);

                $this->traceSuccessMetrics($callerFunc, $startTime);

                return $entity;
            }

        }
        catch (\Throwable $e)
        {
            $this->traceFailureMetrics($callerFunc, $startTime);

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FETCH_PAYMENT_VIA_ROUTE_SERVICE,
                [
                    'transfer_id'  => $transferId,
                    'account_id'   => $accountId,
                    'data'         => $e->getMessage(),
                    'from'         => $callerFunc,
                ]);
        }

        $data = [
            'model' => $this->entityName,
            'attributes' => [
                'transfer_id'      => $transferId,
                'account_id'       => $accountId,
                'input'            => $input,
            ],
            'operation' => $callerFunc,
        ];

        throw new Exception\BadRequestException(
            ErrorCode::BAD_REQUEST_INVALID_ID, null, $data);
    }

    public function getUpdatableLinkedAccountPaymentFields(Payment\Entity $payment)
    {
        $params = [];

        $params[Payment\Entity::STATUS] = $payment->getStatus();

        $params[Payment\Entity::AMOUNT_REFUNDED] = $payment->getAmountRefunded();

        $params[Payment\Entity::ON_HOLD_UNTIL] = $payment->getOnHoldUntil();

        $params[Payment\Entity::ON_HOLD] = $payment->getOnHold();

        $params[Payment\Entity::TRANSACTION_ID] = $payment->getTransactionId();

        $params[Payment\Entity::TRANSFER_ID] = $payment->getTransferId();

        $params[Payment\Entity::REFUND_STATUS] = $payment->getRefundStatus();

        $params[Payment\Entity::UPDATED_AT] = $payment->getUpdatedAt();

        $params[Payment\Entity::ERROR_CODE] = $payment->getErrorCode();

        $params['message'] = '';

        return $params;
    }

    protected function traceFailureMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_FAILURE_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    protected function traceSuccessMetrics(string $functionName, $startTime)
    {
        $trace = App::getFacadeRoot()['trace'];

        $trace->count(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS, [
            'caller'      => $functionName,
        ]);

        $trace->histogram(Metric::EXTERNAL_LA_PAYMENT_REPO_FETCH_SUCCESS_TIME_TAKEN,
            millitime() - $startTime,
            [
                'caller'  => $functionName
            ]);
    }

    public function serializeForIndexingForExternal(PublicEntity $entity): array
    {
        return $this->serializeForIndexing($entity);
    }
}
