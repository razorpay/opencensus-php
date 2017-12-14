<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
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

    /**
     * @override
     * @return PublicCollection
     */
    protected function getAllPaymentsToReconcile()
    {
        return $this->repo->payment->fetchAllPaymentsFromYesterday();
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

        $data[] = ReconFileFields::getHeaders();

        foreach ($input as $index => $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $data[] = [
                $row['gateway']['gateway_merchant_id'],
                'Razorpay Software Private Limited',
                'Razorpay Software Private Limited',
                '9399',
                $row['payment']['id'],
                $row['gateway']['npci_reference_id'],
                $row['gateway']['gateway_payment_id'],
                'U69',
                'COLLECT',
                'Credit',
                'SUCCESS',
                'Collect from razorpay@sbi',
                $date,
                (string) ($row['payment']['amount'] / 100),
                '123456789',
                $row['gateway']['vpa'],
                $row['gateway']['name'] ?? 'Random name',
                $row['gateway']['bank'] . '0000437',
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
