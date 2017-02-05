<?php

namespace RZP\Models\Invoice;

use Illuminate\Support\Facades\Redis;
use Requests;
use Config;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Exception;

class PdfGenerator extends Base\Core
{
    const INVOICE_PDF_TEMPLATE_KEY  = 'invoice_pdf_template_key';
    const INVOICE_PDF_CSS_KEY       = 'invoice_pdf_css_key';
    const INVOICE_PDF_TEMPLATE_PATH = '/dist/invoice_standard.mustache';
    const INVOICE_PDF_CSS_PATH      = '/dist/invoice.css';

    protected $invoicejsBaseUrl;
    protected $invoice;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->invoicejsBaseUrl = Config::get('app.invoicejs_base_url');
    }

    public function generate()
    {
        $viewPayload = (new ViewDataSerializer($this->invoice))->get('test');

        $html = $this->getHtml($viewPayload);

        $pdf = new \mikehaertl\wkhtmlto\Pdf($html);

        return (new FileStore\Creator())
                    ->name($this->invoice->getPdfKey())
                    ->content($pdf->toString())
                    ->extension(FileStore\Format::PDF)
                    ->mime('application/pdf')
                    ->store(FileStore\Store::LOCAL)
                    ->entity($this->invoice)
                    ->type(FileStore\Type::INVOICE_PDF)
                    ->save()
                    ->getFullFilePath();
    }

    protected function getHtml(array $viewPayload)
    {
        $template = $this->getFileFromRedisOrRemote(self::INVOICE_PDF_TEMPLATE_KEY, self::INVOICE_PDF_TEMPLATE_PATH);

        $css      = $this->getFileFromRedisOrRemote(self::INVOICE_PDF_CSS_KEY, self::INVOICE_PDF_CSS_PATH);

        $body = (new \Mustache_Engine())->render($template, $viewPayload);

        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    $css
                </style>
            </head>
            <body>
                $body
            </body>
            </html>
        ";
    }

    protected function getFileFromRedisOrRemote(string $key, string $path)
    {
        $hit = Redis::get($key);

        if ($hit !== null)
        {
            return $hit;
        }

        $url = $this->invoicejsBaseUrl . $path;

        $res = Requests::get($url);

        if ($res->status_code !== 200)
        {
            throw new Exception\LogicException("Received $res->status_code for $url]");
        }

        $body = $res->body;

        Redis::set($key, $body);

        return $body;
    }
}
