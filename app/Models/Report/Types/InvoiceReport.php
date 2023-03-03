<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;

use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\FileStore\Accessor;
use RZP\Models\Merchant\Detail;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Pricing\Feature;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Transaction\FeeBreakup\Name as FeeName;
use RZP\Models\Merchant\Invoice\EInvoice\DocumentTypes;
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

    public static $documentTypeMap = [
        InvoiceReport::TAX_INVOICE      => DocumentTypes::INV,
        InvoiceReport::TAX_DEBIT_NOTE   => DocumentTypes::DBN,
        InvoiceReport::TAX_CREDIT_NOTE  => DocumentTypes::CRN,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->inputRules = [
            'year'        => 'required|digits:4',
            'month'       => 'required|digits_between:1,2',
            'format'      => 'sometimes|string',
        ];
    }

    public function getInvoiceReport($input)
    {
        $this->trace->info(TraceCode::MERCHANT_INVOICE_REPORT_REQUEST, $input);

        (new JitValidator)->rules($this->inputRules)->input($input)->validate();

        $newFlow = $this->isNewFlowEnabledForPgInvoice($this->merchant->getId());

        if ($newFlow === true)
        {
            try
            {
                $signedUrl = (new Invoice\Core())->getSignedUrlForPgInvoice(
                    $input['year'],
                    $input['month'],
                    $this->merchant->getId());

                return [
                    'signed_url' => $signedUrl,
                    'error'      => null,
                ];
            }
            catch(Exception\BadRequestValidationFailureException $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::MERCHANT_INVOICE_GET_REPORT_REQUEST_FAILED,
                    [
                        'input' => $input,
                    ]);

                return [
                    'signed_url' => "",
                    'error'      => $e->getMessage(),
                ];
            }
        }
        else
        {
            $this->month = $input['month'];

            $this->year = $input['year'];

            if ((isset($input['format']) === true) and
                ($input['format'] === 'new'))
            {
                $invoiceBreakup = $this->repo
                                       ->merchant_invoice
                                       ->fetchInvoiceReportData($this->merchant->getId(), $this->month, $this->year);

                $this->getInvoiceNew($input, $invoiceBreakup);

                $this->groupData();

                return $this->invoiceReport;
            }
            else
            {
                return $this->getInvoiceV2($input);
            }
        }
    }

    public function getpgInvoiceTemplateDate($data, $merchant, $invoiceBreakup)
    {
        $this->month = $data['month'];

        $this->year = $data['year'];

        $this->merchant = $merchant;

        if ($data['gst_applicable'] === true )
        {
            $this->getInvoiceNew($data, $invoiceBreakup);

            $this->groupData();

            return $this->invoiceReport;
        }

        return $this->getInvoiceV2($data);
    }

    protected function setInvoiceVariables($invoiceBreakup)
    {
        $invoice = null;

        if ($invoiceBreakup->count() === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice not generated yet for merchant ' . $this->merchant->getId() .
                ' for year ' . $this->year . ' and month ' . $this->month);
        }

        // we don't consider adjustments to construct basic data of invoice.
        // finOps can create adjustment at any time. If the records comes first
        // then entire records will be invalid in terms of date and can create confusion
        foreach ($invoiceBreakup as $invoiceItem)
        {
            if ($invoiceItem->getType() !== Invoice\Type::ADJUSTMENT)
            {
                $invoice = $invoiceItem;

                break;
            }
        }

        $this->invoiceNo = $invoice->getInvoiceNumber();

        // to set the date to last day of previous month
        $this->invoiceDate = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST)
                                    ->endOfMonth()
                                    ->format('d/m/Y');

        $this->gstin = $invoice->getGstin();

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

    protected function getInvoiceNew(array $input, $invoiceBreakup)
    {
        $this->setInvoiceVariables($invoiceBreakup);

        // Different fee component rows
        foreach ($invoiceBreakup as $index => $entity)
        {
            $type = $entity->getType();

            $tax = abs($entity->getTax());

            $amount = abs($entity->getAmount());

            // this is to remove the line column with the 0 tax amount
            if(($amount === 0) and ($tax === 0))
            {
                continue;
            }

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

        //this is to throw error if the total merchant_invoice amount is 0
        if ($summaryAmount === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice not generated yet for merchant ' . $this->merchant->getId() .
                ' for year ' . $this->year . ' and month ' . $this->month . ' since the amount is zero');
        }

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
        return Calculator\Tax\IN\Utils::getTaxComponentsWithGSTIN($gstin, $this->merchant);
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

        if (Calculator\Tax\IN\Utils::isGstApplicable($from) === true)
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

        $merchantBusinessStateCode = $this->merchant->getGstStateCode();

        $intrastateGstApplicable = ($merchantBusinessStateCode === Calculator\Tax\IN\Constants::RZP_GST_STATE_CODE);

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

    public function isNewFlowEnabledForPgInvoice($merchantId)
    {
        return true;
    }
}
