<?php

namespace RZP\Gateway\Upi\Sbi\Mock;

use RZP\Models\Payment;
use RZP\Gateway\Upi\Base;
use RZP\Models\FileStore;
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