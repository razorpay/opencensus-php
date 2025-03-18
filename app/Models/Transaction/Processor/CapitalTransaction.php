<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Balance\Type;
use RZP\Exception\BadRequestException;

class CapitalTransaction extends Base
{
    public function setSourceDefaults()
    {
        parent::setSourceDefaults();

        $this->merchantBalance = $this->txn->source->balance;
    }

    public function fillDetails()
    {
        $this->txn->setAmount(abs($this->source->getAmount()));
    }

    public function calculateFees()
    {
        $amount = $this->source->getAmount();

        if ($amount > 0)
        {
            $this->credit = $amount;
        }

        if ($amount < 0)
        {
            $this->debit = abs($amount);
        }
    }

    public function updateTransaction()
    {
        $this->txn->setGatewayFee(0);
        $this->txn->setGatewayServiceTax(0);
        $this->txn->setApiFee(0);
        $this->txn->setReconciledAt(Carbon::now(Timezone::IST)->getTimestamp());
        $this->txn->setReconciledType(\RZP\Models\Transaction\ReconciledType::NA);

        $this->txn->setType($this->source->getType());

        $this->updatePostedDate();
    }

    public function setMerchantFeeDefaults()
    {
    }

    public function updateBalances(int $negativeLimit = 0)
    {
        $this->validateMerchantBalance();

        // if the balance update has already been validated,
        // then for whatever case the balance is going to be negative,
        // there is no limit to that. hence PHP_INT_MIN
        parent::updateBalances(PHP_INT_MIN);
    }

    public function setMerchantBalanceLockForUpdate()
    {
        $this->merchantBalance = $this->txn->source->balance;

        $this->repo->balance->lockForUpdateAndReload($this->merchantBalance);
    }

    private function validateMerchantBalance()
    {
        if ($this->merchantBalance->getType() === Type::PRINCIPAL)
        {
            // principal balance can go negative.
            return ;
        }

        // for interest & charge, balance can never go negative.
        if ($this->merchantBalance->getBalance() < $this->debit)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE,
                null,
                [
                    'debit_amount'  => $this->debit,
                    'balance_amount'=> $this->merchantBalance->getBalance(),
                    'balance_type'  => $this->merchantBalance->getType(),
                ]);
        }
    }
}
