<?php

namespace RZP\Models\Invoice;

use Cache;
use Config;
use Requests;
use Mustache_Engine;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;

class PdfGenerator extends Base\Core
{
    // Following is cache key to get which holds templates data

    const INVOICE_PDF_TEMPLATES_KEY = 'invoices.pdf.templates';

    const CACHE_DEFAULT_TTL         = 900; // In seconds (=15 min)

    const TEMPLATE_FILE             = 'template_file';
    const CSS_FILE                  = 'css_file';

    // If cache hit is a miss, following invoicejs host path will be used
    // to fetch the templates.

    const INVOICE_PDF_TEMPLATE_PATH = '/invoice_standard.mustache';
    const INVOICE_PDF_CSS_PATH      = '/invoice.css';

    protected $invoicejsBaseUrl;
    protected $invoice;
    protected $cache;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->invoicejsBaseUrl = Config::get('app.cdn_v1_url');

        $this->cache = Cache::getFacadeRoot();
    }

    public function generate(): FileStore\Entity
    {
        $viewPayload = (new ViewDataSerializer($this->invoice))->get();

        $timeStarted = microtime(true);

        $html = $this->getHtml($viewPayload);

        $pdfContent = $this->getPdfContent($html);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->debug(
            TraceCode::INVOICE_PDF_GEN_TIME_TAKEN,
            [
                'id'         => $this->invoice->getId(),
                'time_taken' => $timeTaken,
            ]);

        return (new FileStore\Creator())
                    ->name($this->invoice->getPdfFilename())
                    ->content($pdfContent)
                    ->extension(FileStore\Format::PDF)
                    ->mime('application/pdf')
                    ->store(FileStore\Store::S3)
                    ->entity($this->invoice)
                    ->merchant($this->invoice->merchant)
                    ->type(FileStore\Type::INVOICE_PDF)
                    ->save()
                    ->getFileInstance();
    }

    protected function getPdfContent(string $html): string
    {
        $options = [
            'print-media-type',
            'footer-font-size'  => '9',
            'footer-center'     => 'Page [page] of [topage]',
            'dpi'               => 290,
            'zoom'              => 1.28,
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

    protected function getHtml(array $viewPayload): string
    {
        $result = $this->getFilesFromRedisOrRemote();

        $template = $result[self::TEMPLATE_FILE];
        $css = $result[self::CSS_FILE];

        $body = (new Mustache_Engine())->render($template, $viewPayload);

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

    protected function getFilesFromRedisOrRemote(): array
    {
        $result = $this->cache->get(self::INVOICE_PDF_TEMPLATES_KEY);

        if ($result !== null)
        {
            return json_decode($result, true);
        }

        $result = [];

        $result[self::TEMPLATE_FILE] = $this->getFileFromRemote(self::INVOICE_PDF_TEMPLATE_PATH);

        $result[self::CSS_FILE] = $this->getFileFromRemote(self::INVOICE_PDF_CSS_PATH);

        $this->cache->put(self::INVOICE_PDF_TEMPLATES_KEY, json_encode($result), self::CACHE_DEFAULT_TTL);

        return $result;
    }

    protected function getFileFromRemote(string $path): string
    {
        $url = $this->invoicejsBaseUrl . $path;

        $res = Requests::get($url);

        if ($res->status_code !== 200)
        {
            throw new Exception\LogicException("Received $res->status_code for $url]");
        }

        return $res->body;
    }
}
