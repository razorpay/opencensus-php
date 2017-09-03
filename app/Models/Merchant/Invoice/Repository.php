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

    public function fetchInvoiceReportData(string $merchantId, int $month, int $year)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->get();
    }

    public function updateGstin(string $merchantId, string $invoiceNo, string $gstin)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::INVOICE_NUMBER, '=', $invoiceNo)
                    ->update([Entity::GSTIN => $gstin]);
    }
}