<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Base\Traits\ArchivedCore;
use RZP\Models\Merchant\Core as MerchantCore;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Base\ConnectionType;
use RZP\Gateway\Base\Action;

class Repository extends Base\Repository
{
    use  ArchivedCore;

    protected $entity = 'upi';

    protected $appFetchParamRules = array(
        Entity::GATEWAY                 => 'sometimes|string|max:50',
        Entity::BANK                    => 'sometimes|min:4|max:4',
        Entity::GATEWAY_PAYMENT_ID      => 'sometimes|string|max:50',
        Entity::NPCI_REFERENCE_ID       => 'sometimes|string|max:20',
        Entity::PAYMENT_ID              => 'sometimes|string|min:14|max:18',
        Entity::REFUND_ID               => 'sometimes|string|min:14|max:18',
        Entity::MERCHANT_REFERENCE      => 'sometimes|string|max:50',
    );

    public function findByPaymentIdAndActionOrFail($paymentId, $action)
    {
        try
        {
            $entity = parent::findByPaymentIdAndActionOrFail($paymentId, $action);
        }
        catch (\Throwable $e)
        {
            $entity = $this->newQueryAndResetEntityConnection(function () use ($paymentId, $action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $query = $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                        ->where(Entity::PAYMENT_ID, '=', $paymentId)
                        ->where('action', '=', $action)
                        ->orderBy(Entity::CREATED_AT, 'desc');

                $upiEntity = $query->firstOrFail();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        // We need to populate the npci_reference_id field from mozart entity
        // if it is not set in UPI entity.
        if (($entity instanceof Entity) and
            ($entity->getGateway() === Payment\Gateway::UPI_AIRTEL) and
            (empty($entity->getNpciReferenceId())) === true)
        {
            $mozartEntity = $this->repo->mozart->findByPaymentIdAndActionOrFail($paymentId, $action)->toArray();

            // rrn is stored in raw column of mozart entity.
            if (isset($mozartEntity['raw']) === false)
            {
                return $entity;
            }

            // raw column is stored in json format.
            $rawData = json_decode($mozartEntity['raw'], true);

            if (empty($rawData['rrn']) === false)
            {
                $entity->setNpciReferenceId($rawData['rrn']);

                $this->repo->saveOrFail($entity);
            }
        }

        return $entity;
    }

    public function fetchByGatewayPaymentIdAndAction(string $gatewayPaymentId, string $action = Action::AUTHORIZE)
    {
        try
        {
            $upi =  $this->newQuery()
                ->where('gateway_payment_id', '=', $gatewayPaymentId)
                ->where('action', '=', $action)
                ->firstOrFail();

        }
        catch (\Throwable $e)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($gatewayPaymentId,$action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where('gateway_payment_id', '=', $gatewayPaymentId)
                    ->where('action', '=', $action)
                    ->firstOrFail();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchByNpciReferenceIdAndGateway(string $npciReferenceId, string $gateway, string $action = Action::AUTHORIZE)
    {
        $upi =  $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId,$gateway,$action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }
    public function fetchByNpciReferenceIdAndActions(string $npciReferenceId, array $actions = [])
    {
        $upi = $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->whereIn('action', $actions)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId,$actions)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->whereIn('action', $actions)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchByPaymentId($paymentId)
    {
        $upi =  $this->newQuery()
                    ->where(Entity::PAYMENT_ID , '=', $paymentId)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($paymentId)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::PAYMENT_ID , '=', $paymentId)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchByRefundId(string $refundId)
    {
        $upi = $this->newQuery()
                    ->where(Entity::REFUND_ID , '=', $refundId)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($refundId)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::REFUND_ID , '=', $refundId)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchAllForBankUpdate($limit = 100, $lastId = 0)
    {
        $paymentId = $this->repo->payment->dbColumn(Payment\Entity::ID);
        $paymentStatus = $this->repo->payment->dbColumn(Payment\Entity::STATUS);

        $upiId = $this->dbColumn(Entity::ID);
        $upiVpa = $this->dbColumn(Entity::VPA);
        $upiBank = $this->dbColumn(Entity::BANK);
        $upiPaymentId = $this->dbColumn(Entity::PAYMENT_ID);

        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT);

        return $this->newQueryWithConnection($connectionType)
                    ->select($upiId, $upiVpa)
                    ->join(TABLE::PAYMENT, $upiPaymentId, '=', $paymentId)
                    ->where($paymentStatus, '=', Payment\Status::CAPTURED)
                    ->where($upiId, '>', $lastId)
                    ->whereNull($upiBank)
                    ->limit($limit)
                    ->orderBy($upiId)
                    ->get();
    }

    public function fetchByMerchantReference(string $merchantReference)
    {
        $upi =  $this->newQuery()
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($merchantReference)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchReceivedByMerchantReference(string $merchantReference)
    {
        $upi = $this->newQuery()
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($merchantReference)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function findAllByNpciTxnId(string $npciTxnId)
    {
        $upi =  $this->newQuery()
                    ->where(Entity::NPCI_TXN_ID, '=', $npciTxnId)
                    ->get();

        if (empty($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciTxnId)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_TXN_ID, '=', $npciTxnId)
                    ->get();
            });
        }

        return $upi;
    }

    public function findByMatchingNpciReferenceId(string $match, array $select, int $count, array $filter)
    {
        $upi = $this->newQuery()
                    ->select($select)
                    ->where($filter)
                    ->where(Entity::NPCI_REFERENCE_ID, 'like', $match)
                    ->orderBy(Entity::NPCI_REFERENCE_ID, 'desc')
                    ->limit($count)
                    ->get();

        if (empty($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($match,$select,$count,$filter)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->select($select)
                    ->where($filter)
                    ->where(Entity::NPCI_REFERENCE_ID, 'like', $match)
                    ->orderBy(Entity::NPCI_REFERENCE_ID, 'desc')
                    ->limit($count)
                    ->get();
            });
        }

        return $upi;
    }

    public function fetchByNpciReferenceIdOrGatewayPaymentId(string $arn)
    {
        $upi = $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $arn)
                    ->orWhere(Entity::GATEWAY_PAYMENT_ID, '=', $arn)
                    ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($arn)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $arn)
                    ->orWhere(Entity::GATEWAY_PAYMENT_ID, '=', $arn)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function findAllByNpciReferenceIdAndGateway(string $npciReferenceId, string $gateway, string $action = Action::AUTHORIZE)
    {
        $upi = $this->newQuery()
            ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
            ->where('action', '=', $action)
            ->where('gateway', '=', $gateway)
            ->get();

        if (empty($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId, $gateway, $action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->get();

            });
        }

        return $upi;
    }

    public function findAllByNpciReferenceIdAmountAndGatewayAndMerchantReference(string $npciReferenceId, string $gateway,string $amount, string $merchantReference, string $action = Action::AUTHORIZE)
    {
        $upi = $this->newQuery()
            ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
            ->where(Entity::AMOUNT, '=',$amount)
            ->where(Entity::MERCHANT_REFERENCE, '=',$merchantReference)
            ->where('action', '=', $action)
            ->where('gateway', '=', $gateway)
            ->get();

        if (empty($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId, $gateway, $amount, $merchantReference, $action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where(Entity::AMOUNT, '=',$amount)
                    ->where(Entity::MERCHANT_REFERENCE, '=',$merchantReference)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->get();

            });
        }

        return $upi;
    }

    public function fetchByNpciReferenceIdAmountAndGatewayAndMerchantReference(string $npciReferenceId, string $gateway,string $amount, string $merchantReference, string $action = Action::AUTHORIZE)
    {
        $upi = $this->newQuery()
            ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
            ->where(Entity::AMOUNT, '=',$amount)
            ->where(Entity::MERCHANT_REFERENCE, '=',$merchantReference)
            ->where('action', '=', $action)
            ->where('gateway', '=', $gateway)
            ->first();

        if (is_null($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId, $gateway, $amount, $merchantReference, $action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                $upiEntity =  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where(Entity::AMOUNT, '=',$amount)
                    ->where(Entity::MERCHANT_REFERENCE, '=',$merchantReference)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->first();

                if ($upiEntity !== null)
                {
                    $upiEntity->setArchived(true);
                }

                return $upiEntity;
            });
        }

        return $upi;
    }

    public function fetchAllByMerchantReferenceAndNpciReferenceIdAndGateway(string $merchantReference, string $npciReferenceId, string $gateway, string $action = Action::AUTHORIZE)
    {
        $upi = $this->newQuery()
            ->where(Entity::MERCHANT_REFERENCE, '=', $merchantReference)
            ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
            ->where(Entity::GATEWAY, '=', $gateway)
            ->where('action', '=', $action)
            ->get();

        if (empty($upi) === true)
        {
            $upi = $this->newQueryAndResetEntityConnection(function () use ($npciReferenceId, $gateway, $merchantReference, $action)
            {
                $connectionType = $this->checkHarvsterQuerySplitzAndReturnConnection();

                return  $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
                    ->where(Entity::MERCHANT_REFERENCE, '=', $merchantReference)
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where(Entity::GATEWAY, '=', $gateway)
                    ->where('action', '=', $action)
                    ->get();
            });
        }

        return $upi;
    }

    protected function checkHarvsterQuerySplitzAndReturnConnection()
    {
        $properties = [
            "id" => UniqueIdEntity::generateUniqueId(),
            "experiment_id" => $this->app['config']->get('app.splitz_harvester_query_upi_experiment_id'),
        ];

        $splitzEnabled = (new MerchantCore())->isSplitzExperimentEnable($properties, 'Enable');

        return $splitzEnabled === true ? ConnectionType::DATA_WAREHOUSE_MERCHANT :ConnectionType::PAYMENT_FETCH_REPLICA;
    }
}
