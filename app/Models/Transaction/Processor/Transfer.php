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
