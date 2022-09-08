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

    public function fetchByInvoiceNumberAndDocumentType(string $merchantId, string $invoiceNo, string $documentType)
    {
        return $this->newQuery()
            ->merchantId($merchantId)
            ->where(Entity::INVOICE_NUMBER, '=', $invoiceNo)
            ->where(Entity::STATUS, '=', Status::STATUS_GENERATED)
            ->where(Entity::DOCUMENT_TYPE, '=', $documentType)
            ->orderBy(Entity::UPDATED_AT, 'DESC')
            ->first();
    }

    public function fetchByGspIrn(string $gspIrn)
    {
        return $this->newQuery()
            ->where(Entity::GSP_IRN, '=', $gspIrn)
            ->where(Entity::STATUS, '=', Status::STATUS_GENERATED)
            ->orderBy(Entity::UPDATED_AT, 'DESC')
            ->first();
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

    //This is required for finding merchants which should be eligible for revised e-invoices for Jan 2021.
    //Slack thread ref: https://razorpay.slack.com/archives/C659GARU3/p1612548900099900
    public function checkIfMerchantIsEligibleForRevisedInvoice(int $month, int $year, string $type, string $merchantId)
    {
        $query = $this->newQuery()
            ->select([$this->dbColumn(Entity::MERCHANT_ID)])
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::YEAR, '=', $year)
            ->where(Entity::MONTH, '=', $month)
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::DOCUMENT_TYPE, '=', DocumentTypes::INV)
            ->whereBetween(Entity::CREATED_AT, [1611513000, 1612549800])
            ->where(Entity::STATUS, '=', Status::STATUS_GENERATED);

        return $query->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

    public function fetchLatestGeneratedEInvoiceFromMonthAndType(string $merchantId, int $month, int $year,
                                                            string $type, string $documentType)
    {
        $query = $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Entity::YEAR, '=', $year)
            ->where(Entity::MONTH, '=', $month)
            ->where(Entity::TYPE, '=', $type)
            ->where(Entity::STATUS, '=', Status::STATUS_GENERATED)
            ->where(Entity::DOCUMENT_TYPE, '=', $documentType)
            ->orderBy(Entity::UPDATED_AT, 'DESC');

        return $query->first();
    }
}
