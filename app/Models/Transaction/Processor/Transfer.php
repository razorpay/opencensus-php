<?php

namespace RZP\Models\Transaction\Processor;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Exception\LogicException;
use RZP\Models\Transaction\CreditType;
use RZP\Models\Transaction\ReconciledType;
use RZP\Trace\TraceCode;

class Transfer extends Base {

    function updateTransaction()
    {
        $this->trace->info(
            TraceCode::PAYMENT_TRANSFER_CREATE_TRANSACTION,
            [
                'transfer_id'            => $this->source->getId(),
                'transaction_id'        => $this->txn->getId(),
                'transaction_amount'    => $this->txn->getAmount(),
                'transaction_credit'    => $this->txn->getCredit(),
                'transaction_debit'     => $this->txn->getDebit(),
                'transaction_fees'      => $this->txn->getFee()
            ]);

        $this->repo->saveOrFail($this->txn);
    }

    function calculateFees()
    {
        throw new LogicException('Not implemented', ErrorCode::SERVER_ERROR_LOGICAL_ERROR);
    }

    public function calculateFeesForDualWrite($fees, $tax, $feeCreditsUsed, $amountCreditsUsed, $refundCreditsUed)
    {
        if ($amountCreditsUsed)
        {
            // fail the transaction if the amount credits are less than the transaction amount.
            // This is done to ensure we don't incorrectly mark fee, tax on the transactions
            if ($this->amountCredits < $this->txn->getAmount())
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE);
            }

            $this->calculateFeeForAmountCredit();
        }
        else if ($feeCreditsUsed)
        {
            // fail the transaction if the amount credits are less than the transaction amount.
            // This is done to ensure we don't incorrectly mark fee, tax on the transactions
            if ($this->feeCredits < $fees)
            {
                throw new BadRequestException(ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE);
            }

            $this->calculateFeeForFeeCredit();
        }
        else
        {
            $this->calculateFeeDefault();
        }


        $amount = $this->getNetAmount();

        $this->credit = 0;
        $this->debit  = 0;

        if ($amount > 0)
        {
            $this->credit = $amount;
        }
        else
        {
            $this->debit = -1 * $amount;
        }

        $this->trace->debug(TraceCode::CALCULATED_FEES_FOR_TRANSFER,
            [
                'credit'            => $this->credit,
                'debit'             => $this->debit,
                'amount'            => $amount,
                'fee'               => $this->fees,
                'fee_credits'       => $this->feeCredits,
                'amount_credits'    => $this->amountCredits
            ]
        );
    }

    public function getNetAmount()
    {
        $amount = $this->txn->getAmount();

        $netAmount = -1 * $amount;

        switch (true)
        {
            case ($this->txn->isPostpaid() === true):
            case ($this->txn->getCreditType() === CreditType::FEE):
            case ($this->txn->getCreditType() === CreditType::AMOUNT):
                break;

            default:
                $netAmount = -1 * ($amount + $this->fees);
        }

        $this->trace->debug(TraceCode::NET_AMOUNT_FOR_TRANSACTION,
            [
                'credit'        => $this->credit,
                'debit'         => $this->debit,
                'amount'        => $amount,
                'fee'           => $this->fees,
                'net_amount'    => $netAmount,
            ]
        );

        return $netAmount;
    }

    public function setOtherDetails()
    {
        $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

        $this->txn->setReconciledAt(time());

        $this->txn->setReconciledType(ReconciledType::NA);

        $this->txn->setSettledAt($settledAt);

        $this->txn->setGatewayFee(0);

        $this->txn->setApiFee($this->fees);

        parent::setOtherDetails();
    }
}
