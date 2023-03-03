<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Report\Types\InvoiceReport;
use RZP\Models\Pricing\Calculator as PricingCalculator;

class PgEInvoice extends Core
{
    public static $documentTypeMap = [
        DocumentTypes::INV => InvoiceReport::TAX_INVOICE,
        DocumentTypes::DBN => InvoiceReport::TAX_DEBIT_NOTE,
        DocumentTypes::CRN => InvoiceReport::TAX_CREDIT_NOTE,
    ];

    public static $adjustmentDocumentType = [
        DocumentTypes::DBN,
        DocumentTypes::CRN,
    ];

    public $eInvoiceData;

    public function getItemList(Entity $eInvoiceEntity)
    {
        //TODO: Refactor this with new flow.
        $input = [
            'month'     => $eInvoiceEntity->getMonth(),
            'year'      => $eInvoiceEntity->getYear(),
            'format'    => 'new',
        ];

        $invoiceData = (new Invoice\Core())->getPgInvoiceBreakupGroupedData($input, $eInvoiceEntity->merchant);

        $documentType = self::$documentTypeMap[$eInvoiceEntity->getDocumentType()];
        $invoiceBreakup = $invoiceData[InvoiceReport::PAGES][$documentType];

        $totalIgstValue = 0;
        $totalSgstValue = 0;
        $totalCgstValue = 0;
        $itemSerialNumber = 0;
        $totalInvoiceValue = 0;
        $totalAssessableValue = 0;

        $items = [];

        foreach ($invoiceBreakup[InvoiceReport::ROWS] as $invoiceItem)
        {
            if ($this->shouldIgnoreLineItem($documentType, $invoiceItem) === true)
            {
                continue;
            }

            // TODO: CRN/DBN needs to be evaluated properly here as we might have to send the 0
            // as tax rate or something different
            if(in_array(Invoice\Type::getTypeFromDescription($invoiceItem[InvoiceReport::DESCRIPTION]), Invoice\Type::$taxablePrimaryCommissionTypes) === false)
            {
                $gstRate = 0;
            }
            else
            {
                $gstRate = PricingCalculator\Tax\IN\Constants::IGST_PERCENTAGE/100;
            }

            $amount = $invoiceItem[InvoiceReport::AMOUNT];
            $totalAssessableValue += $amount;

            $totalItemValue = $invoiceItem[InvoiceReport::GRAND_TOTAL];
            $totalInvoiceValue += $totalItemValue;

            $igstAmount = $invoiceItem[InvoiceReport::IGST];
            $totalIgstValue += $igstAmount;

            $sgstAmount = $invoiceItem[InvoiceReport::SGST];
            $totalSgstValue += $sgstAmount;

            $cgstAmount = $invoiceItem[InvoiceReport::CGST];
            $totalCgstValue += $cgstAmount;

            // For sending the gst rate for CRN/DBN if tax amount is greater than 0
            if ( (in_array($eInvoiceEntity->getDocumentType(), self::$adjustmentDocumentType) === true) and
                (($invoiceItem[InvoiceReport::SGST] !== 0 and  $invoiceItem[InvoiceReport::CGST] !== 0) or ($invoiceItem[InvoiceReport::IGST] !== 0)))
            {
                $gstRate = PricingCalculator\Tax\IN\Constants::IGST_PERCENTAGE/100;
            }

            $items[] = [
                Constants::PRODUCT_DESCRIPTION => $this->getModifiedProductDescription($invoiceItem[InvoiceReport::DESCRIPTION]),
                Constants::ITEM_SERIAL_NUMBER => ++$itemSerialNumber,
                Constants::IS_SERVICE => 'Y',
                Constants::HSN_CODE => $invoiceItem[InvoiceReport::GST_SAC_CODE],
                Constants::UNIT => 'OTH',
                Constants::QUANTITY => 1,
                Constants::UNIT_PRICE => $this->getAmountInRupees($amount),
                Constants::TOTAL_AMOUNT => $this->getAmountInRupees($amount),
                Constants::ASSESSABLE_VALUE => $this->getAmountInRupees($amount),
                Constants::GST_RATE => $gstRate,
                Constants::IGST_AMOUNT => $this->getAmountInRupees($igstAmount),
                Constants::SGST_AMOUNT => $this->getAmountInRupees($sgstAmount),
                Constants::CGST_AMOUNT => $this->getAmountInRupees($cgstAmount),
                Constants::TOTAL_ITEM_VALUE => $this->getAmountInRupees($totalItemValue),
            ];
        }

        $valueDetails = [
            Constants::TOTAL_ASSESSABLE_VALUE => $this->getAmountInRupees($totalAssessableValue),
            Constants::TOTAL_INVOICE_VALUE => $this->getAmountInRupees($totalInvoiceValue),
            Constants::TOTAL_IGST_VALUE => $this->getAmountInRupees($totalIgstValue),
            Constants::TOTAL_SGST_VALUE => $this->getAmountInRupees($totalSgstValue),
            Constants::TOTAL_CGST_VALUE => $this->getAmountInRupees($totalCgstValue),
        ];

        return [$items, $valueDetails];
    }

    public function getAmountInRupees($amount)
    {
        return number_format((abs($amount) /100), '2', '.', '');
    }

    public function shouldIgnoreLineItem($documentType, $item) : bool
    {
        if ($item[InvoiceReport::DESCRIPTION] === 'Total')
        {
            return true;
        }

        if ($documentType === InvoiceReport::TAX_CREDIT_NOTE or $documentType === InvoiceReport::TAX_DEBIT_NOTE)
        {
            return false;
        }

        return false;
    }

    public function isZeroTaxLineItem($item) : bool
    {
        if(($item[InvoiceReport::IGST] === 0) and ($item[InvoiceReport::SGST] === 0) and ($item[InvoiceReport::CGST] === 0))
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

        $shouldGeneratePgEInvoice = $this->shouldGenerateEInvoice($merchant, $date->getTimestamp());
        if($shouldGeneratePgEInvoice === true)
        {
            [$count, $entityMap] = $this->getEInvoiceData($merchantId, $month, $year, $type);

            $eInvoiceData[self::CALLOUT_MESSAGE] = 'Invoices generated for the billing period of January 2021 onwards will be registered on the GST Invoice Registration Portal (IRP) as per the guidelines issued by the GST Council with effect from 1st January 2021';

            foreach ($entityMap as $documentType => $eInvoice)
            {
                //TODO : need to re-look the logic of CRN/DBN generation with same PDF
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

                    $eInvoiceData[self::CALLOUT_MESSAGE] = 'Razorpay was unable to register your invoice on the GST IRN portal due to some error related to the current GSTIN and Address PIN code for your Razorpay Account. Please have your GST details updated on your Razorpay Account to have this invoice registered on the GST Invoice Registration Portal IRP';

                    break;
                }

                // adding this step here considering we are only creating for the INV not for CRN/DBN in same PDF
                // to set the date to last day of previous month
                $eInvoiceSuccessTimeStamp = Carbon::createFromDate($year, $month, 1, Timezone::IST)
                                                        ->endOfMonth()
                                                        ->format('d/m/Y');

                $eInvoiceData[self::E_INVOICE_COMPLETE_GENERATION_DATE] = $eInvoiceSuccessTimeStamp;


                $eInvoiceData[PgEInvoice::$documentTypeMap[$documentType]] = [
                    self::IRN                                  => $eInvoice->getGspIrn(),
                    self::SIGNED_QR_CODE                       => $eInvoice->getGspSignedQrCode(),
                    self::QR_CODE_URL                          => $eInvoice->getGspQRCodeUrl(),
                ];
            }
        }

        return $eInvoiceData;
    }

    protected function getModifiedProductDescription($description)
    {
        return str_replace(
            ["<=", ">=", "<", ">"],
            ["less than equal to", "greater than equal to", "less than", "greater than"],
            $description);
    }
}
