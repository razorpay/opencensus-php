<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_e_invoice';

    public function fetchEInvoicesFromMonthAndType(string $merchantId, int $month, int $year,
                                                   string $type, string $documentType = null)
    {
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::YEAR, '=', $year)
            ->where(Entity::MONTH, '=', $month)
            ->where(Entity::TYPE, '=', $type);

        if(empty($documentType) === false)
        {
            $query->where(Entity::DOCUMENT_TYPE, '=', $documentType);
        }

        return $query->get();
    }

    public function fetchByInvoiceNumber(string $merchantId, string $invoiceNo)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->where(Entity::INVOICE_NUMBER, '=', $invoiceNo)
            ->get();
    }

    public function getInvoiceNumber(string $merchantId, int $month, int $year, string $type)
    {
        return $this->newQuery()
            ->select([$this->dbColumn(Entity::INVOICE_NUMBER)])
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::YEAR, '=', $year)
            ->where(Entity::MONTH, '=', $month)
            ->where(Entity::TYPE, '=', $type)
            ->first();
    }
}