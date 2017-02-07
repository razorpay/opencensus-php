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
    //
    // Following is cache key to get which holds templates data
    //

    const INVOICE_PDF_TEMPLATES_KEY = 'invoices.pdf.templates';

    const TEMPLATE_FILE             = 'template_file';
    const CSS_FILE                  = 'css_file';

    //
    // If cache hit is a miss, following invoicejs host path will be used
    // to fetch the templates.
    //

    const INVOICE_PDF_TEMPLATE_PATH = '/dist/invoice_standard.mustache';
    const INVOICE_PDF_CSS_PATH      = '/dist/invoice.css';

    protected $invoicejsBaseUrl;
    protected $invoice;
    protected $redis;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->invoicejsBaseUrl = Config::get('app.invoicejs_base_url');

        $this->redis = Redis::getFacadeRoot();
    }

    public function generate(string $mode)
    {
        $viewPayload = (new ViewDataSerializer($this->invoice))->get($mode);

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
        $result = $this->getFilesFromRedisOrRemote();

        $template = $result[self::TEMPLATE_FILE];
        $css      = $result[self::CSS_FILE];

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

    protected function getFilesFromRedisOrRemote()
    {
        $result = $this->redis->get(self::INVOICE_PDF_TEMPLATES_KEY);

        if ($result !== null)
        {
            return json_decode($result, true);
        }

        $result = [];

        $result[self::TEMPLATE_FILE] = $this->getFileFromRemote(self::INVOICE_PDF_TEMPLATE_PATH);

        $result[self::CSS_FILE] = $this->getFileFromRemote(self::INVOICE_PDF_CSS_PATH);

        $this->redis->set(self::INVOICE_PDF_TEMPLATES_KEY, json_encode($result));

        return $result;
    }

    protected function getFileFromRemote(string $path)
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
