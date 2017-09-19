<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_invoice';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
        Entity::INVOICE_NUMBER  => 'sometimes|string',
        Entity::GSTIN           => 'sometimes|string|size:15',
    ];

    // Gets all invoice entities for a merchant for given month and year
    public function fetchInvoiceReportData(string $merchantId, int $month, int $year)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->get();
    }

    // Gets entities to be displayed on Tax Invoice page
    public function fetchFeesDataForInvoice(string $merchantId, int $month, int $year)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->where(Entity::TYPE, '!=', Type::ADJUSTMENT)
                    ->get();
    }

    public function fetchByInvoiceNumber(string $merchantId, string $invoiceNo)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::INVOICE_NUMBER, '=', $invoiceNo)
                    ->get();
    }
}