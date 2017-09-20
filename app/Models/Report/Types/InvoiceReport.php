<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;

use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Pricing\Feature;
use RZP\Models\Pricing\FeeCalculator;
use RZP\Models\Transaction\FeeBreakup\Name as FeeName;
use RZP\Trace\TraceCode;

class InvoiceReport extends BaseReport
{
    // Corresponds to 15th November 2015 00:00
    //const SWACH_BHARAT_CUTOFF_TIMESTAMP = 1447525800;
    const SWACH_BHARAT_CESS         = 'Swachh Bharat Cess';
    const SWACH_BHARAT_CESS_RATE    = 0.005;

    //Corresponds to 1st June, 2016 00:00
    // const KRISHI_KALYAN_CUTOFF_TIMESTAMP = 1464719400;
    const KRISHI_KALYAN_CESS        = 'Krishi Kalyan Cess';
    const KRISHI_KALYAN_CESS_RATE   = 0.005;

    const SERVICE_TAX   = 'Service Tax';
    const RAZORPAY_FEE  = 'razorpay_fee';
    const TAXES         = 'taxes';
    const TAX           = 'tax';
    const TOTAL_FEE     = 'total_fee';

    // Report headings
    const GST_SAC_CODE  = 'GST.SAC Code';
    const DESCRIPTION   = 'Description';
    const AMOUNT        = 'Amount';
    const AMOUNT_DUE    = 'Amount Due';
    const SGST          = 'SGST @ 9%';
    const CGST          = 'CGST @ 9%';
    const IGST          = 'IGST @ 18%';
    const TAX_TOTAL     = 'Tax Total';
    const GRAND_TOTAL   = 'Grand Total';

    const PAGES             = 'pages';
    const SUMMARY           = 'Summary';
    const SUMMARY_TITLE     = 'Invoice Summary';
    const TAX_INVOICE       = 'Tax Invoice';
    const TAX_DEBIT_NOTE    = 'Tax Debit Note';
    const TAX_CREDIT_NOTE   = 'Tax Credit Note';
    const ROWS              = 'rows';
    const TOTAL_AMOUNT_DUE  = 'total_amount_due';
    const TOTAL_AMOUNT_PAID = 'total_amount_paid';
    const DOCUMENT_NO       = 'Document No.';
    const DOCUMENT_DATE     = 'Document Date';

    protected $inputRules;
    protected $month;
    protected $year;
    protected $invoiceNo;
    protected $invoiceDate;
    protected $gstin;
    protected $taxComponents;
    protected $reportData = [];
    protected $debitNoteData = [];
    protected $creditNoteData = [];
    protected $invoiceReport = [];
    protected $totalInvoiceAmountDue = 0;
    protected $totalDebitNoteAmountDue = 0;
    protected $totalCreditNoteAmountDue = 0;

    public function __construct()
    {
        parent::__construct();

        $this->inputRules = [
            'year'      => 'required|digits:4',
            'month'     => 'required|digits_between:1,2',
            'format'    => 'sometimes|string',
        ];
    }

    public function getInvoiceReport($input)
    {
        $this->trace->info(TraceCode::MERCHANT_INVOICE_REPORT_REQUEST, $input);

        (new JitValidator)->rules($this->inputRules)->input($input)->validate();

        $this->month = $input['month'];

        $this->year = $input['year'];

        if ((isset($input['format']) === true) and
            ($input['format'] === 'new'))
        {
            $this->getInvoiceNew($input);

            $this->groupData();

            return $this->invoiceReport;
        }
        else
        {
            return $this->getInvoiceV2($input);
        }
    }

    protected function setInvoiceVariables()
    {
        $this->invoiceBreakup = $this->repo->merchant_invoice->fetchInvoiceReportData(
                                    $this->merchant->getId(), $this->month, $this->year);

        if ($this->invoiceBreakup->count() === 0)
        {
            throw new Exception\RuntimeException(
                'Invoice not generated yet for merchant ' . $this->merchant->getId() .
                ' for year ' . $this->year . ' and month ' . $this->month);
        }

        $this->invoiceNo = $this->invoiceBreakup[0]->getInvoiceNumber();

        $this->invoiceDate = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST)
                                    ->addMonth()
                                    ->startOfMonth()
                                    ->format('d/m/Y');

        $this->gstin = $this->invoiceBreakup[0]->getGstin();

        $this->taxComponents = $this->getTaxComponents($this->gstin);

        $this->invoiceReport = [
            self::SUMMARY       => [self::SUMMARY_TITLE => [self::ROWS => []]],
            'invoice_number'    => $this->invoiceNo,
            'invoice_date'      => $this->invoiceDate,
            'gstin'             => $this->gstin,
            self::PAGES         => [
                self::TAX_INVOICE       => [],
                self::TAX_CREDIT_NOTE   => [],
                self::TAX_DEBIT_NOTE    => [],
            ],
        ];
    }

    protected function getInvoiceNew(array $input)
    {
        $this->setInvoiceVariables();

        // Different fee component rows
        foreach ($this->invoiceBreakup as $index => $entity)
        {
            $type = $entity->getType();

            $tax = abs($entity->getTax());

            $amount = abs($entity->getAmount());

            // Current row
            $row = $this->getNewRow();

            $row[self::GST_SAC_CODE] = Invoice\Type::getGstSacCodeForType($type);

            $row[self::DESCRIPTION] = $entity->getDescription();

            $row[self::AMOUNT] = $amount;

            $row[self::TAX_TOTAL] = $tax;

            $row[self::GRAND_TOTAL] = $tax + $amount;

            if (count($this->taxComponents) === 1)
            {
                $row[self::IGST] = $tax;
            }
            else
            {
                $taxComponentValue = (int) round($tax / 2);

                $row[self::CGST] = $taxComponentValue;

                $row[self::SGST] = $taxComponentValue;
            }

            if ($type === Invoice\Type::ADJUSTMENT)
            {
                if (($entity->getAmount() < 0) or ($entity->getTax() < 0))
                {
                    $this->debitNoteData[] = $row;
                }
                else
                {
                    $this->creditNoteData[] = $row;
                }
            }
            else
            {
                $this->reportData[] = $row;
            }
        }
    }

    protected function groupDataForSummaryByPageType(
        array $allRows, string $pageType, string $pageDescription, & $summaryAmount)
    {
        if (empty($allRows) === true)
        {
            return;
        }

        $finalRow = $this->getFinalRow($allRows);

        $allRows[] = $finalRow;

        $this->invoiceReport[self::PAGES][$pageType] = [
            self::ROWS => $allRows,
        ];

        $amount = $finalRow[self::GRAND_TOTAL];

        // Add row for summary page
        $this->invoiceReport[self::SUMMARY][self::SUMMARY_TITLE][self::ROWS][] = [
            self::DOCUMENT_NO       => $this->invoiceNo,
            self::DOCUMENT_DATE     => $this->invoiceDate,
            self::DESCRIPTION       => $pageDescription,
            self::AMOUNT            => $amount,
        ];

        if ($pageType === self::TAX_CREDIT_NOTE)
        {
            $summaryAmount -= $amount;
        }
        else
        {
            $summaryAmount += $amount;
        }
    }

    protected function groupData()
    {
        $summaryAmount = 0;

        $this->groupDataForSummaryByPageType(
            $this->reportData, self::TAX_INVOICE, 'Monthly Invoice', $summaryAmount);

        $this->groupDataForSummaryByPageType(
            $this->debitNoteData, self::TAX_DEBIT_NOTE, self::TAX_DEBIT_NOTE, $summaryAmount);

        $this->groupDataForSummaryByPageType(
            $this->creditNoteData, self::TAX_CREDIT_NOTE, self::TAX_CREDIT_NOTE, $summaryAmount);

        // Add final row for the summary page
        $this->invoiceReport[self::SUMMARY][self::SUMMARY_TITLE][self::ROWS][] = [
            self::DOCUMENT_NO       => '',
            self::DOCUMENT_DATE     => '',
            self::DESCRIPTION       => 'Total',
            self::AMOUNT            => $summaryAmount,
        ];
    }

    protected function getNewRow(): array
    {
        return [
            self::GST_SAC_CODE  => '',
            self::DESCRIPTION   => '',
            self::AMOUNT        => 0,
            self::SGST          => 0,
            self::CGST          => 0,
            self::IGST          => 0,
            self::TAX_TOTAL     => 0,
            self::GRAND_TOTAL   => 0,
        ];
    }

    protected function getFinalRow(array $rows): array
    {
        $finalRow = $this->getNewRow();

        $finalRow[self::DESCRIPTION] = 'Total';

        foreach ($rows as $row)
        {
            $finalRow[self::AMOUNT]         += $row[self::AMOUNT];
            $finalRow[self::TAX_TOTAL]      += $row[self::TAX_TOTAL];
            $finalRow[self::GRAND_TOTAL]    += $row[self::GRAND_TOTAL];
            $finalRow[self::IGST]           += $row[self::IGST];
            $finalRow[self::CGST]           += $row[self::CGST];
            $finalRow[self::SGST]           += $row[self::SGST];
        }

        return $finalRow;
    }

    protected function getTaxComponents(string $gstin = null): array
    {
        $businessStateCode = Detail\Entity::getBusinessStateCodeFromGstin($gstin);

        return FeeCalculator::getTaxComponentsFromStateCode($businessStateCode);
    }

    protected function getInvoiceV2(array $input): array
    {
        $merchantId = $this->merchant->getId();

        list($from, $to) = $this->getTimestamps($input);

        $feesBreakup = $this->repo->fee_breakup->fetchFeesBreakupForInvoice($merchantId, $from, $to);

        $fees = $feesBreakup->getStringAttributesByKey('name');

        $totalRzpFee = 0;

        foreach (Feature::FEATURE_LIST as $feature)
        {
            if (isset($fees[$feature]) === true)
            {
                $totalRzpFee += intval($fees[$feature]['sum']);
            }
        }

        if (FeeCalculator::isGstApplicable($from) === true)
        {
            $taxInfo = $this->getGstTaxes($fees);
        }
        else
        {
            $taxInfo = $this->getNonGstTaxes($fees);
        }

        $totalTax = $taxInfo['total_tax'];

        $taxes = $taxInfo['taxes'];

        return [
            self::TOTAL_FEE    => $totalRzpFee + $totalTax,
            self::RAZORPAY_FEE => $totalRzpFee,
            self::TAX          => $totalTax,
            self::TAXES        => $taxes,
        ];
    }

    /**
     * @return Array $arr
     * @return Array $arr['taxes']      List of tax componensts with respective values
     * @return Float $arr['total_tax']  Sum of all tax components
     */
    protected function getNonGstTaxes(array $f): array
    {
        $serviceTax = intval($f[FeeName::SERVICE_TAX]['sum'] ?? 0);
        $swachBharatCess = intval($f[FeeName::SWACHH_BHARAT_CESS]['sum'] ?? 0);
        $krishiKalyanCess = intval($f[FeeName::KRISHI_KALYAN_CESS]['sum'] ?? 0);

        $nonGstTaxes = $serviceTax + $swachBharatCess + $krishiKalyanCess;

        $taxes = [];

        if ($nonGstTaxes > 0)
        {
            $taxes = [
                self::SERVICE_TAX        => $serviceTax,
                self::SWACH_BHARAT_CESS  => $swachBharatCess,
                self::KRISHI_KALYAN_CESS => $krishiKalyanCess,
            ];
        }

        return ['taxes' => $taxes, 'total_tax' => $nonGstTaxes];
    }

    /**
     * @return Array $arr
     * @return Array $arr['taxes']      List of tax componensts with respective values
     * @return Float $arr['total_tax']  Sum of all tax components
     */
    protected function getGstTaxes(array $fees): array
    {
        $igst = intval($fees[FeeName::IGST]['sum'] ?? 0);
        $cgst = intval($fees[FeeName::CGST]['sum'] ?? 0);
        $sgst = intval($fees[FeeName::SGST]['sum'] ?? 0);

        $merchantBusinessStateCode = $this->merchant->getBusinessStateCode();

        $intrastateGstApplicable = ($merchantBusinessStateCode === FeeCalculator::RZP_GST_STATE_CODE);

        // all 3 taxes might have been charged to merchant if merchant updated
        // their GSTN number later
        if ($intrastateGstApplicable === true)
        {
            $halfOfIgst = (int) round($igst / 2);
            $cgst += $halfOfIgst;
            $sgst += $igst - $halfOfIgst;
            $igst = 0;
        }
        else if ($intrastateGstApplicable === false)
        {
            $igst += ($cgst + $sgst);
            $cgst = 0;
            $sgst = 0;
        }

        if (($cgst > 0) or ($sgst > 0))
        {
            $totalTax = $cgst + $sgst;

            $taxes = [
                'CGST' => $cgst,
                'SGST' => $sgst,
            ];
        }
        else
        {
            $totalTax = $igst;

            $taxes = ['IGST' => $igst];
        }

        return ['taxes' => $taxes, 'total_tax' => $totalTax];
    }
}
