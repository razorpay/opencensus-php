<?php

namespace RZP\Models\Invoice;

use View;
use Carbon\Carbon;

use mikehaertl\tmp\File;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;

class DccEInvoicePdfGenerator extends Base\Core
{
    const TEMPLATE_FILE_NAME = 'payment.invoice.invoice';
    const HEADER_FILE_NAME   = 'resources/views/payment/invoice/components/header';

    const DATE_FORMAT        = 'd/m/Y h:i A';

    const DATA               = 'data';

    const TEMP_PATH          = '/tmp/';

    const ISSUED_TO          = 'issued_to';
    const ROWS               = 'rows';
    const INVOICE_NUMBER     = 'invoice_number';
    const INVOICE_DATE       = 'invoice_date';
    const GSTIN              = 'gstin';
    const E_INVOICE_DETAILS  = 'e_invoice_details';
    const DOCUMENT_TYPE      = 'documentType';


    public function generateDccInvoice($payment, $data)
    {
        try
        {
            $this->trace->info(
                TraceCode::PAYMENT_E_INVOICE_PDF_CREATE_REQUEST,
                [
                    'data' => $data,
                ]);

            $merchantInvoicePdfContent = $this->getPdfContentForDccInvoice($payment, $data);

            $tempFileFullPath = $this->getFilePath($payment, $data);

            $fileHandle = fopen($tempFileFullPath, 'w');
            fwrite($fileHandle, $merchantInvoicePdfContent);
            fclose($fileHandle);

            return $tempFileFullPath;
        }
        catch (\Throwable $e)
        {
            $this->trace->info(
                TraceCode::PAYMENT_E_INVOICE_PDF_CREATION_FAILED,
                [
                    'error' => $e,
                ]);
            return;
        }
    }

    protected function getPdfContentForDccInvoice($payment, $data)
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

        $html = View::make(self::TEMPLATE_FILE_NAME)
            ->with(self::ISSUED_TO, $data[Constants::BUYER_DETAILS])
            ->with(self::INVOICE_NUMBER, $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_NUMBER])
            ->with(self::INVOICE_DATE, $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_DATE])
            ->with(self::GSTIN, $data[Constants::USER_GSTIN])
            ->with(self::ROWS, $data[Constants::ITEM_LIST])
            ->with(self::DOCUMENT_TYPE, $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_TYPE])
            ->with(self::E_INVOICE_DETAILS, $this->getEInvoiceDetails($payment, $data));

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

    protected function getFilePath($payment, $data)
    {
        // file name will be <payment_id>_INV for payment flow and <payment_id>_<refund_id>_CRN for refund flow
        $fileName = $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_NUMBER] . '_' . $data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_TYPE] . '.pdf';
        if ($data[Constants::DOCUMENT_DETAILS][Constants::DOCUMENT_TYPE] === Constants::CRN)
        {
            $fileName = $payment->getId() . '_' . $fileName;
        }
        return self::TEMP_PATH . $fileName;
    }

    protected function getEInvoiceDetails($payment, $data)
    {
        return [
            Constants::IRN => $data[Constants::IRN],
            Constants::QR_CODE_URL => $data[Constants::QR_CODE_URL],
            Constants::INVOICE_NUMBER_ISSUE_DATE => Date('d/m/Y', $payment->getCreatedAt()),
            Constants::INVOICE_NUMBER => $payment->getId(),
        ];
    }
}
