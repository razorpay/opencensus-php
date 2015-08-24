<?php

namespace Models\Payment\Refund;

use EE\Exception;
use Models\Base;
use Models\Payment\Refund;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Refund';

    public function findOrFailPublicByParams($id, $merchantId, $paymentId = null)
    {
        $repo = $this->repo;

        $query = $repo::where(Refund\Entity::MERCHANT_ID, '=', $merchantId);

        if ($paymentId !== null)
        {
            $query->where(Refund\Entity::PAYMENT_ID, '=', $paymentId);
        }

        return $query->findOrFailPublic($id);
    }

    public function findForPayment($paymentId)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->get();
    }

    public function findBetweenTimestamps($from, $to)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::CREATED_AT, '>=', $from)
                    ->where(Refund\Entity::CREATED_AT, '<=', $to)
                    ->get();
    }

    public function fetchBetweenTimestampsForMerchant($from, $to, $merchantId)
    {
        $repo = $this->repo;
        return $repo::whereBetween(Refund\Entity::CREATED_AT, [$from, $to])
                    ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    public function findBetweenTimesampsForGateway($from, $to, $gateway)
    {
        $repo = $this->repo;

        return $repo::join('payments', 'refunds.payment_id', '=', 'payments.id')
                    ->select('refunds.*', 'payments.gateway')
                    ->where('refunds.created_at', '>=', $from)
                    ->where('refunds.created_at', '<=', $to)
                    ->where('payments.gateway', '=', $gateway)
                    ->get();
    }

    public function fetchByIdPaymentIdMerchantId($id, $paymentId, $merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Refund\Entity::PAYMENT_ID, '=', $paymentId)
                    ->where(Refund\Entity::MERCHANT_ID, '=', $merchantId)
                    ->findOrFailPublic($id);
    }

    public function fetchRefundsForBankBetweenTimestamps($bank, $from, $to)
    {
        $repo = $this->repo;

        $payment = (new Payment\Entity);

        $ptable = $payment->getTable();
        $pid = $payment->getAttributeWithTableName(Payment\Entity::ID);
        $pbank = $payment->getAttributeWithTableName(Payment\Entity::BANK);

        $entity = (new $this->repo);

        $rid = $entity->getAttributeWithTableName(Refund\Entity::ID);

        $query = $entity->newQuery();

        return $query->where(Payment\Entity::BANK, '=', $bank)
                    ->leftJoin(
                        $rtable,
                        function($join) use ($bank, $pid, $rid, $from, $to)
                        {
                            $join->on($rPaymentId, '=', $pid)
                                 ->where($rCreatedAt, '>=', $from)
                                 ->where($rCreatedAt, '<=', $to);
                        })
                    ->with($ptable)
                    ->get();
    }
}
