<?php

namespace RZP\Models\Invoice;

use Cache;
use Config;
use RZP\Http\Request\Requests;
use Mustache_Engine;
use mikehaertl\wkhtmlto\Pdf;

use RZP\Exception;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Services\UfhService;
use Illuminate\Http\UploadedFile;
use RZP\Services\Mock\UfhService as MockUfhService;

class PdfGenerator extends Base\Core
{
    // Following is cache key to get which holds templates data

    const INVOICE_PDF_TEMPLATES_KEY = 'invoice:invoices.pdf.templates';
    const INVOICE_PDF_PP_TEMPLATES_KEY = 'invoice:invoices.pdf.pp.templates';
    const INVOICE_PDF_SUB_TEMPLATES_KEY = 'invoice:invoices.pdf.subscription.templates';

    const CACHE_DEFAULT_TTL         = 60 * 15; // In seconds

    const TEMPLATE_FILE             = 'template_file';
    const CSS_FILE                  = 'css_file';

    // If cache hit is a miss, following invoicejs host path will be used
    // to fetch the templates.

    const INVOICE_PDF_TEMPLATE_PATH = '/invoice_standard.mustache';
    const INVOICE_PDF_CSS_PATH      = '/invoice.css';
    const INVOICE_PDF_PP_TEMPLATE_PATH = '/invoice_receipt.mustache';
    const INVOICE_PDF_PP_CSS_PATH      = '/invoice.css';
    const INVOICE_PDF_SUB_TEMPLATE_PATH = '/invoice_subscription.mustache';
    const INVOICE_PDF_SUB_CSS_PATH      = '/invoice.css';
    const LOCAL_FILE                    = 'local_file';

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
        $viewPayload = (new ViewDataSerializer($this->invoice))->serializeForInternal();

        $timeStarted = millitime();

        $html = $this->getHtml($viewPayload);

        $pdfContent = $this->getPdfContent($html);

        $duration = millitime() - $timeStarted;

        $this->trace->histogram(Metric::INVOICE_PDF_GEN_DURATION_MILLISECONDS, $duration, ['merchant_country_code' => (string) $this->invoice->merchant->getCountry()]);

        $file = (new FileStore\Creator())
            ->name($this->invoice->getPdfFilename())
            ->content($pdfContent)
            ->extension(FileStore\Format::PDF)
            ->mime('application/pdf')
            ->store(FileStore\Store::LOCAL)
            ->entity($this->invoice)
            ->merchant($this->invoice->merchant)
            ->type(FileStore\Type::INVOICE_PDF)
            ->save()
            ->getFileInstance();

        $localFilePath = $file->getFullFilePath();

        (new FileUploadUfh())->uploadToUfh($localFilePath, $this->invoice);

        return $file;
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
        $templateType = self::INVOICE_PDF_TEMPLATE_PATH;
        $templateKey = self::INVOICE_PDF_TEMPLATES_KEY;
        $templateCss = self::INVOICE_PDF_CSS_PATH;

        if($this->invoice->isPaymentPageInvoice() === true)
        {
            $templateType = self::INVOICE_PDF_PP_TEMPLATE_PATH;
            $templateKey = self::INVOICE_PDF_PP_TEMPLATES_KEY;
            $templateCss = self::INVOICE_PDF_PP_CSS_PATH;
        }

        if($this->invoice->isOfSubscription() === true)
        {
            $templateType = self::INVOICE_PDF_SUB_TEMPLATE_PATH;
            $templateKey = self::INVOICE_PDF_SUB_TEMPLATES_KEY;
            $templateCss = self::INVOICE_PDF_SUB_CSS_PATH;
        }

        $result = $this->cache->get($templateKey);

        if ($result !== null)
        {
            return json_decode($result, true);
        }

        $result = [];

        $result[self::TEMPLATE_FILE] = $this->getFileFromRemote($templateType);

        $result[self::CSS_FILE] = $this->getFileFromRemote($templateCss);

        $this->cache->put($templateKey, json_encode($result), self::CACHE_DEFAULT_TTL);

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
