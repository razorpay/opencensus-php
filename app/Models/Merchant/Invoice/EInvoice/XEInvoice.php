<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
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

        return [$items, $valueDetails];
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

        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);
        $date = Carbon::createFromDate($year, $month, 1, Timezone::IST);

        $shouldGenerateXEInvoice = $this->shouldGenerateEInvoice($merchant, $date->getTimestamp());
        if($shouldGenerateXEInvoice === true)
        {
            [$count, $entityMap] = $this->getEInvoiceData($merchantId, $month, $year, $type);

            foreach ($entityMap as $documentType => $eInvoice)
            {
                $gspError = $eInvoice->getGspError();

                if((isset($gspError) === true) and ($this->shouldGenerateB2C($gspError) === true))
                {
                    $this->trace->info(TraceCode::EINOVICE_FALLBACK_TO_B2C,
                        [
                            'merchant_id'   => $merchantId,
                            'month'         => $month,
                            'year'          => $year,
                            'type'          => $type,
                            'document_type' => $documentType,
                            'error_message' => $gspError,
                        ]);

                    $eInvoiceData = [];

                    break;
                }
                $eInvoiceData = [
                    self::IRN             => $eInvoice->getGspIrn(),
                    self::SIGNED_QR_CODE  => $eInvoice->getGspSignedQrCode(),
                    self::QR_CODE_URL     => $eInvoice->getGspQRCodeUrl(),
                ];
            }
        }

        return $eInvoiceData;
    }
}
