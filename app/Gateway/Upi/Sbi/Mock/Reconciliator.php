<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\PublicCollection;

class Reconciliator extends Base\Mock\Reconciliator
{
    protected $gateway = Payment\Gateway::UPI_SBI;

    /**
     * @override
     * @var string
     */
    protected static $fileToWriteName = 'MerchantReport';

    const HEADERS = [
        'PG Merchant ID',
        'Legal Name',
        'Store Name',
        'MCC',
        'Order No',
        'Trans Ref No.',
        'Customer Ref No.',
        'NPCI Response Code',
        'Trans Type',
        'DR/CR',
        'Transaction Status',
        'Transaction Remarks',
        'Transaction Date',
        'Transaction Amount',
        'Payer A/c No.',
        'Payer Virtual Address',
        'Payer A/C Name',
        'Payer IFSC Code',
        'Payee A/C No',
        'Payee Virtual Address',
        'Payee A/C Name',
        'Payee IFSC Code',
        'Pay Type',
        'Device Type',
    ];

    /**
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
        $data = [];

        $totalAmount = 0;

        $data[] = self::HEADERS;

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $data[] = [
                'random_merchant_id',
                'Razorpay Software Private Limited',
                'Razorpay Software Private Limited',
                '9399',
                $row['payment']['id'],
                12345,
                99999999998,
                'U69',
                'COLLECT',
                'Credit',
                'SUCCESS',
                'Collect from razorpay@sbi',
                $date,
                (string) ($row['payment']['amount'] / 100),
                '123456789',
                'random@vpa',
                'Random name',
                'SBIN0000437',
                (string) random_integer(10),
                'razorpay@sbi',
                'Razorpay Software Private Limited',
                'SBIN0000437',
                'P2M',
                'Mob'
            ];

            $totalAmount += $row['payment']['amount'] / 100;
        }

        $this->content($data, 'sbi_recon');

        return [$totalAmount, $data];
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

    protected function createFile(
        string $extension,
        array $content,
        string $fileName,
        string $type = FileStore\Type::UPI_SBI_RECONCILIATION,
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
