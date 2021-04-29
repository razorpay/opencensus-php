<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_TPV;

    public function getApprovedActiveTpvAccountWithPayerAccountNumber($merchantId,
                                                                      $balanceId,
                                                                      $payerAccountNumber)
    {
        $merchantIdColumn = $this->repo->banking_account_tpv->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn = $this->repo->banking_account_tpv->dbColumn(Entity::BALANCE_ID);

        $trimmedPayerAccountNumberColumn =
            $this->repo->banking_account_tpv->dbColumn(Entity::TRIMMED_PAYER_ACCOUNT_NUMBER);

        $statusColumn = $this->repo->banking_account_tpv->dbColumn(Entity::STATUS);

        $isActiveColumn = $this->repo->banking_account_tpv->dbColumn(Entity::IS_ACTIVE);

        $trimmedPayerAccountNumber = ltrim($payerAccountNumber, '0');

        return $this->newQuery()
                    ->where($merchantIdColumn, $merchantId)
                    ->where($balanceIdColumn, $balanceId)
                    ->where($trimmedPayerAccountNumberColumn, $trimmedPayerAccountNumber)
                    ->where($statusColumn, Status::APPROVED)
                    ->where($isActiveColumn, 1)
                    ->first();
    }

    public function fetchMerchantTpvs(string $mid)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $mid)
                    ->orderBy(Entity::STATUS)
                    ->get();
    }

    public function fetchTpvOnMerchantBalanceAccountNumberIfsc(array $input)
    {
        $ifsc = substr($input[Entity::PAYER_IFSC], 0, 4);

        $trimmedPayerAccountNumber = ltrim($input[Entity::PAYER_ACCOUNT_NUMBER], '0');

        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $input[Entity::MERCHANT_ID])
                    ->where(Entity::BALANCE_ID, $input[Entity::BALANCE_ID])
                    ->where(Entity::TRIMMED_PAYER_ACCOUNT_NUMBER, $trimmedPayerAccountNumber)
                    ->where(Entity::PAYER_IFSC, 'like', $ifsc . '%')
                    ->first();
    }

}
