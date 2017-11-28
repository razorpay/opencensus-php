<?php

namespace RZP\Models\BankTransfer;

use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANK_TRANSFER;

    const REFUND_ID = 'refund_id';

    protected $appFetchParamRules = [
        Entity::PAYMENT_ID         => 'sometimes|string|min:14|max:18',
        Entity::MERCHANT_ID        => 'sometimes|alpha_num|size:14',
        Entity::PAYER_ACCOUNT      => 'sometimes|string|max:20',
        Entity::PAYER_IFSC         => 'sometimes|string|max:15',
        Entity::PAYEE_ACCOUNT      => 'sometimes|string|max:20',
        Entity::PAYEE_IFSC         => 'sometimes|string|size:11',
        Entity::VIRTUAL_ACCOUNT_ID => 'sometimes|string|min:14|max:17',
        Entity::AMOUNT             => 'sometimes|integer',
        Entity::MODE               => 'sometimes|string|max:4',
        Entity::UTR                => 'sometimes|alpha_num|max:22',
        self::REFUND_ID            => 'sometimes|string|min:14|max:19',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
        self::REFUND_ID,
    ];

    protected function addQueryParamPayerIfsc($query, $params)
    {
        $ifsc = $params[Entity::PAYER_IFSC];

        $query->where(Entity::PAYER_IFSC, 'like', $ifsc.'%');
    }

    protected function addQueryParamRefundId($query, $params)
    {
        $paymentId   = $this->dbColumn(Entity::PAYMENT_ID);

        $refundPayId = $this->repo->refund->dbColumn(Refund\Entity::PAYMENT_ID);
        $refundId    = $this->repo->refund->dbColumn(Refund\Entity::ID);;

        $refundTable = $this->repo->refund->getTableName();

        $query->join($refundTable, $paymentId, '=', $refundPayId);

        $query->where($refundId, '=', $params[self::REFUND_ID]);

        $query->select($this->getTableName().'.*');
    }

    public function findByUtr(string $utr)
    {
        return $this->newQuery()
                    ->where(Entity::UTR, '=', $utr)
                    ->first();
    }

    public function findByPayment(Payment\Entity $payment)
    {
        return $this->findByPaymentId($payment->getId());
    }

    public function findByPaymentId(string $paymentId)
    {
        $bankTransfer = $this->newQuery()
                             ->where(Entity::PAYMENT_ID, '=', $paymentId)
                             ->with('payerBankAccount', 'virtualAccount')
                             ->firstOrFail();

        return $bankTransfer;
    }
}
