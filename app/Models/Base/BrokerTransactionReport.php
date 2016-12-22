<?php

namespace RZP\Models\Base;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Base\JitValidator;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Models\Transaction\FeeBreakup;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class BrokerTransactionReport extends Base\Report
{
    protected $allowed = [
        E::TRANSACTION
    ];

    protected function fetchEntitiesForReport($merchantId, $from, $to, $entity)
    {
        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForBrokerReport($merchantId, $from, $to);
    }

    protected function fetchFormattedDataForReport($entities)
    {
        $data = [];

        $name = $this->merchant->getName();
        $merchantId = $this->merchant->getId();

        foreach ($entities as $txn)
        {
            $feesBreakup = $this->getFeesBreakupDetails($txn);

            $merchantTxnId = null;

            if (($txn->isTypePayment()) and
                ($txn->source->getApiOrderId() !== null))
            {
                $merchantTxnId = $txn->source->order->getReceipt();
            }

            $clientCode = null;

            if ($txn->isTypePayment())
            {
                $notes = $txn->source->getNotes();
                if (isset($notes['clientid']))
                {
                    $clientCode = $notes['clientid'];
                }
            }

            $setlDate = null;
            if ($txn->isSettled())
            {
                $setlDate = $txn->getDateInFormat(Transaction\Entity::SETTLED_AT, 'Y-m-d');
            }

            $row = [
                'Merchant Name'      => $name,
                'Merchant ID'        => $merchantId,
                'Txn Id'             => $txn->source->getPublicId(),
                'Txn State'          => $this->getTxnState($txn),
                'Txn Date'           => $this->getTxnDate($txn),
                'Client Code'        => $clientCode,
                'Merchant Txn Id'    => $merchantTxnId,
                'Product'            => 'NSE',
                'Discriminator'      => 'NB',
                'Bank Name'          => $this->getTxnBankName($txn),
                'Card Type'          => null,
                'Card No'            => null,
                'Card Issuing Bank'  => null,
                'Bank Ref No'        => $this->getTxnBankReferenceNo($txn),
                'Gross Txn Amount'   => ($txn->getAmount() / 100),
                'Txn Charges'        => $feesBreakup['Txn Charges'],
                'Service Tax'        => $feesBreakup['Service Tax'],
                'SB Cess'            => $feesBreakup['SB Cess'],
                'Krishi Kalyan Cess' => $feesBreakup['Krishi Kalyan Cess'],
                'Total Chargeable'   => $feesBreakup['Total Chargeable'],
                'Net Amount'         => $this->getTxnNetAmount($txn),
                'Payment Status'     => $this->getTxnPaymentStatus($txn),
                'Settlement Date'    => $setlDate,
                'Refund Reference'   => null,      // TBD Need to decide whats to be shown here,
                'Refund Status'      => null,
            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function getTxnBankName($txn)
    {
        if ($txn->isTypePayment())
        {
            return $txn->source->getBankName();
        }
        else
        {
            return $txn->source->payment->getBankName();
        }
    }

    protected function getCardType($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getType();
        }

        return null;
    }

    protected function getCardNumber($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getFormatted();
        }

        return null;
    }

    protected function getCardIssuer($methodDetails)
    {
        $card = $methodDetails[Payment\Method::CARD] ?? null;

        if ($card !== null)
        {
            return $card->getIssuer();
        }

        return null;
    }

    protected function getTxnNetAmount($txn)
    {
        if ($txn->isTypeRefund())
        {
            return ($txn->getDebit() / 100);
        }
        elseif ($txn->isTypePayment())
        {
            return ($txn->getCredit() / 100);
        }
    }

    protected function getTxnState($txn)
    {
        if ($txn->isTypePayment())
        {
            return 'Sale';
        }
        elseif ($txn->isTypeRefund())
        {
            return 'Refund';
        }
        else
        {
            return null;
        }
    }

    protected function getTxnDate($txn)
    {
        $ts = $txn->source->getCreatedAt();

        // Format yyyy-mm-dd hh:mm,
        // hh is in 24 hrs
        $txnDate = Carbon::createFromTimestamp($ts, 'Asia/Kolkata')
                         ->format('Y-m-d H:i');

        return $txnDate;
    }

    protected function getTxnBankReferenceNo($txn)
    {
        if ($txn->isTypePayment() === false)
        {
            return null;
        }

        $payment = $txn->source;

        if ($payment->getGateway() === 'billdesk')
        {
            return $payment->billdesk->getBankReferenceNo();
        }
        else if ($payment->getRelation('netbanking') !== null)
        {
            return $payment->netbanking->getBankPaymentId();
        }
        else
        {
            throw new Exception\LogicException('Should not reach here');
        }
    }

    protected function getTxnPaymentStatus($txn)
    {
        if ($txn->isSettled())
        {
            return 'PAYMENT GIVEN';
        }
    }

    protected function getFeesBreakupDetails($txn)
    {
        $fees = [];

        if ($txn->isTypePayment() === false)
        {
            $fees = [
                'Txn Charges'        => '0.0',
                'Service Tax'        => '0.0',
                'SB Cess'            => '0.0',
                'Krishi Kalyan Cess' => '0.0',
                'Total Chargeable'   => '0.0',
            ];

            return $fees;
        }

        $feesBreakup = $txn->feesBreakup;

        $feesBreakupDetails = $feesBreakup->flatMap(function ($fee)
        {
            return [$fee->getName() => $fee->getAmount()];
        });

        $feesBreakupDetails = $feesBreakupDetails->toArray();

        $fees = [
            'Txn Charges'        => ($feesBreakupDetails[FeeBreakup\Name::PAYMENT] ?? 0) / 100,
            'Service Tax'        => ($feesBreakupDetails[FeeBreakup\Name::SERVICE_TAX] ?? 0) / 100,
            'SB Cess'            => ($feesBreakupDetails[FeeBreakup\Name::SWACHH_BHARAT_CESS] ?? 0) / 100,
            'Krishi Kalyan Cess' => ($feesBreakupDetails[FeeBreakup\Name::KRISHI_KALYAN_CESS] ?? 0) / 100,
            'Total Chargeable'   => ($txn->getFee() / 100),
        ];

        return $fees;
    }
}
