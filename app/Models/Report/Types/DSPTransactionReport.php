<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Models\Transaction\FeeBreakup;

class DSPTransactionReport extends BasicEntityReport
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

    const BILLER_ID             = "Biller Id";
    const BANK_ID               = "Bank Id";
    const BANK_REF_NUMBER       = "Bank Ref. No.";
    const PGI_REF_NUMBER        = "PGI Ref. No.";
    const REF_1                 = "Ref. 1";
    const REF_2                 = "Ref. 2";
    const REF_3                 = "Ref. 3";
    const REF_4                 = "Ref. 4";
    const REF_5                 = "Ref. 5";
    const REF_6                 = "Ref. 6";
    const REF_7                 = "Ref. 7";
    const REF_8                 = "Ref. 8";
    const ACCOUNT_NUMBER        = "Account Number";
    const ACCOUNT_TYPES         = "Account Typs";
    const TRANSACTION_DATE      = "Date of Txn";
    const AMOUNT                = "Amount(Rs.Ps)";
    const STATUS                = "Status";
    const CREDIT_ACCOUNT_NUMBER = "CREDITACNO";




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

        foreach ($entities as $txn)
        {
            $row = [
                self::BILLER_ID             => 'DSPBMF',
                self::BANK_ID               => $this->getBank($txn),
                self::BANK_REF_NUMBER       => $this->getTxnBankReferenceNo($txn),
                self::PGI_REF_NUMBER        => $txn->source->getPublicId(),
                self::REF_1                 => 'NA',
                self::REF_2                 => 'NA',
                self::REF_3                 => 'NA',
                self::REF_4                 => 'NA',
                self::REF_5                 => 'NA',
                self::REF_6                 => 'NA',
                self::REF_7                 => 'NA',
                self::REF_8                 => 'NA',
                self::ACCOUNT_NUMBER        => 'NA',
                self::ACCOUNT_TYPES         => 'NA',
                self::TRANSACTION_DATE      => $this->getTxnDate($txn),
                self::AMOUNT                => $txn->getAmount(),
                self::STATUS                => 'NA',
                self::CREDIT_ACCOUNT_NUMBER => 'NA',
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

    protected function getBank($txn)
    {
        if ($txn->isTypePayment())
        {
            return $txn->source->getBank();
        }

        return $txn->source->payment->getBank();
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
                         ->format('d/m/Y H:i');

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
}
