<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Constants\Product;

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
        $balanceIdCol   = $this->repo->balance->dbColumn(Entity::ID);
        $balanceTypeCol = $this->repo->balance->dbColumn(Entity::TYPE);

        $merchantInvoiceBalanceIdCol = $this->dbColumn(Entity::BALANCE_ID);

        return $this->newQuery()
                    ->selectRaw(Table::MERCHANT_INVOICE . '.*')
                    ->join(Table::BALANCE, $merchantInvoiceBalanceIdCol , '=', $balanceIdCol)
                    ->merchantId($merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->where($balanceTypeCol, '=', Product::PRIMARY)
                    ->get();
    }

    public function fetchBankingInvoiceReportData(string $merchantId, int $month, int $year, $balanceId)
    {
        $balanceIDColumn = $this->repo->merchant_invoice->dbColumn(Entity::BALANCE_ID);

        $typeColumn = $this->repo->merchant_invoice->dbColumn(Entity::TYPE);

        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where($typeColumn, '=', Type::RX_TRANSACTIONS)
                    ->where($balanceIDColumn, '=', $balanceId)
                    ->where(Entity::MONTH, '=', $month)
                    ->where(Entity::YEAR, '=', $year)
                    // We do first only for banking invoice report data since we know
                    // there will be exactly one row only for now - rx_transactions
                    ->first();
    }

    /**
     * Gets entities to be displayed on Tax Invoice page
     *
     * @param string $merchantId
     * @param int    $month
     * @param int    $year
     *
     * @return Base\PublicCollection | null
     */
    public function fetchFeesDataForInvoice(string $merchantId, int $month, int $year)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->where(Entity::TYPE, '!=', Type::ADJUSTMENT)
                    ->get();
    }

    public function fetchFeesDataToCheckInvoiceExists(string $merchantId, int $month, int $year , string $balanceId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::YEAR, '=', $year)
                    ->where(Entity::MONTH, '=', $month)
                    ->where(Entity::BALANCE_ID , '=', $balanceId)
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
