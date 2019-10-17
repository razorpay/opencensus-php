<?php

namespace RZP\Reconciliator\NetbankingSbi;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Netbanking\Sbi\ReconFields\RefundReconFields;
use RZP\Gateway\Netbanking\Sbi\ReconFields\PaymentReconFields;

class Reconciliate extends Base\Reconciliate
{
    const RAZORPAY = 'razorpay';

    public function getColumnHeadersForType($type)
    {
        if ($type === Reconciliate::PAYMENT)
        {
            return PaymentReconFields::PAYMENT_COLUMN_HEADERS;
        }
        elseif ($type === Reconciliate::REFUND)
        {
            return RefundReconFields::REFUND_COLUMN_HEADERS;
        }
    }

    protected function getTypeName($fileName)
    {
        if (strpos($fileName, self::RAZORPAY) !== false)
        {
            $typeName = self::PAYMENT;
        }
        else
        {
            $typeName = self::REFUND;
        }

        return $typeName;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        $type = $this->getTypeName($fileDetails['file_name']);

        if ($type === self::REFUND)
        {
            return [
                FileProcessor::LINES_FROM_TOP    => 1,
                FileProcessor::LINES_FROM_BOTTOM => 0
            ];
        }
        else
        {
           return parent::getNumLinesToSkip($fileDetails);
        }
    }

    public function getFileType(string $mimeType): string
    {
        return FileProcessor::CSV;
    }
}
