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

    const RPP_TXN_ID  = "RPP Transaction Id";
    const AMOUNT      = "Amount";
    const TXN_ID      = "Razorpay Payment Id";
    const TXN_DATE    = "Transaction Date";
    const FEES        = "Fees";
    const STATUS      = "Status";
    const DESCRIPTION = "Status Description";
    const MODE        = "Mode";
    const TYPE        = "Type";
    const BANK_NAME   = "Bank Name";
    const BANK_BID    = "Bank Ref No.";
    const CARD_TYPE   = "Card Type";
    const USERNAME    = "Customer Name";
    const USER_EMAIL  = "Customer Email";
    const USER_MOBILE = "Customer Mobile";

    protected function fetchFormattedDataForReport($entities): array
    {
        $data = [];

        $attemptedOrderIds = [];

        foreach ($entities as $payment)
        {
            $order = $payment->order;

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

    protected function createEntry(Payment\Entity $payment)
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

    protected function getFees(Payment\Entity $payment)
    {
        $order = $payment->order;

        $fees = $payment->getAmount() - $order->getAmount();

        return $fees/100;
    }

    protected function getPaymentStatus(Payment\Entity $payment)
    {
        $status = ($payment->hasBeenCaptured() === true) ? 'success' : 'failure';

        $statusDescription = $payment->getErrorDescription() ?? '';

        return [$status, $statusDescription];
    }

    protected function getType(Payment\Entity $payment)
    {
        $cardType = '';

        if ($payment->isCard())
        {
            $cardType = $payment->card->getNetwork();
        }

        return $cardType;
    }

    protected function getBank(Payment\Entity $payment)
    {
        $bank = null;

        if ($payment->isCard())
        {
            $bank = $payment->card->getIssuer();
        }
        else if ($payment->isNetbanking())
        {
            $bank = $payment->getBank();
        }

        return $bank;
    }

    protected function getPaymentDate(Payment\Entity $payment)
    {
        $ts = $payment->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
        $paymentDate = Carbon::createFromTimestamp($ts, 'Asia/Kolkata')
                         ->format('d/m/Y H:i:s');

        return $paymentDate;
    }

    protected function getTxnBankReferenceNo(Payment\Entity $payment)
    {
        $bankTxnNumber = $payment->getNetbankingReferenceId() ?? '';

        return $bankTxnNumber;
    }

    protected function getCardType(Payment\Entity $payment)
    {
        $cardType = '';

        if ($payment->isCard())
        {
            $cardType = ($payment->card->isInternational() === true) ? 'International' : 'National';
        }

        return $cardType;
    }

    protected function getUsername(Payment\Entity $payment)
    {
        $name = '';

        if ($payment->isCard())
        {
            $name = $payment->card->getFirstName();
        }

        return $name;
    }
}
