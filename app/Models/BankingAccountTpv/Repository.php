<?php

namespace RZP\Models\BankingAccountTpv;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::BANKING_ACCOUNT_TPV;

    public function getApprovedActiveTpvAccountWithPayerAccountNumberAndIfscFirstFour($merchantId,
                                                                                      $balanceId,
                                                                                      $payerAccountNumber,
                                                                                      $payerBankCode)
    {
        $merchantIdColumn = $this->repo->banking_account_tpv->dbColumn(Entity::MERCHANT_ID);

        $balanceIdColumn = $this->repo->banking_account_tpv->dbColumn(Entity::BALANCE_ID);

        $payerAccountNumberColumn = $this->repo->banking_account_tpv->dbColumn(Entity::PAYER_ACCOUNT_NUMBER);

        $payerIfscColumn = $this->repo->banking_account_tpv->dbColumn(Entity::PAYER_IFSC);

        $statusColumn = $this->repo->banking_account_tpv->dbColumn(Entity::STATUS);

        $isActiveColumn = $this->repo->banking_account_tpv->dbColumn(Entity::IS_ACTIVE);

        return $this->newQuery()
                    ->where($merchantIdColumn, $merchantId)
                    ->where($balanceIdColumn, $balanceId)
                    ->where($payerAccountNumberColumn, $payerAccountNumber)
                    ->where($payerIfscColumn, 'like', $payerBankCode . '%')
                    ->where($statusColumn, Status::APPROVED)
                    ->where($isActiveColumn, 1)
                    ->first();
    }
}
