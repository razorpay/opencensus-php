<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Constants\Entity as E;

class RPPOrderReport extends BasicEntityReport
{
    // Maps the transaction source to the entities to be fetched for it
    protected $entityToRelationFetchMap = [
        E::ORDER => []
    ];

    protected $allowed = [
        E::ORDER
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
    const LAST4       = 'Last4';
    const CARD_TYPE   = 'Card Type';
    const USERNAME    = 'Customer Name';
    const USER_EMAIL  = 'Customer Email';
    const USER_MOBILE = 'Customer Mobile';

    // As per the requirement from RPP, the report should contain only one entry for each order
    // Case 1: Order is created, We add a row stating that the rzp payment is not created
    // Case 2: Order is attempted, we add a row with status as pending/failure
    //         2.a. order is authorized, status is pending
    //         2.b  order is not authorized, status is failure
    // Case 3: Order is paid, we add the payment only if its captured
    protected function fetchFormattedDataForReport($entities): array
    {
        $data = [];

        foreach ($entities as $order)
        {
            switch ($order->getStatus())
            {
                case Order\Status::CREATED:
                    $row = $this->createFailureEntry($order, 'pending', 'Razorpay Payment does not exists');
                    break;

                case Order\Status::ATTEMPTED:
                    $row = $this->createEntryForAttemptedOrder($order);
                    break;

                case Order\Status::PAID:
                    $row = $this->createEntryForPaidOrder($order);
                    break;
            }

            $data[] = $row;
        }

        return $data;
    }

    protected function createEntryForAttemptedOrder(Order\Entity $order): array
    {
        if ($order->isAuthorized() === true)
        {
            return $this->createFailureEntry($order, 'pending', 'Status pending from Gateway');
        }
        else
        {
            return $this->createFailureEntry($order, 'failure', 'Razorpay Payment failed');
        }
    }

    protected function createEntryForPaidOrder(Order\Entity $order): array
    {
        $payments = $order->payments;

        foreach ($payments as $payment)
        {
            if ($payment->hasBeenCaptured() === true)
            {
                return $this->createEntry($order, $payment);
            }
        }
    }

    protected function createEntry(Order\Entity $order, Payment\Entity $payment): array
    {
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
            self::LAST4       => $this->getLast4($payment),
            self::CARD_TYPE   => $this->getCardType($payment),
            self::USERNAME    => $this->getUsername($payment),
            self::USER_EMAIL  => $payment->getEmail(),
            self::USER_MOBILE => $payment->getContact(),
        ];

        return $row;
    }

    protected function createFailureEntry(Order\Entity $order, string $status, string $statusDescription): array
    {
         $row = [
            self::RPP_TXN_ID  => $order->getReceipt(),
            self::AMOUNT      => ($order->getAmount() / 100),
            self::TXN_ID      => '',
            self::TXN_DATE    => '',
            self::FEES        => '',
            self::STATUS      => $status,
            self::DESCRIPTION => $statusDescription,
            self::MODE        => '',
            self::TYPE        => '',
            self::BANK_NAME   => '',
            self::BANK_BID    => '',
            self::LAST4       => '',
            self::CARD_TYPE   => '',
            self::USERNAME    => '',
            self::USER_EMAIL  => '',
            self::USER_MOBILE => '',
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

    protected function getBank(Payment\Entity $payment)
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
        $paymentDate = Carbon::createFromTimestamp($ts, Timezone::IST)
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

    protected function getLast4(Payment\Entity $payment): string
    {
        $last4 = '';

        if ($payment->isCard() === true)
        {
            $last4 = $payment->card->getLast4();
        }

        return $last4;
    }
}
