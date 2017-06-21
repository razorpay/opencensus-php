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
            ],
            E::SETTLEMENT   => [],
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
    const SETTLED               = "settled";

    protected $allowed = [
        E::TRANSACTION
    ];

    const BILLDESK = 'billdesk';

    /**
     * Gets report data as array
     *
     * Not being used anywhere on dashboard
     * Keeping it to maintain backward compatibility
     *
     * @param $input array
     *        expected : 'day', 'month', 'year'
     * @return $data array
     */
    public function getReport(array $input)
    {
        $merchantId = $input['merchant_id'];

        $this->setDefaults();

        list($from, $to, $count, $skip) = $this->getParamsForReport($input);

        // currently limiting the api response can break the merchant integration
        // so overwriting the limits for now
        list($count, $skip) = [self::BATCH_LIMIT, 0];

        list($data, $count) = $this->getReportData($from, $to, $count, $skip, $merchantId);

        $fullpath = $this->createCsvFile($data, $merchantId, null, 'files/report');

        return $data;
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
                self::REF_1                 => $clientFields['ref_1'],
                self::REF_2                 => $clientFields['ref_2'],
                self::REF_3                 => $clientFields['ref_3'],
                self::REF_4                 => $clientFields['ref_4'],
                self::REF_5                 => 'NA',
                self::REF_6                 => 'NA',
                self::REF_7                 => 'NA',
                self::REF_8                 => 'NA',
                self::ACCOUNT_NUMBER        => 'NA',
                self::ACCOUNT_TYPES         => 'NA',
                self::TRANSACTION_DATE      => $this->getTxnDate($txn),
                self::AMOUNT                => $txn->getAmount(),
                self::STATUS                => 'SUCCESS',
                self::CREDIT_ACCOUNT_NUMBER => $this->getAccountNumber($txn),
                self::SETTLED               => $txn->isSettled()

            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function getClientFields($txn)
    {
        $fields = [
            'ref_1' => null,
            'ref_2' => null,
            'ref_3' => null,
            'ref_4' => null,
        ];

        if ($txn->isTypePayment())
        {
            $order = $txn->source->order;

            if ($order === null)
            {
                return $fields;
            }

            $notes = $order->getNotes();

            switch (true)
            {
                case (isset($notes->ref_1) === true):
                    $fields['ref_1'] = $notes->ref_1;

                case (isset($notes->ref_2) === true):
                    $fields['ref_2'] = $notes->ref_2;

                case (isset($notes->ref_3) === true):
                    $fields['ref_3'] = $notes->ref_3;

                case (isset($notes->ref_9) === true):
                    $fields['ref_4'] = $notes->ref_9;
            }
        }

        return $fields;
    }

    protected function getBank($txn)
    {
        $payment = null;
        $bank = null;

        switch (true)
        {
            case $txn->isTypePayment():
                $payment = $txn->source;
                break;

            case $txn->isTypeRefund():
                $payment = $txn->source->payment;
                break;

            default:
                break;
        }

        if ($payment !== null)
        {
            if ($payment->isCard())
            {
                $bank = $payment->card->getIssuer();
            }
            else
            {
                $bank = $payment->getBank();
            }
        }

        return $bank;
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

        $bankTxnNumber = null;

        if ($payment->getGateway() === self::BILLDESK)
        {
            $bankTxnNumber = $payment->billdesk->getBankReferenceNo();
        }
        else if ($payment->getRelation('netbanking') !== null)
        {
            $bankTxnNumber = $payment->netbanking->getBankPaymentId();
        }

        return $bankTxnNumber;
    }

    protected function getAccountNumber($txn)
    {
        $bankAccountNumber = null;

        if ($txn->isTypeSettlement())
        {
            $bankAccountNumber = $txn->source->bankAccount->getAccountNumber();
        }

        return $bankAccountNumber;
    }

    protected function getTimestamps($input): array
    {
        $day = $input['day'];

        $from = $to = null;

        if ($day === 'today')
        {
            $from = Carbon::now('Asia/Kolkata')
                          ->startOfDay()
                          ->timestamp;

            $to = Carbon::now('Asia/Kolkata')
                        ->timestamp;
        }
        else if ($day === 'yesterday')
        {
            $from = Carbon::now('Asia/Kolkata')
                           ->startOfDay()
                           ->subDay()
                           ->timestamp;

            $to = Carbon::now('Asia/Kolkata')
                        ->startOfDay()
                        ->timestamp - 1;


        }
        return [$from, $to];
    }
}
