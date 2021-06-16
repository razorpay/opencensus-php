<?php

namespace RZP\Models\P2p\Transaction;

use RZP\Exception;
use RZP\Models\P2p\Vpa;
use RZP\Models\P2p\Base;
use RZP\Models\Base\PublicCollection;
use RZP\Models\P2p\Base\Libraries\ArrayBag;
use RZP\Trace\TraceCode;

/**
 * @property  Repository $repo
 * @property  Validator $validator
 */
class Core extends Base\Core
{
    public function create(Properties $properties, array $input): Entity
    {
        $transaction = $this->build($input);

        $properties->attachToTransaction($transaction);

        $this->repo->saveOrFail($transaction);

        return $transaction;
    }

    public function update(Entity $transaction, array $input): Entity
    {
        $this->repo->saveOrFail($transaction);

        return $transaction;
    }

    public function createUpi(Entity $transaction, string $action, array $input = [])
    {
        $refId                = $this->context()->getRequestId();
        $networkTransactionId = $this->context()->handlePrefix() . $this->app['request']->getId();

        $default = [
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => $networkTransactionId,
            UpiTransaction\Entity::REF_ID                   => $refId,
        ];

        $cleaned = $this->cleanUpiInput(array_merge($default, $input));

        $defined = [
            UpiTransaction\Entity::STATUS                   => $transaction->getInternalStatus(),
            UpiTransaction\Entity::ACTION                   => $action,
        ];

        $upi = (new UpiTransaction\Core)->create($transaction, array_merge($cleaned, $defined));

        return $upi;
    }

    public function buildUpi(Entity $transaction, string $action, array $input): UpiTransaction\Entity
    {
        $refId                = $this->context()->getRequestId();
        $networkTransactionId = $this->context()->handlePrefix() . $this->app['request']->getId();

        $default = [
            UpiTransaction\Entity::NETWORK_TRANSACTION_ID   => $networkTransactionId,
            UpiTransaction\Entity::REF_ID                   => $refId,
        ];

        $cleaned = $this->cleanUpiInput(array_merge($default, $input));

        $defined = [
            UpiTransaction\Entity::STATUS                   => $transaction->getInternalStatus(),
            UpiTransaction\Entity::ACTION                   => $action,
        ];

        $upi = (new UpiTransaction\Core)->build(array_merge($cleaned, $defined));

        return $upi;
    }

    public function updateUpi(UpiTransaction\Entity $upi, array $input)
    {
        $cleaned = $this->cleanUpiInput($input);

        $upi = (new UpiTransaction\Core)->update($upi, $cleaned);

        return $upi;
    }

    public function findAllUpi(array $input): PublicCollection
    {
        if (isset($input[UpiTransaction\Entity::ACTION]) === false)
        {
            throw $this->logicException('Action is required', $input);
        }

        $defined = array_only($input, UpiTransaction\Entity::ACTION);

        $transactionId = $input[UpiTransaction\Entity::TRANSACTION_ID] ?? null;
        $networkTransactionId = $input[UpiTransaction\Entity::NETWORK_TRANSACTION_ID] ?? null;

        $this->trace()->info(TraceCode::P2P_CALLBACK_TRACE,[
            'action'                    => $defined[UpiTransaction\Entity::ACTION],
            'rrn'                       => $input[UpiTransaction\Entity::RRN] ?? null,
            'transaction_id'            => $transactionId,
            'network_transaction_id'    => $networkTransactionId
        ]);

        if (empty($networkTransactionId) === false)
        {
            $defined[UpiTransaction\Entity::NETWORK_TRANSACTION_ID] = $networkTransactionId;
        }
        else if (empty($transactionId) === false)
        {
            $defined[UpiTransaction\Entity::TRANSACTION_ID] = $transactionId;
        }
        else
        {
            throw $this->logicException('Invalid find parameters', $input);
        }

        $upi = (new UpiTransaction\Core)->findAll($defined);

        return $upi;
    }

    public function getFirstTransactionWithStatusAndFlow(array $status, $flow)
    {
        return $this->repo->newP2pQuery()
                ->whereIn(Entity::STATUS, $status)
                ->where(Entity::FLOW, $flow)
                ->oldest()
                ->first();
    }

    public function getTotalTransactionAmountWithStatusAndFlow(array $status, $flow)
    {
        return $this->repo->newP2pQuery()
               ->whereIn(Entity::STATUS, $status)
               ->where(Entity::FLOW, $flow)
               ->sum(Entity::AMOUNT);
    }

    /**
     * @param int $day Time in which collect requests are fetched upto
     * @param string $payee_id PayeeID entity
     * @param string $flow Flow entity
     * @param string $type Type entity
     * @param int $limit Number of records to fetch
     * @return mixed Query result
     */
    public function getCollectRequestsWithCreatedAtAndPayee($day, $payee_id, $flow, $type, $limit)
    {
        return $this->repo->newP2pQuery()
                    ->where(Entity::CREATED_AT, '>=', $day)
                    ->where(Entity::PAYEE_ID, $payee_id)
                    ->where(Entity::FLOW, $flow)
                    ->where(Entity::TYPE, $type)
                    ->oldest()
                    ->limit($limit)
                    ->get();
    }

    public function deletePendingCollectForVpa(Vpa\Entity $vpa)
    {
        $query = $this->repo->newP2pQuery();

        $query->where(Entity::STATUS, Status::REQUESTED)
              ->where(Entity::PAYER_TYPE, Vpa\Entity::VPA)
              ->where(Entity::PAYER_ID, $vpa->getId());

        return $query->delete();
    }

    protected function cleanUpiInput(array $input): array
    {
        unset($input[UpiTransaction\Entity::TRANSACTION_ID],
              $input[UpiTransaction\Entity::ACTION],
              $input[UpiTransaction\Entity::HANDLE],
              $input[UpiTransaction\Entity::TRANSACTION]);

        return $input;
    }
}
