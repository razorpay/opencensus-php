<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Models\Order;
use RZP\Models\Payment;
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
                self::REFUND_AMOUNT      => ($refund->getAmount() / 100),
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

            $reservationId = (isset($notes->reservation_id) === true) ? $notes->reservation_id : '';
        }

        return $reservationId;
    }

    protected function getPaymentDate(Payment\Entity $payment)
    {
        $paymentDate = '';

        $order = $payment->order;

        if ($order !== null)
        {
            $notes = $order->notes;

            $paymentDate = (isset($notes->txn_date) === true) ? $notes->txn_date : '';
        }

        return $paymentDate;
    }

    protected function getRefundedDate(RefundEntity $refund)
    {
        $ts = $refund->getCreatedAt();

        // Format dd/mm/yyyy hh:mm,
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
        $version = 1;

        $time = Carbon::now(Timezone::IST)->format('Ymd');

        return 'deltarefund_WUATRZRPAY_' . $time . '_' .$version . '.txt';
    }

    protected function writeDataToFileForMerchant(int $from,
                                                 int $to,
                                                 int $count,
                                                 int $skip,
                                                 string $filename,
                                                 string $merchantId,
                                                 bool $append = false): array
    {
        list($data, $count) = $this->getReportDataForMerchant($from, $to, self::BATCH_LIMIT, $skip, $merchantId);

        $txt = $this->generateText($data, '|');

        $creator = new FileStore\Creator;

        $file = $creator->extension(FileStore\Format::TXT)
                        ->content($txt)
                        ->name('reports/' . $fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::REPORT)
                        ->merchant($this->merchant)
                        ->save();

        $fullPath =  $file->getFileInstance();


        return [$count, $fullpath];
    }
}
