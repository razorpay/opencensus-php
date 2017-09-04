<?php

namespace RZP\Models\Report\Types;

use Mail;
use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\FileStore;
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

    protected $transactionCount = 0;

    protected $transactionVolume = 0;

    public function getReport(array $input)
    {
        $this->setDefaults();

        $now = Carbon::now()->getTimestamp();

        $filename = $this->generateFilename($now) . '_' . strtoupper($this->mode);

        $fullpath = $this->writeDataToCsv($input, $filename);

        $s3File = $this->createFileAndSave($fullpath, $filename);

        $this->unlinkFile($fullpath);

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($s3File);

        $shouldSendMail = false;

        if (isset($input['mail']) === true)
        {
            $shouldSendMail = ($input['mail'] === '1');
        }

        if ($shouldSendMail === true)
        {
            $filenameWithExtension = $filename . '.csv';

            $data = $this->createMailData($filenameWithExtension, $signedUrl, $input);

            $reportingMail = new DSPMail($data);

            Mail::queue($reportingMail);
        }

        return [ 'url' => $signedUrl ];
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
                self::AMOUNT                => ($txn->getAmount() / 100),
                self::STATUS                => 'SUCCESS',
                self::CREDIT_ACCOUNT_NUMBER => $this->getCreditAccountNumber($txn)
            ];

            $data[] = $row;

            $this->transactionVolume += ($txn->getAmount() / 100);
        }

        if (count($data) === 0)
        {
            $data[] = $this->createDefaultFile();
        }
        else
        {
            $this->transactionCount = count($data);
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
        $txnDate = Carbon::createFromTimestamp($ts, Timezone::IST)
                         ->format('d/m/Y H:i:s');

        return $txnDate;
    }

    protected function getTxnBankReferenceNo($txn)
    {
        $payment = $this->getPayment($txn);

        $bankTxnNumber = $payment->getNetbankingReferenceId() ?? 'NA';

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
        $from = $to = null;

        // If day is set, `from` and `to` are of that day start and end only.
        // If day is not set, month should be set. `from` and `to` will be
        // the first day and the last day of the month.
        if (isset($input['day']) === true)
        {
            // this is needed for cron input as we can't pass the exact day from
            // cron
            if ($input['day'] === 'today')
            {
                $date = Carbon::today(Timezone::IST)->startOfDay();
            }
            else if ($input['day'] === 'yesterday')
            {
                $date = Carbon::yesterday(Timezone::IST)->startOfDay();
            }
            else
            {
                $this->validateInput($input);

                $day = (int) $input['day'];
                $month = (int) $input['month'];
                $year = (int) $input['year'];

                $date = Carbon::createFromDate($year, $month, $day, Timezone::IST)
                              ->startOfDay();
            }

            $from = $date->getTimestamp();
            $to = $date->addDay()->getTimestamp() - 1;
        }
        else if (isset($input['month']) === true)
        {
            $this->validateInput($input);

            $month = (int) $input['month'];
            $year = (int) $input['year'];

            assertTrue($month > 0);
            assertTrue($month <= 12);

            $from = Carbon::createFromDate($year, $month, 1, Timezone::IST)
                          ->startOfDay()
                          ->getTimestamp();

            $to = Carbon::createFromDate($year, $month, 1, Timezone::IST)
                        ->endOfMonth()
                        ->getTimestamp();
        }
        else
        {
            $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;

            if (isset($input['from']) === true)
            {
                $from = $input['from'];
            }

            if (isset($input['to']) === true)
            {
                $to = $input['to'];
            }
        }

        return [$from, $to];
    }

    protected function createDefaultFile()
    {
        return [
            self::BILLER_ID             => '',
            self::BANK_ID               => '',
            self::BANK_REF_NUMBER       => '',
            self::PGI_REF_NUMBER        => '',
            self::REF_1                 => '',
            self::REF_2                 => '',
            self::REF_3                 => '',
            self::REF_4                 => '',
            self::REF_5                 => '',
            self::REF_6                 => '',
            self::REF_7                 => '',
            self::REF_8                 => '',
            self::ACCOUNT_NUMBER        => '',
            self::ACCOUNT_TYPES         => '',
            self::TRANSACTION_DATE      => '',
            self::AMOUNT                => '',
            self::STATUS                => '',
            self::CREDIT_ACCOUNT_NUMBER => ''
        ];
    }
    protected function createMailData($filename, $signedUrl, $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        $fdate = Carbon::createFromTimestamp($from, Timezone::IST)->format('Y-m-d');

        $tdate = Carbon::createFromTimestamp($to, Timezone::IST)->format('Y-m-d');

        $fdateTime = Carbon::createFromTimestamp($from, Timezone::IST)->format('Y-m-d H:i');

        $tdateTime = Carbon::createFromTimestamp($to, Timezone::IST)->format('Y-m-d H:i');

        if ((isset($input['day']) === true) and
            ($input['day'] === 'today'))
        {
            // $from and $to would be 00:00 to 23:59. In the message body we need to send time as 15:00
            $tdateTime = Carbon::create(null, null, null, 15, 00, 00, Timezone::IST)->format('Y-m-d H:i');
        }

        $data = [
            'subject'    => 'Razorpay recon report - ' . $fdate .' to ' . $tdate,
            'body'       => 'Razorpay recon report (Mode: ' . strtoupper($this->mode) .') From ' . $fdateTime .' To ' . $tdateTime
                                . '<br>Total Transaction Count = ' .$this->transactionCount
                                . '<br>Total Transaction Volume (INR) = ' .$this->transactionVolume,
            'signed_url' => $signedUrl,
            'filename'   => $filename,
            'emails'     => $input['email']
        ];

        return $data;
    }
}
