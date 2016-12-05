<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Error\ErrorCode;
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

    public function createForPayment($payment, $transfers)
    {
        $validator = new Validator;

        foreach ($transfers as $transfer)
        {
            $validator->validateInput('payment_transfer', $transfer);

            $from = $payment->merchant;

            if (isset($transfer['customer']) === true)
            {
                $to = $this->repo->customer->findByPublicIdAndMerchant($transfer['customer'], $this->merchant);

                $transfer = $this->createTransfer($to, $payment, $transfer['amount']);

                $customerTxn = (new Customer\Transactions\Core)
                                ->createFromCustomerCredit($payment, $transfer->transaction->getAmount(), $to);

                $this->repo->saveOrFail($customerTxn);
            }

        }
    }
}
