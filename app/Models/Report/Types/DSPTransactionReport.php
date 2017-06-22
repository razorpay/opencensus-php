<?php

namespace RZP\Models\Report\Types;

use Mail;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Models\Transaction;
use RZP\Constants\Entity as E;
use RZP\Mail\Report\DSPReport as DSPMail;

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
    const SETTLED               = "Settled";

    protected $allowed = [
        E::TRANSACTION
    ];

    const BILLDESK = 'billdesk';

    /**
     * Report for DSP Blackrock Merchant
     * @param  array  $input ['day'         => 'yesterday/today',
     *                        'merchant_id' => 'Merchant Id'
     *                        'email'       => <for testing purpose only>]
     * @return [type]        [description]
     */
    public function getReport(array $input)
    {
        $merchantId = $input['merchant_id'];

        $this->setDefaults();

        $this->setMerchant($merchantId);

        $email = $input['email'] ?? $this->merchant->getEmail();

        $now = Carbon::now('Asia/Kolkata')->timestamp;

        $filename = $this->generateFilename($now);

        $fullpath = $this->writeDataToCsv($input, $filename);

        $reportingMail = new DSPMail($email, $fullpath);

        Mail::queue($reportingMail);

        return [
            'merchantId' => $merchantId,
            'email'      => $email,
            'file'       => $fullpath
        ];
    }

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
            $payment = $this->getPayment($txn);

            if ($payment->hasBeenCaptured() === false)
            {
                continue;
            }

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
                self::CREDIT_ACCOUNT_NUMBER => $this->getCreditAccountNumber($txn),
                self::SETTLED               => $txn->isSettled() ? 'True' : 'False'

            ];

            $data[] = $row;
        }

        return $data;
    }

    protected function getClientFields($txn)
    {
        $fields = [
            'ref_1' => 'NA',
            'ref_2' => 'NA',
            'ref_3' => 'NA',
            'ref_4' => 'NA',
        ];

        $payment = $this->getPayment($txn);

        $order = $payment->order;

        if ($order !== null)
        {
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

    protected function getPayment($txn)
    {
        $payment = null;

        switch (true)
        {
            case $txn->isTypePayment():
                $payment = $txn->source;
                break;

            case $txn->isTypeRefund():
                $payment = $txn->source->payment;
                break;
        }

        return $payment;
    }

    protected function getBank($txn)
    {
        $payment = $this->getPayment($txn);

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

    protected function getTxnDate($txn)
    {
        $ts = $txn->source->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
        $txnDate = Carbon::createFromTimestamp($ts, 'Asia/Kolkata')
                         ->format('d/m/Y H:i:s');

        return $txnDate;
    }

    protected function getTxnBankReferenceNo($txn)
    {
        $payment = $this->getPayment($txn);

        $bankTxnNumber = 'NA';

        if ($payment->isNetbanking() === true)
        {
            if ($payment->getGateway() === self::BILLDESK)
            {
                $bankTxnNumber = $payment->billdesk->getBankReferenceNo();
            }
            else if ($payment->getRelation('netbanking') !== null)
            {
                $bankTxnNumber = $payment->netbanking->getBankPaymentId();
            }
        }

        return $bankTxnNumber;
    }

    protected function getCreditAccountNumber($txn)
    {
        $bankAccountNumber = 'NA';

        if ($txn->isSettled() === true)
        {
            $bankAccountNumber = $txn->settlement->bankAccount->getAccountNumber();
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
