<?php

namespace Models\Payment\Refund;

use EE\Exception;
use Models\Base;
use Models\Payment;
use Models\Payment\Refund;
use Constants\Table;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'refund';

    protected $proxyFetchParamRules = [
        Entity::NOTES           => 'sometimes|string|max:500',
    ];

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID     => 'sometimes|alpha_num',
        Entity::PAYMENT_ID      => 'sometimes|alpha_num',
        Entity::TRANSACTION_ID  => 'sometimes|alpha_num',
        Entity::NOTES           => 'sometimes|string|max:500',
    );

    protected $esWhitelistedParams = [
        Entity::NOTES
    ];

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

    public function fetchEntitiesForReport($merchantId, $from, $to)
    {
        return $this->fetchBetweenTimestampWithRelations(
                        $merchantId, $from, $to, ['payment']);
    }

    public function fetchRefundsWithoutTransactions()
    {
        return $this->newQuery()
                    ->join(
                            Table::PAYMENT,
                            Table::REFUND . '.' . Refund\Entity::PAYMENT_ID,
                            '=',
                            Table::PAYMENT . '.' . Payment\Entity::ID)
                    ->whereNull(Refund\Entity::TRANSACTION_ID)
                    ->whereNotNull(Table::PAYMENT . '.' . Payment\Entity::TRANSACTION_ID)
                    ->with('payment')
                    ->get();
    }

    public function fetchRefundsForGatewayBetweenTimestamps($type, $gatewayCode, $from, $to, $gateway)
    {
        $repo = $this->repo;

        $ptable = Payment\Entity::getTableName();

        $attrs = Refund\Entity::getTableName() . '.*';

        $query = $this->newQuery();

        $refunds = $query->select($attrs)->join(
            $ptable,
            function ($join) use ($from, $to, $type, $gatewayCode, $gateway)
            {
                $rPaymentId = Refund\Entity::getAttributeWithTableName(Refund\Entity::PAYMENT_ID);
                $rCreatedAt = Refund\Entity::getAttributeWithTableName(Refund\Entity::CREATED_AT);

                $pid = Payment\Entity::getAttributeWithTableName(Payment\Entity::ID);
                $ptype = Payment\Entity::getAttributeWithTableName($type);
                $pgateway = Payment\Entity::getAttributeWithTableName(Payment\Entity::GATEWAY);

                $join->on($rPaymentId, '=', $pid)
                     ->where($rCreatedAt, '>=', $from)
                     ->where($rCreatedAt, '<=', $to)
                     ->where($ptype, '=', $gatewayCode)
                     ->where($pgateway, '=', $gateway);
            })
            ->with('payment')
            ->get();

        return $refunds;
    }
}
