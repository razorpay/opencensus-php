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

    protected $allowed = [
        E::TRANSACTION
    ];

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        $entity = $this->entity;

        $repo = $this->repo->$entity;

        return $repo->fetchEntitiesForDSPReport(
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
            $clientFields = $this->getClientFields($txn);

            $row = [
                self::BILLER_ID             => 'DSPBMF',
                self::BANK_ID               => $this->getBank($txn),
                self::BANK_REF_NUMBER       => $this->getTxnBankReferenceNo($txn),
                self::PGI_REF_NUMBER        => $txn->source->getPublicId(),
                self::REF_1                 => $clientFields['ref1'],
                self::REF_2                 => $clientFields['ref2'],
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
                self::STATUS                => 'SUCCESS',
                self::CREDIT_ACCOUNT_NUMBER => 'NA',
            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function getClientFields($txn)
    {
        $fields = [
            'ref1' => null,
            'ref2' => null,
        ];

        if ($txn->isTypePayment())
        {
            $notes = $txn->source->order->getNotes();

            switch (true)
            {
                case (isset($notes->field1) === true):
                    $fields['ref1'] = $notes->field1;

                case (isset($notes->field2) === true):
                    $fields['ref2'] = $notes->field2;
            }
        }

        return $fields;
    }

    protected function getBank($txn)
    {
        if ($txn->isTypePayment())
        {
            return $txn->source->getBank();
        }

        return $txn->source->payment->getBank();
    }

    protected function getTxnDate($txn)
    {
        $ts = $txn->source->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
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
}
