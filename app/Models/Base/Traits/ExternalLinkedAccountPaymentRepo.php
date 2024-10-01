<?php

namespace RZP\Models\Base\Traits;

use App;
use Razorpay\Trace\Logger as Trace;
use RZP\Constants\Entity;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Admin\ConfigKey;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;

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
            catch (\Throwable $innerEx)
            {
                $this->trace->traceException(
                    $innerEx,
                    Trace::ERROR,
                    TraceCode::ROUTE_ENTITY_FETCH_FAILURE,
                    [
                        'exception_message' => $innerEx->getMessage(),
                        'function_name'     => __FUNCTION__
                    ]);

            }

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

        try
        {
            $entity = $class->fetchPaymentById($id);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

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
            'model' => $this->entityName,
            'attributes' => [
                'id'       => $id,
                'input'    => $input,
            ],
            'operation' => 'find'
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

        try
        {
            $entity = $class->fetchPaymentByTransferIdAndAccountId($transferId, $accountId);

            if (empty($entity) === false)
            {
                $entity->setExternal(true);

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
            'model' => $this->entityName,
            'attributes' => [
                'transfer_id'      => $transferId,
                'account_id'       => $accountId,
                'input'            => $input,
            ],
            'operation' => 'find'
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

    public function serializeForIndexingForExternal(PublicEntity $entity): array
    {
        return $this->serializeForIndexing($entity);
    }
}
