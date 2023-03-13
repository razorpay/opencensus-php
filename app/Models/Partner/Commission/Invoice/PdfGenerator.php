<?php

namespace RZP\Models\Partner\Commission\Invoice;

use Carbon\Carbon;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Partner\Commission;

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
        $html = $this->getHtml($this->invoice);

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

    protected function getHtml(Entity $invoice): string
    {
        $data = (new Core)->getTemplateData($invoice);

        if ($invoice->merchant->getCountry() === 'MY')
        {
            $html = view('merchant.commission_invoice.my_invoice', $data)->render();
        }
        else
        {
            $html = view('merchant.commission_invoice.invoice', $data)->render();
        }

        return $html;
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
            'margin-top'        => 10,
            'margin-right'      => 0,
            'margin-bottom'     => 0,
            'margin-left'       => 0,
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
