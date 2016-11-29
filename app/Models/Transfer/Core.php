<?php

namespace RZP\Models\Transfer;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Transfer;
use RZP\Models\Transaction;

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

        $txn = (new Transaction\Core)->createFromTransfer($transfer);

        $transfer->transaction()->associate($txn);

        $this->repo->saveOrFail($transfer);

        $this->repo->saveOrFail($txn);

        return $transfer;
    }

    public function createForPayment($payment, $transfers)
    {
        foreach ($transfers as $transfer)
        {
            $this->getValidator()->validateInput('payment_transfer', $transfer);

            $from = $payment->merchant;

            if (isset($transfer['customer']) === true)
            {
                $customerId = Customer\Entity::verifyIdAndStripSign($transfer['customer']);

                if ($customerId !== $payment->getCustomerId())
                {
                    throw new Exception\BadRequestValidationFailureException(
                        'invalid customer id for payment');
                }

                $to = $payment->localCustomer;

                $transfer = $this->createTransfer($to, $payment, $transfer['amount']);
            }


        }
    }
}
