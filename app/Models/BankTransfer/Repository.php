<?php

namespace RZP\Models\BankTransfer;

use Carbon\Carbon;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Constants\Table;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Models\Merchant\Balance;

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

    /**
     * This will fetch all bank transfers in created state which are created in the last 24 hours
     * after 05-02-2022 and where transaction_id is null.
     * @param int $days
     * @param int $limit
     * @return mixed
     */
    public function fetchCreatedBankTransferAndTxnIdNullBetweenTimestamp(int $days, int $limit)
    {
        $currentTime = Carbon::now(Timezone::IST)->subMinutes(15)->subDays($days);
        $currentTimeStamp = $currentTime->getTimestamp();

        $lastTimestamp = $currentTime->subDay()->getTimestamp();
        $txnIdFillingTimestamp = Carbon::createFromFormat('d-m-Y', '05-02-2022', Timezone::IST)->getTimestamp();

        if ($lastTimestamp < $txnIdFillingTimestamp)
        {
            $lastTimestamp = $txnIdFillingTimestamp;
        }

        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $btTransactionIdColumn = $this->repo->bank_transfer->dbColumn(Entity::TRANSACTION_ID);
        $btStatusColumn        = $this->repo->bank_transfer->dbColumn(Entity::STATUS);
        $btBalanceIdColumn     = $this->repo->bank_transfer->dbColumn(Entity::BALANCE_ID);
        $btCreatedAtColumn     = $this->dbColumn(Entity::CREATED_AT);

        $btAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $btBalanceIdColumn)
                    ->select($btAttrs)
                    ->where($btStatusColumn, '=', Status::CREATED)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($btTransactionIdColumn)
                    ->whereBetween($btCreatedAtColumn, [$lastTimestamp, $currentTimeStamp])
                    ->limit($limit)
                    ->get();
    }

    /**
     * This will fetch all bank transfers in created state where id is in the given list of ids
     * and where transaction_id is null.
     * @param array $ids
     * @return mixed
     */
    public function fetchCreatedBankTransferWhereTxnIdNullAndIdsIn(array $ids)
    {
        $balanceIdColumn          = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $balanceTypeColumn        = $this->repo->balance->dbColumn(Balance\Entity::TYPE);
        $balanceAccountTypeColumn = $this->repo->balance->dbColumn(Balance\Entity::ACCOUNT_TYPE);

        $btIdColumn            = $this->repo->bank_transfer->dbColumn(Entity::ID);
        $btTransactionIdColumn = $this->repo->bank_transfer->dbColumn(Entity::TRANSACTION_ID);
        $btBalanceIdColumn     = $this->repo->bank_transfer->dbColumn(Entity::BALANCE_ID);

        $btAttrs = $this->dbColumn('*');

        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->join(Table::BALANCE, $balanceIdColumn, '=', $btBalanceIdColumn)
                    ->select($btAttrs)
                    ->where($balanceTypeColumn, '=', Balance\Type::BANKING)
                    ->where($balanceAccountTypeColumn, '=', Balance\AccountType::SHARED)
                    ->whereNull($btTransactionIdColumn)
                    ->whereIn($btIdColumn, $ids)
                    ->get();
    }

    public function addQueryParamBalanceType($query, $params)
    {
        $balance = Table::BALANCE;
        $balanceIdForeignColumn = $this->repo->balance->dbColumn(Balance\Entity::ID);
        $bankTransferBalanceIdColumn = $this->repo->bank_transfer->dbColumn(Entity::BALANCE_ID);

        return  $query->select(Table::BANK_TRANSFER . ".*")
                    ->join($balance, $balanceIdForeignColumn, '=', $bankTransferBalanceIdColumn)
                    ->where('balance.type', $params['balance_type']);
    }
}
