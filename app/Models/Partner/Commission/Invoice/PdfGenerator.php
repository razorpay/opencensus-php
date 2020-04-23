<?php

namespace RZP\Models\Partner\Commission\Invoice;

use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;

class PdfGenerator extends Base\Core
{
    protected $invoice;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;
    }

    public function generate(): FileStore\Entity
    {
        $viewPayload = $this->invoice->toArrayPublic();

        $html = $this->getHtml($viewPayload);

        $pdfContent = $this->getPdfContent($html);

        return (new FileStore\Creator())
            ->name($this->invoice->getPdfFilename())
            ->content($pdfContent)
            ->extension(FileStore\Format::PDF)
            ->mime('application/pdf')
            ->store(FileStore\Store::S3)
            ->entity($this->invoice)
            ->merchant($this->invoice->merchant)
            ->type(FileStore\Type::COMMISSION_INVOICE)
            ->save()
            ->getFileInstance();
    }

    protected function getHtml(array $viewPayload): string
    {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                </style>
            </head>
            <body>
                Invoice Pdf
            </body>
            </html>
        ";
    }

    protected function getPdfContent(string $html): string
    {
        $options = [
            'print-media-type',
            'footer-font-size'  => '9',
            'footer-center'     => 'Page [page] of [topage]',
            'dpi'               => 290,
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

}
