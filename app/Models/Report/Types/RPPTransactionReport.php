<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Constants\Entity as E;

class RPPTransactionReport extends BasicEntityReport
{
    // Maps the transaction source to the entities to be fetched for it
    protected $entityToRelationFetchMap = [
        E::PAYMENT  => [
            E::NETBANKING,
            E::BILLDESK,
            E::ORDER
        ],
    ];

    protected $allowed = [
        E::PAYMENT
    ];

    const RPP_TXN_ID  = 'RPP Transaction Id';
    const AMOUNT      = 'Amount';
    const TXN_ID      = 'Razorpay Payment Id';
    const TXN_DATE    = 'Transaction Date';
    const FEES        = 'Fees';
    const STATUS      = 'Status';
    const DESCRIPTION = 'Status Description';
    const MODE        = 'Mode';
    const TYPE        = 'Type';
    const BANK_NAME   = 'Bank Name';
    const BANK_BID    = 'Bank Ref No.';
    const CARD_TYPE   = 'Card Type';
    const USERNAME    = 'Customer Name';
    const USER_EMAIL  = 'Customer Email';
    const USER_MOBILE = 'Customer Mobile';

    // As per the requirement from RPP, the report should contain only one entry for each order
    // Case 1: Order is paid, we add the payment only if its captured
    // Case 2: Order is attempeted, then we add the first payment entity for that order.
    //         A list of $attemptedOrderIds is maintained to make sure only 1 payment is added for that order
    // Case 3: Order is created, we do not add anything as payment won't be created.
    protected function fetchFormattedDataForReport(array $entities): array
    {
        $data = [];

        $attemptedOrderIds = [];

        foreach ($entities as $payment)
        {
            $order = $payment->order;

            // Just an addtional check. RPP is on orders api via hosted
            if ($order === null)
            {
                continue;
            }

            if ($order->isPaid() === true)
            {
                if ($payment->hasBeenCaptured() === true)
                {
                    $data[] = $this->createEntry($payment);
                }
            }
            else
            {
                if (in_array($order->getId(), $attemptedOrderIds, true) === false)
                {
                    $data[] = $this->createEntry($payment);

                    $attemptedOrderIds[] = $order->getId();
                }
            }
        }

        return $data;
    }

    protected function createEntry(Payment\Entity $payment): array
    {
        $order = $payment->order;

        list($status, $statusDescription) = $this->getPaymentStatus($payment);

        $row = [
            self::RPP_TXN_ID  => $order->getReceipt(),
            self::AMOUNT      => ($order->getAmount() / 100),
            self::TXN_ID      => $payment->getPublicId(),
            self::TXN_DATE    => $this->getPaymentDate($payment),
            self::FEES        => $this->getFees($payment),
            self::STATUS      => $status,
            self::DESCRIPTION => $statusDescription,
            self::MODE        => $payment->getMethod(),
            self::TYPE        => $this->getType($payment),
            self::BANK_NAME   => $this->getBank($payment),
            self::BANK_BID    => $this->getTxnBankReferenceNo($payment),
            self::CARD_TYPE   => $this->getCardType($payment),
            self::USERNAME    => $this->getUsername($payment),
            self::USER_EMAIL  => $payment->getEmail(),
            self::USER_MOBILE => $payment->getContact(),
        ];

        return $row;
    }

    protected function getFees(Payment\Entity $payment): float
    {
        $order = $payment->order;

        $fees = $payment->getAmount() - $order->getAmount();

        return $fees/100;
    }

    protected function getPaymentStatus(Payment\Entity $payment): array
    {
        $status = ($payment->hasBeenCaptured() === true) ? 'success' : 'failure';

        $statusDescription = $payment->getErrorDescription() ?? '';

        return [$status, $statusDescription];
    }

    protected function getType(Payment\Entity $payment): string
    {
        $cardType = '';

        if ($payment->isCard() === true)
        {
            $cardType = $payment->card->getNetwork();
        }

        return $cardType;
    }

    protected function getBank(Payment\Entity $payment): string
    {
        $bank = null;

        if ($payment->isCard() === true)
        {
            $bank = $payment->card->getIssuer();
        }
        else if ($payment->isNetbanking() === true)
        {
            $bank = $payment->getBank();
        }

        return $bank;
    }

    protected function getPaymentDate(Payment\Entity $payment): string
    {
        $ts = $payment->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
        $paymentDate = Carbon::createFromTimestamp($ts, 'Asia/Kolkata')
                         ->format('d/m/Y H:i:s');

        return $paymentDate;
    }

    protected function getTxnBankReferenceNo(Payment\Entity $payment): string
    {
        $bankTxnNumber = $payment->getNetbankingReferenceId() ?? '';

        return $bankTxnNumber;
    }

    protected function getCardType(Payment\Entity $payment): string
    {
        $cardType = '';

        if ($payment->isCard() === true)
        {
            $cardType = ($payment->card->isInternational() === true) ? 'International' : 'National';
        }

        return $cardType;
    }

    protected function getUsername(Payment\Entity $payment): string
    {
        $name = '';

        if ($payment->isCard() === true)
        {
            $name = $payment->card->getFirstName();
        }

        return $name;
    }
}
