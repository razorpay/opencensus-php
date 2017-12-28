<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class Reconciliator extends Base\Mock\Reconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    private $headers = [
        'merchantID',
        'merchantName',
        'subMerchantID',
        'subMerchantName',
        'MerchantTranID',
        'Original Transaction date',
        'Original Transaction Time',
        'Refund Transaction date',
        'Refund Transaction Time',
        'Refund Amount',
        'Original Bank RRN',
        'Customer VPA',
        'Reason for refund',
        'Merchantaccount',
        'MerchantIFSCCode',
        'Customer Account Number',
        'Customer IFSC Code',
        'Type of Refund (Online/offline)',
        'Refund RRN',
        'Status',
    ];

    /**
     * @override
     * @var string
     */
    // TODO: Check what the name of the file should be
    protected static $fileToWriteName = 'MerchantReport';

    /**
     * The parent class's method gets only successful payments,
     * but for sbi recon, we need all payments - both successful
     * and failed. This method accomplishes that.
     *
     * @override
     * @return PublicCollection
     */
    protected function getAllPaymentsToReconcile()
    {
        $createdAtStart = Carbon::yesterday(Timezone::IST)->getTimestamp();

        $createdAtEnd = Carbon::today(Timezone::IST)->getTimestamp();

        return $this->repo
                    ->payment
                    ->fetch([
                        'gateway' => $this->gateway,
                        'from'    => $createdAtStart,
                        'to'      => $createdAtEnd,
                    ]);
    }

    /**
     * @override
     * @param array $input
     * @return array
     */
    protected function getReconciliationData(array $input)
    {
        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $col = [
                '116798',
                'RAZORPAY',
                '116798',
                'RAZORPAY',
                $row['refund']['id'],
                $date,
                '08:06 AM',
                '04-12-2017',
                '05:09 PM',
                $row['refund']['amount'] / 100,
                $row['gateway']['gateway_payment_id'],
                '9560658505@upi',
                'Razorpay Refund' . $row['refund']['id'],
                '205025290',
                'ICIC0000002',
                '00000020183413215',
                'SBIN0010441',
                'ONLINE',
                '733817298334',
                'SUCCESS',
            ];

            $this->content($col, 'col_icici_recon');

            $data[] = $col;
        }

        $emptyRow = array_fill(0, sizeof($this->headers), ' ');

        $headers = [$emptyRow, $this->headers];

        $data = array_merge($headers, $data);

        $this->content($data, 'icici_recon');

        return $data;
    }

    /**
     * @override
     * @param mixed $content
     * @return FileStore\Creator
     */
    protected function createReconFile($content)
    {
        return $this->createFile(
            FileStore\Format::XLSX,
            $content,
            self::$fileToWriteName
        );
    }

    protected function addRefundEntityIfNeeded(array & $data, Payment\Entity $payment)
    {
        // Since it is via the test flow, it is expected that each payment will have just one refund
        $data['refund'] = $payment->refunds->first()->toArray();
    }

    private function createFile(
        string $extension,
        array $content,
        string $fileName,
        string $type = FileStore\Type::MOCK_RECONCILIATION_FILE,
        string $store = FileStore\Store::S3)
    {
        $creator = new FileStore\Creator;

        $creator->extension($extension)
                ->content($content)
                ->name($fileName)
                ->store($store)
                ->type($type)
                ->headers(false)
                ->save();

        return $creator;
    }
}
