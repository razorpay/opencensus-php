<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;
use RZP\Models\Customer;

class Core extends Base\Core
{
    /**
     * Creates and saves a new transfer entity
     *
     * @param  Base\Entity        $from        Source entity for transfer
     * @param  Base\Entity        $to          Recieving entity for transfer
     * @param  Transaction\Entity $transaction Transaction Entity
     * @return Transfer\Entity
     */
    public function createTransfer(Base\Entity $to, $source, $amount)
    {
        $transferData = [
            Entity::TO_ID           => $to->getId(),
            Entity::TO_TYPE         => $to->getEntityName(),
            Entity::SOURCE_ID       => $source->getId(),
            Entity::SOURCE_TYPE     => $source->getEntityName(),
            Entity::AMOUNT          => $amount,
        ];

        $transfer = (new Entity)->build($transferData);

        $transfer->generateId();

        $transfer->merchant()->associate($source->merchant);

        $txn = (new Transaction\Core)->createFromTransfer($transfer, $to);

        $this->repo->saveOrFail($txn);

        $transfer->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        return $transfer;
    }

    public function createForPayment($payment, $input)
    {
        $transfers = new Base\PublicCollection;

        $this->validateTransfers($payment, $input);

        foreach ($input as $transfer)
        {
            if (isset($transfer[ToType::CUSTOMER]) === true)
            {
                $transfer = $this->customerTransfer($payment, $transfer);

                $transfers->push($transfer);
            }
        }

        return $transfers;
    }

    protected function customerTransfer($payment, $transfer)
    {
        $to = $this->repo->customer
                   ->findByPublicIdAndMerchant($transfer['customer'], $this->merchant);

        $transfer = $this->createTransfer($to, $payment, $transfer['amount']);

        $customerTxn = (new Customer\Transaction\Core)
                        ->createFromCustomerCredit($payment, $transfer->transaction->getAmount(), $to);

        $this->repo->saveOrFail($customerTxn);

        return $transfer;
    }

    protected function merchantTransfer()
    {
        ;
    }

    protected function validateTransfers($payment, $transfers)
    {
        // For now -
        // 1. Sum of transfers cant be greater than the capture amount
        // 2. Sum of transfers should be greater than merchant balance

        $transferSum = 0;

        foreach ($transfers as $transfer)
        {
            $amount = $transfer['amount'];

            $transferSum += $transfer['amount'];
        }

        $traceData = [
            'payment_id' => $payment->getId(),
            'transfers'  => $transfers
        ];

        if ($transferSum > $payment->getAmount())
        {
            $this->failValidationForTotalSum($traceData);
        }

        $balance = $this->repo->balance->getMerchantBalance($this->merchant);

        if ($transferSum > $balance->getBalance())
        {
            $this->failValdationForMerchantBalance($traceData);
        }
    }

    protected function failValidationForTotalSum($traceData)
    {
        $this->trace->info(TraceCode::PAYMENT_TRANSFER_VALIDATION_FAILED, $traceData);

        throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED);
    }

    protected function failValdationForMerchantBalance($traceData)
    {
        $this->trace->info(TraceCode::PAYMENT_TRANSFER_VALIDATION_FAILED, $traceData);

        throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_NOT_ENOUGH_BALANCE);
    }

}
