<?php

namespace RZP\Models\Merchant\Invoice\EInvoice;


use Carbon\Carbon;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Report\Types\BankingInvoiceReport;
use RZP\Models\Pricing\Calculator as PricingCalculator;



class XEInvoice extends Core
{

    protected $xDocumentTypes = [
        DocumentTypes::INV,
        DocumentTypes::CRN
    ];

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

        foreach ($invoiceData[BankingInvoiceReport::ROWS][$eInvoiceEntity->getDocumentType()] as $invoiceItem)
        {
            if ($this->shouldIgnoreLineItem($invoiceItem) === true)
            {
                continue;
            }

            $gstRate = PricingCalculator\Tax\IN\Constants::IGST_PERCENTAGE/100;

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
                Constants::ACCOUNT_TYPE => $invoiceItem[BankingInvoiceReport::ACCOUNT_TYPE],
                Constants::CHANNEL => $invoiceItem[BankingInvoiceReport::CHANNEL],
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
            $items[$index][Constants::PRODUCT_DESCRIPTION]  = $this->getModifiedProductDescription($values[Constants::ACCOUNT_TYPE], $values[Constants::CHANNEL]);
            $items[$index][Constants::UNIT_PRICE]           = $this->getAmountInRupees($values[Constants::UNIT_PRICE]);
            $items[$index][Constants::TOTAL_AMOUNT]         = $this->getAmountInRupees($values[Constants::TOTAL_AMOUNT]);
            $items[$index][Constants::ASSESSABLE_VALUE]     = $this->getAmountInRupees($values[Constants::ASSESSABLE_VALUE]);
            $items[$index][Constants::IGST_AMOUNT]          = $this->getAmountInRupees($values[Constants::IGST_AMOUNT]);
            $items[$index][Constants::SGST_AMOUNT]          = $this->getAmountInRupees($values[Constants::SGST_AMOUNT]);
            $items[$index][Constants::CGST_AMOUNT]          = $this->getAmountInRupees($values[Constants::CGST_AMOUNT]);
            $items[$index][Constants::TOTAL_ITEM_VALUE]     = $this->getAmountInRupees($values[Constants::TOTAL_ITEM_VALUE]);
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

        if(($item[BankingInvoiceReport::IGST] == 0) and ($item[BankingInvoiceReport::SGST] == 0) and ($item[BankingInvoiceReport::CGST] == 0))
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

        foreach($this->xDocumentTypes as $documentType)
        {
            $eInvoiceEntity = $this->getLatestGeneratedEInvoiceData($merchantId, $month, $year, $type, $documentType);

            if (isset($eInvoiceEntity) === true)
            {
                $invoiceEntity = $this->repo->merchant_e_invoice->fetchByInvoiceNumberAndDocumentType(
                    $eInvoiceEntity->getMerchantId(), $eInvoiceEntity->getInvoiceNumber(), DocumentTypes::INV);

                $invoiceIssueTime = Carbon::createFromTimestamp($invoiceEntity->getCreatedAt(), Timezone::IST)
                    ->format('d/m/Y');

                $eInvoiceData[$documentType] = [
                    self::IRN                         => $eInvoiceEntity->getGspIrn(),
                    self::SIGNED_QR_CODE              => $eInvoiceEntity->getGspSignedQrCode(),
                    self::QR_CODE_URL                 => $eInvoiceEntity->getGspQRCodeUrl(),
                    self::INVOICE_NUMBER              => $eInvoiceEntity->getInvoiceNumber(),
                    self::INVOICE_NUMBER_ISSUE_DATE   => $invoiceIssueTime,
                ];
            }
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

    public function correctInvoiceNumberForCreditNote(Entity $eInvoiceEntity, $sellerEntity)
    {
        $input = [
            Entity::MONTH          => $eInvoiceEntity->getMonth(),
            Entity::YEAR           => $eInvoiceEntity->getYear(),
        ];

        $data = (new BankingInvoiceReport())->getInvoiceReportForEInvoice($input, $eInvoiceEntity->merchant);

        $invoiceAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::INV]
        [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

        $creditNoteAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::CRN]
        [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

        if ($creditNoteAmount > $invoiceAmount)
        {
            $this->trace->info(TraceCode::EINVOICE_CRN_AMOUNT_GREATER_THAN_INV_FOR_X, [
                'merchantID' => $eInvoiceEntity->getMerchantId(),
                'month'      => $eInvoiceEntity->getMonth(),
                'year'       => $eInvoiceEntity->getYear(),
            ]);

            $invoiceTime = Carbon::createFromDate($input[Entity::YEAR],
                $input[Entity::MONTH], 1, Timezone::IST)->startOfMonth()->subMonth();

            [$invoiceNumber, $sellerEntity] = $this->getInvoiceNumberGreaterThanAmountAndRegisteredOnGSPPortal($creditNoteAmount,
                $invoiceTime, $eInvoiceEntity);

            if (empty($invoiceNumber))
            {
                return [false, $sellerEntity];
            }

            $eInvoiceEntity->invoice_number = $invoiceNumber;

            $eInvoiceEntity->save();
        }
        return [true, $sellerEntity];
    }

    protected function getInvoiceNumberGreaterThanAmountAndRegisteredOnGSPPortal($creditNoteAmount, $invoiceTime, $eInvoiceEntity)
    {
        $activatedAt = $eInvoiceEntity->merchant->getActivatedAt();
        do
        {
            $input = [
                Entity::MONTH          => $invoiceTime->month,
                Entity::YEAR           => $invoiceTime->year,
            ];

            $data = (new BankingInvoiceReport())->getInvoiceReportForEInvoice($input, $eInvoiceEntity->merchant);

            $invoiceAmount = $data[BankingInvoiceReport::ROWS][DocumentTypes::INV]
            [BankingInvoiceReport::COMBINED][BankingInvoiceReport::GRAND_TOTAL];

            if ($creditNoteAmount < $invoiceAmount)
            {
                $isRegisteredOnGspPortal = $this->repo->merchant_e_invoice->fetchByInvoiceNumberAndDocumentType($eInvoiceEntity->getMerchantId(),
                    $data[BankingInvoiceReport::INVOICE_NUMBER], DocumentTypes::INV);

                if (empty($isRegisteredOnGspPortal) === false)
                {
                    return [$data[BankingInvoiceReport::INVOICE_NUMBER], Constants::RSPL];
                }
            }

            $invoiceTime = $invoiceTime->startOfMonth()->subMonth();

        } while(($invoiceTime->getTimestamp() >= self::EINVOICE_START_TIMESTAMP) and ($invoiceTime->getTimestamp() > $activatedAt));

        $this->trace->info(TraceCode::EINVOICE_MANUAL_CREDIT_NOTE_REQUIRED_FOR_X, [
            'merchantID' => $eInvoiceEntity->getMerchantId(),
            'month'      => $eInvoiceEntity->getMonth(),
            'year'       => $eInvoiceEntity->getYear(),
        ]);
        return [null, Constants::RSPL];
    }

    protected function getModifiedProductDescription($account_type, $channel)
    {
        if($account_type === "shared")
        {
            return "RazorpayX Virtual Account Transactions";
        }

        return strtoupper($channel) . " " . "Current Account Transactions";
    }
}
