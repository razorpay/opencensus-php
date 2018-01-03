<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use Carbon\Carbon;
use RZP\Gateway\Base;
use RZP\Models\Payment;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Base\UniqueIdEntity;
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
        $data = [];

        foreach ($input as $row)
        {
            $date = Carbon::createFromTimestamp(
                $row['payment']['created_at'],
                Timezone::IST)
                ->format('d-M-y H:i:s');

            $data[] = [
                'PG Merchant ID'        => 'random_merchant_id',
                'Legal Name'            => 'Razorpay Software Private Limited',
                'Store Name'            => 'Razorpay Software Private Limited',
                'MCC'                   => '9399',
                'Order No'              => $row['payment']['id'],
                'Trans Ref No.'         => 12345,
                'Customer Ref No.'      => 99999999998,
                'NPCI Response Code'    => 'U69',
                'Trans Type'            => 'COLLECT',
                'DR/CR'                 => 'Credit',
                'Transaction Status'    => 'SUCCESS',
                'Transaction Remarks'   => 'Collect from razorpay@sbi',
                'Transaction Date'      => $date,
                'Transaction Amount'    => (string)($row['payment']['amount'] / 100),
                'Payer A/c No.'         => '123456789',
                'Payer Virtual Address' => 'random@vpa',
                'Payer A/C Name'        => 'Random name',
                'Payer IFSC Code'       => 'SBIN0000437',
                'Payee A/C No'          => (string)random_integer(10),
                'Payee Virtual Address' => 'razorpay@sbi',
                'Payee A/C Name'        => 'Razorpay Software Private Limited',
                'Payee IFSC Code'       => 'SBIN0000437',
                'Pay Type'              => 'P2M',
                'Device Type'           => 'Mob',
            ];
        }

        $this->content($data, 'sbi_recon');

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
                ->headers(true)
                ->save();

        return $creator;
    }

    protected function addGatewayEntityIfNeeded(array & $data)
    {
        return;
    }
}
