<?php

namespace RZP\Models\Report\Types;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Base\JitValidator;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\Invoice;
use RZP\Models\Pricing\Calculator;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class BankingInvoiceReport extends BaseReport
{
    const TAX                       = 'tax';
    // Report headings
    const GST_SAC_CODE              = 'GST.SAC Code';
    const DESCRIPTION               = 'description';
    const AMOUNT                    = 'amount';

    const SGST                      = 'SGST_9%';
    const CGST                      = 'CGST_9%';
    const IGST                      = 'IGST_18%';
    const TAX_TOTAL                 = 'tax_total';
    const GRAND_TOTAL               = 'grand_total';

    const PAGES                     = 'pages';
    const SUMMARY_TITLE             = 'invoice_summary';
    const TAX_INVOICE               = 'tax_invoice';
    const ROWS                      = 'rows';
    const DOCUMENT_NO               = 'document_no';
    const DOCUMENT_DATE             = 'document_date';
    const DATE_FORMAT               = 'd/m/Y';
    const ACCOUNT_NUMBER            = 'account_number';
    const GSTIN                     = 'gstin';
    const INVOICE_NUMBER            = 'invoice_number';
    const INVOICE_ID                = 'id';
    const INVOICE_DATE              = 'invoice_date';
    const MONTHLY_INVOICE           = 'monthly_invoice';
    const BILLING_PERIOD            = 'billing_period';
    const ISSUED_TO                 = 'issued_to';
    const ADDRESS                   = 'address';
    const BUSINESS_REGISTERED_CITY  = 'business_registered_city';
    const BUSINESS_REGISTERED_STATE = 'business_registered_state';
    const BUSINESS_REGISTERED_PIN   = 'business_registered_pin';

    const VALIDATION_RULES          = [
        'year'           => 'required|digits:4',
        'month'          => 'required|digits_between:1,2',
        'account_number' => 'required|alpha_num|between:5,22',
    ];

    protected $month;

    protected $year;

    public function __construct()
    {
        parent::__construct();

    }

    public function getInvoiceReport($input)
    {
        $this->trace->info(TraceCode::MERCHANT_BANKING_INVOICE_REPORT_REQUEST, $input);

        (new JitValidator)->rules(self::VALIDATION_RULES)->input($input)->validate();

        $this->month = $input['month'];

        $this->year = $input['year'];

        $this->merchant->getValidator()->validateAndTranslateAccountNumberForBanking($input);

        $balanceId = $input[BankingAccountEntity::BALANCE_ID];

        $invoice = $this->repo->merchant_invoice->fetchBankingInvoiceReportData($this->merchant->getId(),
                                                                                $this->month,
                                                                                $this->year,
                                                                                $balanceId);

        if (empty($invoice) === true)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice not generated yet for merchant',
                null,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'year'        => $this->year,
                    'month'       => $this->month,
                    'balance_id'  => $balanceId,
                ]);
        }

        $reportData = $this->getInvoiceReportData($invoice);

        $invoiceReport = $this->groupDataForSummaryByPageType($invoice, $reportData);

        return $invoiceReport;
    }

    protected function getInvoiceReportData(Invoice\Entity $invoice)
    {
        $type = $invoice->getType();

        $tax = $invoice->getTax();

        $amount = $invoice->getAmount();

        // Current row
        $row = $this->getNewRow();

        $row[self::GST_SAC_CODE] = Invoice\Type::getGstSacCodeForType($type);

        $row[self::DESCRIPTION] = $invoice->getDescription();

        $row[self::AMOUNT] = $amount;

        $row[self::TAX_TOTAL] = $tax;

        $row[self::GRAND_TOTAL] = $tax + $amount;

        $taxComponents = $this->getTaxComponents($invoice->getGstin());

        if (count($taxComponents) === 1)
        {
            $row[self::IGST] = $tax;
        }
        else
        {
            $taxComponentValue = (int) round($tax / 2);

            $row[self::CGST] = $taxComponentValue;

            $row[self::SGST] = $taxComponentValue;
        }

        $reportData[] = $row;

        return $reportData;
    }

    protected function getIssuedToDetails()
    {
        $merchantDetail = $this->merchant->merchantDetail;

        return [
            MerchantEntity::MERCHANT_ID     => $this->merchant->getAttribute(MerchantEntity::ID),
            MerchantEntity::NAME            => $this->merchant->getAttribute(MerchantEntity::NAME),
            self::ADDRESS                   => $merchantDetail->getAttribute(Detail\Entity::BUSINESS_REGISTERED_ADDRESS),
            self::BUSINESS_REGISTERED_CITY  => $merchantDetail->getAttribute(Detail\Entity::BUSINESS_REGISTERED_CITY),
            self::BUSINESS_REGISTERED_STATE => $merchantDetail->getAttribute(Detail\Entity::BUSINESS_REGISTERED_STATE),
            self::BUSINESS_REGISTERED_PIN   => $merchantDetail->getAttribute(Detail\Entity::BUSINESS_REGISTERED_PIN),
        ];
    }

    protected function getBillingPeriod()
    {
        $from = Carbon::createFromDate($this->year, $this->month , 1, Timezone::IST)
                      ->startOfDay()
                      ->getTimestamp();

        $to = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST)
                    ->endOfMonth()
                    ->getTimestamp();

        $from = Carbon::createFromTimestamp($from,Timezone::IST)->format(self::DATE_FORMAT);
        $to = Carbon::createFromTimestamp($to,Timezone::IST)->format(self::DATE_FORMAT);

        return $from . '-' . $to;
    }

    protected function getTaxComponents(string $gstin = null): array
    {
        return Calculator\Base::getTaxComponentsForMerchant($gstin, $this->merchant);
    }

    protected function groupDataForSummaryByPageType(Invoice\Entity $invoice, array $allRows)
    {
        if (empty($allRows) === true)
        {
            return [];
        }

        $finalRow = $this->getFinalRow($allRows);

        $allRows[] = $finalRow;

        $amount = $finalRow[self::GRAND_TOTAL];

        $invoiceReport = [
            self::SUMMARY_TITLE  => [
                self::ROWS => [
                    self::DOCUMENT_NO       => $invoice->getInvoiceNumber(),
                    self::DOCUMENT_DATE     => $this->getInvoiceDate($invoice->getCreatedAt()),
                    self::DESCRIPTION       => self::MONTHLY_INVOICE,
                    self::AMOUNT            => $amount,
            ]],
            self::ISSUED_TO      => $this->getIssuedToDetails(),
            self::INVOICE_NUMBER => $invoice->getInvoiceNumber(),
            self::INVOICE_ID     => $invoice->getId(),
            self::BILLING_PERIOD => $this->getBillingPeriod(),
            self::INVOICE_DATE   => $this->getInvoiceDate($invoice->getCreatedAt()),
            self::GSTIN          => $invoice->getGstin(),
            self::PAGES          => [
                self::TAX_INVOICE => [
                    self::ROWS  => $allRows
                ],
            ],
        ];

        return $invoiceReport;
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

    protected function getInvoiceDate($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)
                     ->format(self::DATE_FORMAT);
    }
}
