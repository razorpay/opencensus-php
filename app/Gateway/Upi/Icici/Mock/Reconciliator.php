<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Exception\LogicException;
use RZP\Models\Base\PublicEntity;
use RZP\Models\Base\PublicCollection;
use RZP\Reconciliator\Base\Reconciliate;

class Reconciliator extends Base\Mock\Reconciliator
{
    protected $gateway = Payment\Gateway::UPI_ICICI;

    private $refundHeaders = [
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

    private $paymentHeaders = [
        'accountNumber',
        'merchantID',
        'merchantName',
        'subMerchantID',
        'subMerchantName',
        'merchantTranID',
        'bankTranID',
        'date',
        'time',
        'amount',
        'payerVA',
        'status',
        'Commission',
        'Service tax',
        'Net amount'
    ];

    /**
     * @override
     * @var string
     */
    // TODO: Check what the name of the file should be
    protected static $fileToWriteName = 'MerchantReport';

    // TODO: Check this once?
    protected static $paymentFileToWriteName = 'PaymentMerchantReport';

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

    protected function getReconciliationData(array $input)
    {
        switch ($this->type)
        {
            case Reconciliate::REFUND:
                $data = $this->getRefundReconciliationData($input);
                break;

            case Reconciliate::PAYMENT:
                $data = $this->getPaymentReconciliationData($input);
                break;

            default:
                throw new LogicException('Action set incorrectly');
        }

        return $data;
    }

    private function getPaymentReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $col = [
                '000205025290',
                '116798',
                'RAZORPAY',
                '116798',
                'Razorpay SUB',
                $row['payment']['id'],
                '734122607521',
                $date,
                '10:39 PM',
                $row['payment']['amount'] / 100,
                '9619218329@ybl',
                'SUCCESS',
                '0',
                '0',
                '0',
            ];

            $this->content($col, 'col_payment_icici_recon');

            $data[] = $col;
        }

        $emptyRow = array_fill(0, sizeof($this->paymentHeaders), ' ');

        $headers = [$emptyRow, $this->paymentHeaders];

        $data = array_merge($headers, $data);

        $this->content($data, 'icici_payment_recon');

        return $data;
    }

    /**
     * @param array $input
     * @return array
     */
    private function getRefundReconciliationData(array $input)
    {
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['refund']['created_at'],
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

        $emptyRow = array_fill(0, sizeof($this->refundHeaders), ' ');

        $headers = [$emptyRow, $this->refundHeaders];

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
        $fileName = ($this->type === Reconciliate::REFUND) ? self::$fileToWriteName : self::$paymentFileToWriteName;

        return $this->createFile(
            FileStore\Format::XLSX,
            $content,
            $fileName
        );
    }

    protected function addGatewayEntityIfNeeded(array & $data, PublicEntity $entity)
    {
        $type = $entity->getEntity();

        $method = 'fetchBy' . ucfirst($type) . 'Id';

        $gatewayPayment = $this->repo->upi->{$method}($data[$type]['id']);

        $data['gateway'] = $gatewayPayment->toArray();
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
