<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;


use RZP\Models\Merchant;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Models\Pricing\Calculator as PricingCalculator;



class XEInvoice extends Core
{

    public function getItemList(Entity $eInvoiceEntity)
    {

        $invoiceData = (new Invoice\Core())->getXEInvoiceData($eInvoiceEntity->getMonth(), $eInvoiceEntity->getYear(), $eInvoiceEntity->merchant);

        $totalIgstValue = 0;
        $totalSgstValue = 0;
        $totalCgstValue = 0;
        $itemSerialNumber = 0;
        $totalInvoiceValue = 0;
        $totalAssessableValue = 0;

        $items = [];

        foreach ($invoiceData[BankingInvoiceReport::ROWS] as $invoiceItem)
        {
            if ($this->shouldIgnoreLineItem($invoiceItem) === true)
            {
                continue;
            }

            $gstRate = PricingCalculator\Base::IGST_PERCENTAGE/100;

            $amount = $invoiceItem[BankingInvoiceReport::AMOUNT];
            $totalAssessableValue += $amount;

            $totalItemValue = $invoiceItem[BankingInvoiceReport::GRAND_TOTAL];
            $totalInvoiceValue += $totalItemValue;

            $igstAmount = $invoiceItem[BankingInvoiceReport::IGST];
            $totalIgstValue += $igstAmount;

            $sgstAmount = $invoiceItem[BankingInvoiceReport::SGST];
            $totalSgstValue += $sgstAmount;

            $cgstAmount = $invoiceItem[BankingInvoiceReport::CGST];
            $totalCgstValue += $cgstAmount;

            $items[] = [
                Constants::ITEM_SERIAL_NUMBER => ++$itemSerialNumber,
                Constants::IS_SERVICE => 'Y',
                Constants::HSN_CODE => $invoiceItem[BankingInvoiceReport::GST_SAC_CODE],
                Constants::UNIT => 'OTH',
                Constants::QUANTITY => 1,
                Constants::UNIT_PRICE => $amount,
                Constants::TOTAL_AMOUNT => $amount,
                Constants::ASSESSABLE_VALUE => $amount,
                Constants::GST_RATE => $gstRate,
                Constants::IGST_AMOUNT => $igstAmount,
                Constants::SGST_AMOUNT => $sgstAmount,
                Constants::CGST_AMOUNT => $cgstAmount,
                Constants::TOTAL_ITEM_VALUE => $totalItemValue,
            ];
        }

        $valueDetails = [
            Constants::TOTAL_ASSESSABLE_VALUE => $totalAssessableValue,
            Constants::TOTAL_INVOICE_VALUE => $totalInvoiceValue,
            Constants::TOTAL_IGST_VALUE => $totalIgstValue,
            Constants::TOTAL_SGST_VALUE => $totalSgstValue,
            Constants::TOTAL_CGST_VALUE => $totalCgstValue,
        ];

        foreach ($valueDetails as $type => $value)
        {
            $valueDetails[$type] = $this->getAmountInRupees($value);
        }

        foreach ($items as $index => $values)
        {
            $items[$index][Constants::UNIT_PRICE]       = $this->getAmountInRupees($values[Constants::UNIT_PRICE]);
            $items[$index][Constants::TOTAL_AMOUNT]     = $this->getAmountInRupees($values[Constants::TOTAL_AMOUNT]);
            $items[$index][Constants::ASSESSABLE_VALUE] = $this->getAmountInRupees($values[Constants::ASSESSABLE_VALUE]);
            $items[$index][Constants::IGST_AMOUNT]      = $this->getAmountInRupees($values[Constants::IGST_AMOUNT]);
            $items[$index][Constants::SGST_AMOUNT]      = $this->getAmountInRupees($values[Constants::SGST_AMOUNT]);
            $items[$index][Constants::CGST_AMOUNT]      = $this->getAmountInRupees($values[Constants::CGST_AMOUNT]);
            $items[$index][Constants::TOTAL_ITEM_VALUE] = $this->getAmountInRupees($values[Constants::TOTAL_ITEM_VALUE]);
        }

        return [$items, $valueDetails];
    }

    public function getAmountInRupees($amount)
    {
        return number_format((abs($amount) /100), '2', '.', '');
    }

    public function shouldIgnoreLineItem($item) : bool
    {
        if ($item[BankingInvoiceReport::DESCRIPTION] === 'total')
        {
            return true;
        }

        if(($item[BankingInvoiceReport::IGST] === 0) and ($item[BankingInvoiceReport::SGST] === 0) and ($item[BankingInvoiceReport::CGST] === 0))
        {
            return true;
        }

        return false;
    }

    public function shouldGenerateB2C(string $errorMessage) : bool
    {
        return ($this->isInvalidDataError($errorMessage) === true);
    }

    public function getEInvoiceDataForPdf($merchantId, $month, $year, $type) : array
    {
        $eInvoiceData = [];

        $eInvoiceEntity = $this->getLatestGeneratedEInvoiceData($merchantId, $month, $year, $type, DocumentTypes::INV);

        if (isset($eInvoiceEntity) === true)
        {
            $eInvoiceData = [
                self::IRN             => $eInvoiceEntity->getGspIrn(),
                self::SIGNED_QR_CODE  => $eInvoiceEntity->getGspSignedQrCode(),
                self::QR_CODE_URL     => $eInvoiceEntity->getGspQRCodeUrl(),
            ];
        }

        return $eInvoiceData;
    }

    public function shouldGenerateEInvoice(Merchant\Entity $merchant, $fromTimestamp) : bool
    {
        $merchantDetails = $merchant->merchantDetail;

        $gstin = $merchantDetails->getGstin();
        $pinCode = $merchantDetails->getBusinessRegisteredPin();

        if((empty($gstin) === true) or (empty($pinCode) === true))
        {
            return false;
        }

        return ($fromTimestamp >= self::EINVOICE_START_TIMESTAMP);
    }
}
