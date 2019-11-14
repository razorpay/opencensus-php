<?php

namespace RZP\Models\Merchant\Invoice;

use View;
use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use RZP\Models\Report\Types\BankingInvoiceReport;

class PdfGenerator extends Base\Core
{
    const TEMPLATE_FILE_NAME    =   'merchant.invoice.invoice';
    const DATE_FORMAT           =   'd/m/Y h:i A';
    const DATA                  =   'data';

    const TEMP_PATH             =   '/tmp/';

    const SUMMARY               =   'summary';
    const ISSUED_TO             =   'issued_to';
    const PAGES                 =   'pages';
    const INVOICE_NUMBER        =   'invoice_number';
    const INVOICE_DATE          =   'invoice_date';
    const GSTIN                 =   'gstin';
    const BILLING_PERIOD        =   'billing_period';

    protected $data;

    public function generate($data)
    {
        $this->trace->info(
            TraceCode::MERCHANT_BANKING_INVOICE_PDF_CREATE_REQUEST,
            [
                'data' => $data,
            ]);

        $merchantInvoicePdfContent = $this->getPdfContent($data);

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

    protected function getPdfContent($data)
    {
        $html = View::make(self::TEMPLATE_FILE_NAME)
                    ->with(self::SUMMARY, $data[BankingInvoiceReport::SUMMARY_TITLE])
                    ->with(self::ISSUED_TO, $data[BankingInvoiceReport::ISSUED_TO])
                    ->with(self::PAGES, $data[BankingInvoiceReport::PAGES])
                    ->with(self::INVOICE_NUMBER, $data[BankingInvoiceReport::INVOICE_NUMBER])
                    ->with(self::INVOICE_DATE, $data[BankingInvoiceReport::INVOICE_DATE])
                    ->with(self::GSTIN, $data[BankingInvoiceReport::GSTIN])
                    ->with(self::BILLING_PERIOD, $data[BankingInvoiceReport::BILLING_PERIOD]);

        $options = [
            'print-media-type',
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

        $pdf->addPage($html);

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
}
