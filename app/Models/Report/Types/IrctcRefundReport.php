<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Order;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\FileStore;
use RZP\Models\Feature;
use RZP\Constants\Entity as E;

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
        '8YPFnW5UOM91H7' => 'deltarefund_WMRAZOR00000_'
    ];

    protected function fetchEntitiesForReport($merchantId, $from, $to, $count, $skip)
    {
        return $this->repo->refund
                        ->fetchByMerchantBetweenTimestamps($merchantId, $from, $to);
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
                self::REFUND_AMOUNT      => $refund->getAmount(),
                self::REFUND_STATUS      => '5', // 5 for success, 6 for failure
                self::REFUND_REMARKS     => 'Refunded',
                self::REFUND_DATE        => $this->getRefundedDate($refund),
                self::REFUND_ID          => $refund->getPublicId(),
            ];
        }

        return $data;
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
        $ts = $payment->getAuthorizeTimestamp();

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

    /**
     * Generates filename basis merchant_id, entity and timestamp
     *
     * @param  $timestamp
     * @return $filename string
     */
    protected function generateFilename($timestamp) : string
    {
        $version = 'V1';

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        $filePrefix = self::FILE_PREFIX[$this->merchant->getId()];

        return $filePrefix . $time . '_' .$version;
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

        $txt = $this->generateText($data, '|');

        $fullpath = $this->createTxtFile($filename, $txt);

        return [$count, $fullpath];
    }

    public function generateReport(array $input)
    {
        $this->setDefaults();

        $now = Carbon::now()->getTimestamp();

        $filename = $this->generateFilename($now);

        // We do not want all the aggregator merchant to download the complete report
        // so its behind aggregator_report feature
        if ($this->merchant->isFeatureEnabled(Feature\Constants::AGGREGATOR_REPORT) === true)
        {
            $fullpath = $this->writeDataToCsvForAggregator($input, $filename);
        }
        else
        {
            $fullpath = $this->writeDataToCsv($input, $filename);
        }

        $s3File = $this->createFileAndSave($fullpath, $filename);

        $this->unlinkFile($fullpath);
    }

    protected function getTimestamps($input): array
    {
        $from = Carbon::yesterday(Timezone::IST)->timestamp;

        $to = Carbon::today(Timezone::IST)->timestamp - 1;

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
}
