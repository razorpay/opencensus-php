<?php

namespace RZP\Models\Merchant\Invoice;

use RZP\Models\Merchant\RazorxTreatment;
use View;
use Carbon\Carbon;

use mikehaertl\tmp\File;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Merchant\Invoice\EInvoice;
use RZP\Models\Report\Types\BankingInvoiceReport;

class PdfGenerator extends Base\Core
{
    const TEMPLATE_FILE_NAME = 'merchant.invoice.invoice';
    const HEADER_FILE_NAME   = 'resources/views/merchant/invoice/components/header';

    const DATE_FORMAT                       = 'd/m/Y h:i A';
    const INVOICE_NUMBER_ISSUE_DATE_FORMAT  = 'd/m/Y';

    const DATA               = 'data';

    const TEMP_PATH = '/tmp/';

    const ISSUED_TO          = 'issued_to';
    const ROWS               = 'rows';
    const INVOICE_NUMBER     = 'invoice_number';
    const INVOICE_DATE       = 'invoice_date';
    const GSTIN              = 'gstin';
    const BILLING_PERIOD     = 'billing_period';
    const E_INVOICE_DETAILS  = 'e_invoice_details';
    const PAGE_NAME           = 'pageName';

    const MERCHANT_INVOICE_PG_PDF_PREFIX = 'merchant_pg_invoices';
    const SELLER_ENTITY = 'seller_entity';

    protected $data;

    public function generateBankingInvoice($data)
    {
        $this->trace->info(
            TraceCode::MERCHANT_BANKING_INVOICE_PDF_CREATE_REQUEST,
            [
                'data' => $data,
            ]);

        $merchantInvoicePdfContent = $this->getPdfContentForBankingInvoice($data);

        $billingPeriodString = str_replace('-', ' ', $data[BankingInvoiceReport::BILLING_PERIOD]);

        $billingPeriodString = str_replace(' ', '_', $billingPeriodString);

        $billingPeriodString = str_replace('/', '', $billingPeriodString);

        $tempFileName = $data[self::INVOICE_NUMBER] . '_' . $billingPeriodString . '.pdf';

        $tempFileFullPath = self::TEMP_PATH . $tempFileName;

        $fileHandle = fopen($tempFileFullPath, 'w');

        fwrite($fileHandle, $merchantInvoicePdfContent);

        fclose($fileHandle);

        return $tempFileFullPath;
    }

    protected function getPdfContentForBankingInvoice($data)
    {
        $options = [
            'print-media-type',
            'header-html'      => new File(self::HEADER_FILE_NAME, '.html'),
            'header-spacing'   => '-18',
            'footer-font-size' => '6',
            'footer-right'     => 'Page [page] of [topage]',
            'footer-left'      => 'Date and Time: ' . Carbon::createFromTimestamp(Carbon::now()->getTimestamp(),
                                                                                  Timezone::IST)
                                                            ->format(self::DATE_FORMAT),
            'dpi'              => 290,
            'zoom'             => 1,
            'ignoreWarnings'   => false,
            'encoding'         => 'UTF-8',
        ];

        $pdf = new Pdf($options);

        $invoiceDate = $data[BankingInvoiceReport::INVOICE_DATE];

        $dateArray = explode('/', $invoiceDate);

        $month = (int)$dateArray[1];

        $year  = (int)$dateArray[2];

        if(($month >= 9 and $year >= 2021) or $year >= 2022)
        {
            $newInvoiceDate = Carbon::createFromFormat(self::INVOICE_NUMBER_ISSUE_DATE_FORMAT, $invoiceDate, Timezone::IST)
                ->subDay()
                ->format(self::INVOICE_NUMBER_ISSUE_DATE_FORMAT);

            $data[BankingInvoiceReport::INVOICE_DATE] = $newInvoiceDate;
        }

        foreach($data[BankingInvoiceReport::ROWS] as $page => $rows)
        {
            $html = View::make(self::TEMPLATE_FILE_NAME)
                ->with(self::ISSUED_TO, $data[BankingInvoiceReport::ISSUED_TO])
                ->with(self::INVOICE_NUMBER, $data[BankingInvoiceReport::INVOICE_NUMBER])
                ->with(self::INVOICE_DATE, $data[BankingInvoiceReport::INVOICE_DATE])
                ->with(self::GSTIN, $data[BankingInvoiceReport::GSTIN])
                ->with(self::BILLING_PERIOD, $data[BankingInvoiceReport::BILLING_PERIOD])
                ->with(self::ROWS, $data[BankingInvoiceReport::ROWS][$page])
                ->with(self::PAGE_NAME, $page)
                ->with(self::SELLER_ENTITY, $data[BankingInvoiceReport::ROWS][$page][BankingInvoiceReport::SELLER_ENTITY]);

            if(isset($data[BankingInvoiceReport::E_INVOICE_DETAILS][$page]))
            {
                $invoiceIssueDate = $data[BankingInvoiceReport::E_INVOICE_DETAILS][$page]['InvoiceNumberIssueDate'];

                $dateArray = explode('/', $invoiceIssueDate);

                $month = (int)$dateArray[1];

                $year  = (int)$dateArray[2];

                if(($month >= 9 and $year >= 2021) or $year >= 2022)
                {
                    $newInvoiceIssueDate = Carbon::createFromFormat(self::INVOICE_NUMBER_ISSUE_DATE_FORMAT,
                        $invoiceIssueDate, Timezone::IST)
                        ->subDay()
                        ->format(self::INVOICE_NUMBER_ISSUE_DATE_FORMAT);

                    $data[BankingInvoiceReport::E_INVOICE_DETAILS][$page]['InvoiceNumberIssueDate'] = $newInvoiceIssueDate;
                }

                $html = $html->with(self::E_INVOICE_DETAILS, $data[BankingInvoiceReport::E_INVOICE_DETAILS][$page]);
            }

            $pdf->addPage($html);
        }

        $pdfContent = $pdf->toString();

        if ($pdfContent === false)
        {
            throw new Exception\LogicException(
                'Pdf generation failed: ' . $pdf->getError(),
                ErrorCode::SERVER_ERROR_INVOICE_PDF_GENERATION_FAILED,
                [
                    'pdf_options' => $options
                ]);
        }

        return $pdfContent;
    }

    public function generatePgInvoice($merchantId, $month, $year, $invoiceBreakup): Filestore\Entity
    {
        $name = $this->getNameForMerchantPgInvoice($year, $month, $merchantId);

        $merchant = $this->repo->merchant->findOrFailPublicWithRelations($merchantId, ['merchantDetail']);

        $eInvoiceData = (new EInvoice\PgEInvoice())->getEInvoiceDataForPdf($merchantId, $month, $year, EInvoice\Types::PG);

        $html = $this->getHtml($merchant, $month, $year, $invoiceBreakup, $eInvoiceData);

        $pdfContent = $this->getPdfContentForPgInvoice($html);

        return (new FileStore\Creator())
            ->name($name)
            ->content($pdfContent)
            ->extension(FileStore\Format::PDF)
            ->mime('application/pdf')
            ->store(FileStore\Store::S3)
            ->merchant($merchant)
            ->type(FileStore\Type::MERCHANT_INVOICE)
            ->save()
            ->getFileInstance();
    }

    protected function getHtml($merchant, $month, $year, $invoiceBreakup, $eInvoiceData = []) : string
    {
        $data = (new Core())->getTemplateDataForPgInvoice($merchant, $month, $year, $invoiceBreakup, $eInvoiceData) ;

        if($this->isMerchantPGInvoiceV2($merchant->getId()) === true)
        {
            $view = ($data['isGstApplicable'] === true) ?'merchant.pg_invoice.invoiceV2' : 'merchant.pg_invoice.invoice_old';
        }
        else {
            $view = ($data['isGstApplicable'] === true) ?'merchant.pg_invoice.invoice' : 'merchant.pg_invoice.invoice_old';
        }

        return view($view, $data)->render();
    }

    protected function getPdfContentForPgInvoice(string $html): string
    {
        $options = [
            'print-media-type',
            'footer-font-size'  => '9',
            'footer-center'     => 'Page [page] of [topage]',
            'zoom'              => 1,
            'ignoreWarnings'    => false,
            'encoding'          => 'UTF-8',
        ];

        $pdf = (new Pdf($options))->addPage($html);

        $pdfContent = $pdf->toString();

        if ($pdfContent === false)
        {
            throw new Exception\LogicException('Pdf generation failed: ' . $pdf->getError());
        }

        return $pdfContent;
    }

    public function getNameForMerchantPgInvoice(int $year, int $month, $merchantId)
    {
        return self::MERCHANT_INVOICE_PG_PDF_PREFIX . '/' . $year . '/'. $month . '/' . $merchantId;
    }

    public function getNameForMerchantPgRevisedInvoice(int $year, int $month, $merchantId)
    {
        return self::MERCHANT_INVOICE_PG_PDF_PREFIX . '/revised/' . $year . '/'. $month . '/' . $merchantId;
    }

    public function isMerchantPGInvoiceV2($merchantId): bool
    {
        $mode = $this->app['rzp.mode'] ?? 'live';

        $result = $this->app->razorx->getTreatment(
            $merchantId, RazorxTreatment::MERCHANT_PG_INVOICE_V2, $mode);

        $this->trace->info(
            TraceCode::MERCHANT_PG_INVOICE_V2_RAZORX,
            [
                'result' => $result,
                'mode' => $mode,
                'merchant_id' => $merchantId,
            ]);

        return (strtolower($result) === RazorxTreatment::RAZORX_VARIANT_ON);
    }
}
