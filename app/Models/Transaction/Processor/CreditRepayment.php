<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Exception\BadRequestException;

class CreditRepayment extends Base
{
    public function updateTransaction()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setSettledAt($settledAt);
        $this->txn->setGatewayFee(0);
        $this->txn->setGatewayServiceTax(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(\RZP\Models\Transaction\ReconciledType::NA);

        $this->updatePostedDate();
    }

    public function setMerchantFeeDefaults()
    {
        return ;
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->txn->merchant->primaryBalance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    function calculateFees()
    {
        $amount = $this->source->getAmount();

        $this->debit = abs($amount);
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->validateMerchantBalance();

        parent::updateBalances($negativeLimit);
    }

    private function validateMerchantBalance()
    {
        $hasBalance = ($this->merchantBalance->getBalance() >= $this->debit);

        if ($hasBalance === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE,
                null,
                [
                    'debit_amount'  => $this->debit,
                    'balance_amount'=> $this->merchantBalance->getBalance()
                ]);
        }
    }
}
