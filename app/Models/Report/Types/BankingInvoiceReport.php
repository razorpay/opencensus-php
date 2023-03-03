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
use RZP\Models\Merchant\Invoice\Type;
use RZP\Models\Merchant\Entity as MerchantEntity;
use RZP\Models\Merchant\Invoice\EInvoice\DocumentTypes;
use RZP\Models\BankingAccount\Entity as BankingAccountEntity;

class BankingInvoiceReport extends BaseReport
{
    const DATE_FORMAT                = 'd/m/Y';
    const BILLING_PERIOD_DATE_FORMAT = 'd/m/Y';

    const TAX                       = 'tax';
    const GST_SAC_CODE              = 'GST.SAC Code';
    const DESCRIPTION               = 'description';
    const AMOUNT                    = 'amount';
    const SGST                      = 'SGST_9%';
    const CGST                      = 'CGST_9%';
    const IGST                      = 'IGST_18%';
    const TAX_TOTAL                 = 'tax_total';
    const GRAND_TOTAL               = 'grand_total';

    const TITLE                     = 'title';
    const TOTAL                     = 'total';
    const TAX_INVOICE               = 'Tax Invoice';
    const ROWS                      = 'rows';
    const ACCOUNT_NUMBER            = 'account_number';
    const ACCOUNT_TYPE              = 'account_type';
    const CHANNEL                   = 'channel';
    const GSTIN                     = 'gstin';
    const INVOICE_NUMBER            = 'invoice_number';
    const INVOICE_ID                = 'id';
    const INVOICE_DATE              = 'invoice_date';
    const BILLING_PERIOD            = 'billing_period';
    const ISSUED_TO                 = 'issued_to';
    const ADDRESS                   = 'address';
    const BUSINESS_REGISTERED_CITY  = 'business_registered_city';
    const BUSINESS_REGISTERED_STATE = 'business_registered_state';
    const BUSINESS_REGISTERED_PIN   = 'business_registered_pin';
    const COMBINED                  = 'combined';
    const E_INVOICE_DETAILS         = 'e_invoice_details';
    const SELLER_ENTITY             = 'seller_entity';

    const VALIDATION_RULES          = [
        'year'           => 'required|digits:4',
        'month'          => 'required|digits_between:1,2',
    ];

    public $documentTypeMap         = [
        Type::RX_TRANSACTIONS   =>    DocumentTypes::INV,
        Type::RX_ADJUSTMENTS    =>    DocumentTypes::CRN
    ];

    protected $month;

    protected $year;

    public function __construct()
    {
        parent::__construct();

    }

    public function getInvoiceReportForEInvoice($input, $merchant)
    {
        $this->merchant = $merchant;

        (new JitValidator)->rules(self::VALIDATION_RULES)->input($input)->validate();

        $this->month = $input['month'];

        $this->year = $input['year'];

        $invoices = $this->repo->merchant_invoice->fetchBankingInvoiceReportData($this->merchant->getId(),
            $this->month,
            $this->year);

        if (count($invoices) === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice not generated yet for merchant',
                null,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'year'        => $this->year,
                    'month'       => $this->month,
                ]);
        }

        foreach ($invoices as $invoice)
        {
            $reportData = $this->getInvoiceReportDataForEInvoice($invoice);

            $combinedData[$this->documentTypeMap[$invoice->getType()]][$reportData[self::ACCOUNT_NUMBER]] = array_except($reportData, self::ACCOUNT_NUMBER);
        }

        $invoiceReport = $this->groupDataForInvoice($invoice, $combinedData);

        return $invoiceReport;
    }

    protected function getInvoiceReportDataForEInvoice(Invoice\Entity $invoice)
    {
        $type = $invoice->getType();

        $tax = $invoice->getTax();

        $amount = $invoice->getAmount();

        // Current row
        $row = $this->getNewRow();

        $row[self::ACCOUNT_NUMBER] = $invoice->getAccountNumberAttribute();

        $balance = $this->repo->balance->getBalanceByAccountNumberAndMerchantIDOrFail($row[self::ACCOUNT_NUMBER], $this->merchant->getId());

        $row[self::ACCOUNT_TYPE] = $balance->getAccountType();

        $row[self::CHANNEL] = $balance->getChannel();

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

        $reportData = $row;

        return $reportData;
    }

    public function getInvoiceReport($input)
    {
        $this->trace->info(TraceCode::MERCHANT_BANKING_INVOICE_REPORT_REQUEST, $input);

        (new JitValidator)->rules(self::VALIDATION_RULES)->input($input)->validate();

        $this->month = $input['month'];

        $this->year = $input['year'];

        $invoices = $this->repo->merchant_invoice->fetchBankingInvoiceReportData($this->merchant->getId(),
                                                                                 $this->month,
                                                                                 $this->year);

        if (count($invoices) === 0)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invoice not generated yet for merchant',
                null,
                [
                    'merchant_id' => $this->merchant->getId(),
                    'year'        => $this->year,
                    'month'       => $this->month,
                ]);
        }

        foreach ($invoices as $invoice)
        {
            $reportData = $this->getInvoiceReportData($invoice);

            $combinedData[$this->documentTypeMap[$invoice->getType()]][$reportData[self::ACCOUNT_NUMBER]] = array_except($reportData, self::ACCOUNT_NUMBER);
        }

        $invoiceReport = $this->groupDataForInvoice($invoice, $combinedData);

        return $invoiceReport;
    }

    protected function getInvoiceReportData(Invoice\Entity $invoice)
    {
        $type = $invoice->getType();

        $tax = $invoice->getTax();

        $amount = $invoice->getAmount();

        // Current row
        $row = $this->getNewRow();

        $row[self::ACCOUNT_NUMBER] = $invoice->getAccountNumberAttribute();

        $balance = $this->repo->balance->getBalanceByAccountNumberOrFail($row[self::ACCOUNT_NUMBER]);

        $row[self::ACCOUNT_TYPE] = $balance->getAccountType();

        $row[self::CHANNEL] = $balance->getChannel();

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

        $reportData = $row;

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
        $from = $this->getPatchedStartDateForBillingPeriod();

        $to = $this->getPatchedEndDateForBillingPeriod();

        $from = Carbon::createFromTimestamp($from,Timezone::IST)->format(self::BILLING_PERIOD_DATE_FORMAT);
        $to = Carbon::createFromTimestamp($to,Timezone::IST)->format(self::BILLING_PERIOD_DATE_FORMAT);

        return $from . '-' . $to;
    }

    protected function getPatchedStartDateForBillingPeriod()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);
        if ($this->month == 1 and $this->year == 2021) {
            return $date->startOfDay()->subDays(1)->getTimestamp();
        }
        else {
            return $date->startOfMonth()->getTimestamp();
        }
    }

    protected function getPatchedEndDateForBillingPeriod()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1, Timezone::IST);
        if ($this->month == 12 and $this->year == 2020) {
            return $date->lastOfMonth()->subDays(1)->endOfDay()->getTimestamp();
        }
        else {
            return $date->endOfMonth()->getTimestamp();
        }
    }

    protected function getTaxComponents(string $gstin = null): array
    {
        return Calculator\Tax\IN\Utils::getTaxComponentsWithGSTIN($gstin, $this->merchant);
    }

    protected function groupDataForInvoice(Invoice\Entity $invoice, array $allRows)
    {
        foreach($allRows as $type => & $allRow)
        {
            if (empty($allRow) === true)
            {
                return [];
            }

            $finalRow = $this->getNewRow();

            $finalRow[self::DESCRIPTION] = self::TOTAL;

            $this->constructFinalRow($allRow, $finalRow);

            $allRow[self::COMBINED] = $finalRow;

            $data[$type] = $allRow;

            $data[$type][self::SELLER_ENTITY] = null;
        }

        $invoiceReport = [
            self::TITLE              => self::TAX_INVOICE,
            self::ISSUED_TO          => $this->getIssuedToDetails(),
            self::INVOICE_NUMBER     => $invoice->getInvoiceNumber(),
            self::INVOICE_ID         => $invoice->getId(),
            self::BILLING_PERIOD     => $this->getBillingPeriod(),
            self::INVOICE_DATE       => $this->getInvoiceDate($invoice->getCreatedAt()),
            self::GSTIN              => $invoice->getGstin(),
            self::ROWS               => $data,
            self::E_INVOICE_DETAILS  => [],
        ];

        return $invoiceReport;
    }

    protected function constructFinalRow(array $rows, array& $finalRow)
    {
        foreach ($rows as $row)
        {
            $finalRow[self::AMOUNT]         += $row[self::AMOUNT];
            $finalRow[self::TAX_TOTAL]      += $row[self::TAX_TOTAL];
            $finalRow[self::GRAND_TOTAL]    += $row[self::GRAND_TOTAL];
            $finalRow[self::IGST]           += $row[self::IGST];
            $finalRow[self::CGST]           += $row[self::CGST];
            $finalRow[self::SGST]           += $row[self::SGST];
        }
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
