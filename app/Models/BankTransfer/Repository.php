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
        Entity::REFUND_ID          => 'sometimes|string|min:14|max:19',
    ];

    protected $signedIds = [
        Entity::PAYMENT_ID,
        Entity::VIRTUAL_ACCOUNT_ID,
        Entity::REFUND_ID,
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
        $refundId    = $this->repo->refund->dbColumn(Refund\Entity::ID);

        $refundTable = $this->repo->refund->getTableName();

        $query->join($refundTable, $paymentId, '=', $refundPayId);

        $query->where($refundId, '=', $params[Entity::REFUND_ID]);

        $query->select($this->getTableName().'.*');
    }

    public function findByUtrAndPayerIfsc(string $utr, string $payerIfsc, bool $useWritePdo = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::UTR, '=', $utr)
                      ->where(Entity::PAYER_IFSC, '=', $payerIfsc);

        if ($useWritePdo === true)
        {
            $query->useWritePdo();
        }

        return $query->first();
    }

    public function findByUtrAndPayeeAccountAndAmount(string $utr, $payeeAccount, int $amount, bool $useWritePdo = false)
    {
        $payeeAccount = strtoupper(str_replace(' ', '', $payeeAccount));

        $query =  $this->newQuery()
                       ->where(Entity::UTR, '=', $utr)
                       ->where(Entity::PAYEE_ACCOUNT, '=', $payeeAccount)
                       ->where(Entity::AMOUNT, '=', $amount);

        if ($useWritePdo === true)
        {
            $query->useWritePdo();
        }

        return $query->first();
    }

    public function findByUtr(string $utr, bool $useWritePdo = false)
    {
        $query =  $this->newQuery()
                       ->where(Entity::UTR, '=', $utr);

        if ($useWritePdo === true)
        {
            $query->useWritePdo();
        }

        return $query->first();
    }

    public function findByNarration(string $narration, bool $useWritePdo = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::NARRATION, '=', $narration);

        if ($useWritePdo === true)
        {
            $query->useWritePdo();
        }

        return $query->first();
    }

    public function findByNarrationAndIfsc(string $narration, string $payerIfsc, bool $useWritePdo = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::NARRATION, '=', $narration)
                      ->where(Entity::PAYER_IFSC, '=', $payerIfsc);

        if ($useWritePdo === true)
        {
            $query->useWritePdo();
        }

        return $query->first();
    }

    public function findByPayment(Payment\Entity $payment)
    {
        return $this->findByPaymentId($payment->getId());
    }

    public function findByPaymentId(string $paymentId)
    {
        $bankTransfer = $this->newQuery()
                             ->where(Entity::PAYMENT_ID, '=', $paymentId)
                             ->with('payerBankAccount')
                             ->firstOrFail();

        return $bankTransfer;
    }
}
