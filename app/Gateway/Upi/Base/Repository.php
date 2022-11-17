<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Base\ConnectionType;
use RZP\Gateway\Base\Action;

class Repository extends Base\Repository
{
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
        $entity = parent::findByPaymentIdAndActionOrFail($paymentId, $action);

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

    public function fetchGatewayPaymentIdByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where('payment_id' , '=', $paymentId)
                    ->pluck('gateway_payment_id');
    }

    public function fetchByGatewayPaymentIdAndAction(string $gatewayPaymentId, string $action = Action::AUTHORIZE)
    {
        return $this->newQuery()
                    ->where('gateway_payment_id', '=', $gatewayPaymentId)
                    ->where('action', '=', $action)
                    ->firstOrFail();
    }

    public function fetchByNpciReferenceIdAndGateway(string $npciReferenceId, string $gateway, string $action = Action::AUTHORIZE)
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where('action', '=', $action)
                    ->where('gateway', '=', $gateway)
                    ->first();
    }

    public function fetchByNpciReferenceIdAndPaymentIdAndGateway(string $npciReferenceId, string $paymentId, string $gateway, $action = Action::AUTHORIZE)
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->where(Entity::PAYMENT_ID, '=', $paymentId)
                    ->where('gateway', '=', $gateway)
                    ->where('action', '=', $action)
                    ->first();
    }

    public function fetchByNpciReferenceIdAndActions(string $npciReferenceId, array $actions = [])
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
                    ->whereIn('action', $actions)
                    ->first();
    }

    public function fetchByPaymentId($paymentId)
    {
        return $this->newQuery()
                    ->where(Entity::PAYMENT_ID , '=', $paymentId)
                    ->first();
    }

    public function fetchByRefundId(string $refundId)
    {
        return $this->newQuery()
                    ->where(Entity::REFUND_ID , '=', $refundId)
                    ->first();
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
        return $this->newQuery()
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();
    }

    public function fetchReceivedByMerchantReference(string $merchantReference)
    {
        return $this->newQuery()
                    ->where('merchant_reference', '=', $merchantReference)
                    ->first();
    }

    public function findAllByNpciTxnId(string $npciTxnId)
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_TXN_ID, '=', $npciTxnId)
                    ->get();
    }

    public function findByMatchingNpciReferenceId(string $match, array $select, int $count, array $filter)
    {
        return $this->newQuery()
                    ->select($select)
                    ->where($filter)
                    ->where(Entity::NPCI_REFERENCE_ID, 'like', $match)
                    ->orderBy(Entity::NPCI_REFERENCE_ID, 'desc')
                    ->limit($count)
                    ->get();
    }

    public function fetchByNpciReferenceIdOrGatewayPaymentId(string $arn)
    {
        return $this->newQuery()
                    ->where(Entity::NPCI_REFERENCE_ID, '=', $arn)
                    ->orWhere(Entity::GATEWAY_PAYMENT_ID, '=', $arn)
                    ->first();
    }

    public function findAllByNpciReferenceIdAndGateway(string $npciReferenceId, string $gateway, string $action = Action::AUTHORIZE)
    {
        return $this->newQuery()
            ->where(Entity::NPCI_REFERENCE_ID, '=', $npciReferenceId)
            ->where('action', '=', $action)
            ->where('gateway', '=', $gateway)
            ->get();
    }
}
