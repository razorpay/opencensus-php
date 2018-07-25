<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;

use RZP\Exception;
use RZP\Base\JitValidator;
use RZP\Models\Payment;
use RZP\Models\Transaction;
use RZP\Models\Transaction\FeeBreakup;
use RZP\Constants\Entity as E;
use RZP\Trace\TraceCode;

class BrokerTransactionReport extends BasicEntityReport
{
    // Maps the transaction source to the entities to be fetched for it
    protected $entityToRelationFetchMap = [
        E::TRANSACTION => [
            E::PAYMENT  => [
                E::NETBANKING,
                E::BILLDESK,
                E::ORDER
            ],
            E::REFUND   => [
                E::PAYMENT,
                E::PAYMENT . '.' . E::ORDER,
                E::PAYMENT . '.' . E::NETBANKING,
                E::PAYMENT . '.' . E::BILLDESK
            ]
        ]
    ];

    const MERCHANT_NAME     = 'Merchant Name';
    const MERCHANT_ID       = 'Merchant ID';
    const TXN_ID            = 'Txn Id';
    const TXN_STATE         = 'Txn State';
    const TXN_DATE          = 'Txn Date';
    const CLIENT_CODE       = 'Client Code';
    const MERCHANT_TXN_ID   = 'Merchant Txn Id';
    const PRODUCT           = 'Product';
    const DISCRIMINATOR     = 'Discriminator';
    const BANK_NAME         = 'Bank Name';
    const CARD_TYPE         = 'Card Type';
    const CARD_NUMBER       = 'Card No';
    const CARD_ISSUING_BANK = 'Card Issuing Bank';
    const BANK_REF_NO       = 'Bank Ref No';
    const GROSS_TXN_AMOUNT  = 'Gross Txn Amount';
    const TXN_CHARGES       = 'Txn Charges';
    const SERVICE_TAX       = 'Service Tax';
    const SB_CESS           = 'SB Cess';
    const KK_CESS           = 'Krishi Kalyan Cess';
    const TOTAL_CHARGEABLE  = 'Total Chargeable';
    const NET_AMOUNT        = 'Net Amount';
    const PAYMENT_STATUS    = 'Payment Status';
    const SETTLEMENT_DATE   = 'Settlement Date';
    const REFUND_REFERENCE  = 'Refund Reference';
    const REFUND_STATUS     = 'Refund Status';

    protected $allowed = [
        E::TRANSACTION
    ];

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        $entity = $this->entity;

        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForBrokerReport(
                        $merchantId,
                        $from,
                        $to,
                        $count,
                        $skip,
                        $this->relationsToFetch
        );
    }

    protected function fetchFormattedDataForReport($entities): array
    {
        $data = [];

        $name = $this->merchant->getName();
        $merchantId = $this->merchant->getId();

        foreach ($entities as $txn)
        {
            $feesBreakup = $this->getFeesBreakupDetails($txn);

            $merchantTxnId = $this->getMerchantTxnId($txn);

            $clientCode = $this->getClientCode($txn);

            $setlDate = null;
            if ($txn->isSettled())
            {
                $setlDate = $txn->getDateInFormat(Transaction\Entity::SETTLED_AT, 'Y-m-d');
            }

            $row = [
                self::MERCHANT_NAME      => $name,
                self::MERCHANT_ID        => $merchantId,
                self::TXN_ID             => $txn->source->getPublicId(),
                self::TXN_STATE          => $this->getTxnState($txn),
                self::TXN_DATE           => $this->getTxnDate($txn),
                self::CLIENT_CODE        => $clientCode,
                self::MERCHANT_TXN_ID    => $merchantTxnId,
                self::PRODUCT            => 'NSE',
                self::DISCRIMINATOR      => 'NB',
                self::BANK_NAME          => $this->getTxnBankName($txn),
                self::CARD_TYPE          => null,
                self::CARD_NUMBER        => null,
                self::CARD_ISSUING_BANK  => null,
                self::BANK_REF_NO        => $this->getTxnBankReferenceNo($txn),
                self::GROSS_TXN_AMOUNT   => ($txn->getAmount() / 100),
                self::TXN_CHARGES        => $feesBreakup['Txn Charges'],
                self::SERVICE_TAX        => $feesBreakup['Service Tax'],
                self::SB_CESS            => $feesBreakup['SB Cess'],
                self::KK_CESS            => $feesBreakup['Krishi Kalyan Cess'],
                self::TOTAL_CHARGEABLE   => $feesBreakup['Total Chargeable'],
                self::NET_AMOUNT         => ($txn->getAmount() / 100),
                self::PAYMENT_STATUS     => $this->getTxnPaymentStatus($txn),
                self::SETTLEMENT_DATE    => $setlDate,
                self::REFUND_REFERENCE   => null,
                self::REFUND_STATUS      => null,
            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function getMerchantTxnId($txn)
    {
        $merchantTxnId = null;

        if (($txn->isTypePayment()) and
            ($txn->source->getApiOrderId() !== null))
        {
            $merchantTxnId = $txn->source->order->getReceipt();
        }

        return $merchantTxnId;
    }

    protected function getClientCode($txn)
    {
        $clientCode = null;

        if ($txn->isTypePayment())
        {
            $notes = $txn->source->getNotes();

            if (isset($notes['clientid']) === true)
            {
                $clientCode = $notes['clientid'];
            }
        }

        return $clientCode;
    }

    protected function getTxnBankName($txn)
    {
        if ($txn->isTypePayment())
        {
            return $txn->source->getBankName();
        }

        return $txn->source->payment->getBankName();
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
        $txnDate = Carbon::createFromTimestamp($ts, Timezone::IST)
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
            return $payment->billdesk->getBankPaymentId();
        }
        else if ($payment->getRelation('netbanking') !== null)
        {
            return $payment->netbanking->getBankPaymentId();
        }
        else
        {
            return null;
        }
    }

    protected function getTxnPaymentStatus($txn)
    {
        if ($txn->isSettled())
        {
            return 'PAYMENT GIVEN';
        }
    }

    /**
     * For broker report we need to show the fees as 0.0 although actual fees
     * are applied to the txn amount
     */
    protected function getFeesBreakupDetails($txn): array
    {
        $fees = [
            'Txn Charges'        => 0,
            'Service Tax'        => 0,
            'SB Cess'            => 0,
            'Krishi Kalyan Cess' => 0,
            'Total Chargeable'   => 0,
        ];

        return $fees;
    }
}
