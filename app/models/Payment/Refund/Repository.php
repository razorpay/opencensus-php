<?php

namespace Models\Payment\Refund;

use EE\Exception;
use Models\Base;
use Models\Payment;
use Models\Payment\Refund;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Refund';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
    );

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

    public function fetchRefundsForBankBetweenTimestamps($bank, $from, $to, $gateway)
    {
        $repo = $this->repo;

        $ptable = Payment\Entity::getTableName();

        $attrs = Refund\Entity::getTableName() . '.*';

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $ptable,
            function ($join) use ($from, $to, $bank, $gateway)
            {
                $rPaymentId = Refund\Entity::getAttributeWithTableName(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = Refund\Entity::getAttributeWithTableName(Refund\Entity::CREATED_AT);

                $pid = Payment\Entity::getAttributeWithTableName(Payment\Entity::ID);
                $pbank = Payment\Entity::getAttributeWithTableName(Payment\Entity::BANK);
                $pgateway = Payment\Entity::getAttributeWithTableName(Payment\Entity::GATEWAY);

                $join->on($rPaymentId, '=', $pid)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($pbank, '=', $bank)
                     ->where($pgateway, '=', $gateway);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }
}
