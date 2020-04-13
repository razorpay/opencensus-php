<?php

namespace RZP\Models\Report\Types;

use Mail;
use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Payment\Refund;
use RZP\Constants\Entity as E;
use RZP\Mail\Report\IrctcRefundReport as IrctcMail;

class IrctcRefundReport extends BasicEntityReport
{
    const BATCH_LIMIT = 100000;

    // Maps the transaction source to the entities to be fetched for it
    protected $entityToRelationFetchMap = [
        E::REFUND => [
            E::PAYMENT
        ]
    ];

    protected $allowed = [
        E::REFUND
    ];

    const MERCHANT_REFERENCE = 'Merchant Transaction Id';
    const PAYMENT_DATE       = 'Payment Date';
    const PAYMENT_ID         = 'Payment Id';
    const REFUND_AMOUNT      = 'Refund Amount';
    const REFUND_STATUS      = 'Refund Status';
    const REFUND_REMARKS     = 'Refund Remarks';
    const REFUND_DATE        = 'Refund Date';
    const REFUND_ID          = 'Refund Id';

    const FILE_PREFIX = [
        '8ST00QgEPT14cE' => 'deltarefund_WRZRMPP00000_',
        '8YPFnW5UOM91H7' => 'deltarefund_WMRAZOR00000_',
        '8byazTDARv4Io0' => 'deltarefund_',
    ];

    public function getReport(array $input)
    {
        $this->setDefaults();

        // Take auto refund delay (seconds) from merchant config
        $autoRefundDelay = $this->merchant->getAutoRefundDelay();

        $timestamp = Carbon::yesterday(Timezone::IST)->subSeconds($autoRefundDelay)->timestamp;

        if (isset($input['on']) === true)
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->setTime(0,0,0);

            $timestamp = $from->getTimestamp();
        }
        elseif (isset($input['from']) === true)
        {
            $timestamp = $input['from'];
        }

        $filename = $this->generateFilename($timestamp);

        $fullpath = $this->writeDataToCsv($input, $filename);

        $s3File = $this->createFileAndSave($fullpath, $filename);

        $this->unlinkFile($fullpath);

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($s3File);

        $data = $this->createMailData($filename, $signedUrl, $input);

        $reportingMail = new IrctcMail($data);

        Mail::queue($reportingMail);

        return [ 'url' => $signedUrl ];
    }

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        return $this->repo->refund
                          ->fetchIrctcDeltaRefunds($merchantId, $from, $to);
    }

    protected function fetchFormattedDataForReport($entities): array
    {
        $data = [];

        foreach ($entities as $refund)
        {
            $payment = $refund->payment;

            $data[] = [
                self::MERCHANT_REFERENCE => $this->getReservationId($payment),
                self::PAYMENT_DATE       => $this->getPaymentDate($payment),
                self::PAYMENT_ID         => $payment->getPublicId(),
                self::REFUND_AMOUNT      => $this->getRefundAmount($payment),
                self::REFUND_STATUS      => '5', // 5 for success, 6 for failure
                self::REFUND_REMARKS     => 'Refunded',
                self::REFUND_DATE        => $this->getRefundedDate($refund),
                self::REFUND_ID          => $refund->getPublicId(),
            ];
        }

        return $data;
    }

    protected function getRefundAmount(Payment\Entity $payment)
    {
        $amount = '';

        $order = $payment->order;

        if ($order !== null)
        {
            $amount = $order->getAmount()/100;
        }

        return $amount;
    }


    protected function getReservationId(Payment\Entity $payment)
    {
        $reservationId = '';

        $order = $payment->order;

        if ($order !== null)
        {
            $notes = $order->notes;

            $reservationId = $notes->reservation_id ?? '';
        }

        return $reservationId;
    }

    protected function getPaymentDate(Payment\Entity $payment)
    {
        $ts = $payment->getCreatedAt();

        $paymentDate = Carbon::createFromTimestamp($ts, Timezone::IST)
                             ->format('Ymd');

        return $paymentDate;
    }

    protected function getRefundedDate(Refund\Entity $refund)
    {
        $ts = $refund->getCreatedAt();

        $refundDate = Carbon::createFromTimestamp($ts, Timezone::IST)
                             ->format('Ymd');

        return $refundDate;
    }

    protected function generateFilename($timestamp) : string
    {
        $version = 'V1';

        $time = Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('Ymd');

        $filePrefix = self::FILE_PREFIX[$this->merchant->getId()];

        return $filePrefix . $time . '_' . $version . '.txt';
    }

    protected function writeDataToCsvForMerchant(int $from,
                                                 int $to,
                                                 int $count,
                                                 int $skip,
                                                 string $filename,
                                                 string $merchantId,
                                                 bool $append = false): array
    {
        list($data, $count) = $this->getReportDataForMerchant($from, $to, self::BATCH_LIMIT, $skip, $merchantId);

        $txt = $this->generateText($data, ',');

        $fullpath = $this->createTxtFile($filename, $txt);

        return [$count, $fullpath];
    }

    protected function getTimestamps($input): array
    {
        // Take auto refund delay (seconds) from merchant config
        $autoRefundDelay = $this->merchant->getAutoRefundDelay();

        $from = Carbon::yesterday(Timezone::IST)->subSeconds($autoRefundDelay)->timestamp;

        // 1 day = 24*60*60 = 86400 seconds
        $to = Carbon::yesterday(Timezone::IST)->subSeconds($autoRefundDelay - 86400)->timestamp;

        if (isset($input['on']) === true)
        {
            $from = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST)->setTime(0,0,0);

            $fromTimeStamp = $from->getTimestamp();

            $to = $from->addDay()->getTimestamp() - 1;

            $from = $fromTimeStamp;
        }

        if (isset($input['from']) === true)
        {
            $from = $input['from'];
        }

        if (isset($input['to']) === true)
        {
            $to = $input['to'];
        }

        return [$from, $to];
    }

    protected function validateInput(array $input)
    {
        $this->checkAllowedEntity();
    }

    protected function createFileAndSave($filePath, $fileName)
    {
        $creator = new FileStore\Creator;

        $s3File = $creator->localFilePath($filePath)
                          ->extension(FileStore\Format::TXT)
                          ->mime('text/plain')
                          ->name('reports/' . $fileName)
                          ->store(FileStore\Store::S3)
                          ->type(FileStore\Type::REPORT)
                          ->merchant($this->merchant)
                          ->save()
                          ->getFileInstance();

        return $s3File;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }

    protected function createMailData($filename, $signedUrl, $input)
    {
        list($from, $to) = $this->getTimestamps($input);

        $fdate = Carbon::createFromTimestamp($from, Timezone::IST)->format('Y-m-d');

        $tdate = Carbon::createFromTimestamp($to, Timezone::IST)->format('Y-m-d');

        $emails = $this->merchant['transaction_report_email'];

        if (isset($input['email']) === true)
        {
            $inputEmails = explode(',', $input['email']);

            $emails = array_merge($emails, $inputEmails);
        }

        $data = [
            'subject'    => 'Irctc Delta Refunds Report - ' . $fdate .' to ' . $tdate,
            'body'       => '',
            'signed_url' => $signedUrl,
            'filename'   => $filename,
            'emails'     => $emails,
        ];

        return $data;
    }
}
