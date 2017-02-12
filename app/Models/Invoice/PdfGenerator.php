<?php

namespace RZP\Models\Invoice;

use Illuminate\Support\Facades\Redis;
use Requests;
use Config;
use mikehaertl\wkhtmlto\Pdf;
use Mustache_Engine;

use RZP\Models\Base;
use RZP\Models\FileStore;
use RZP\Exception;
use RZP\Trace\TraceCode;

class PdfGenerator extends Base\Core
{
    //
    // Following is cache key to get which holds templates data
    //

    const INVOICE_PDF_TEMPLATES_KEY = 'invoices.pdf.templates';

    const REDIS_DEFAULT_TTL         = 900;

    const TEMPLATE_FILE             = 'template_file';
    const CSS_FILE                  = 'css_file';

    const WKHTMLTOPDF_BIN           = 'wkhtmltopdf';

    //
    // If cache hit is a miss, following invoicejs host path will be used
    // to fetch the templates.
    //

    const INVOICE_PDF_TEMPLATE_PATH = '/invoice_standard.mustache';
    const INVOICE_PDF_CSS_PATH      = '/invoice.css';

    protected $invoicejsBaseUrl;
    protected $invoice;
    protected $redis;

    public function __construct(Entity $invoice)
    {
        parent::__construct();

        $this->invoice = $invoice;

        $this->invoicejsBaseUrl = Config::get('app.cdn_v1_url');

        $this->redis = Redis::getFacadeRoot();
    }

    public function generate(string $mode)
    {
        $viewPayload = (new ViewDataSerializer($this->invoice))->get($mode);

        $timeStarted = microtime(true);

        $html = $this->getHtml($viewPayload);

        $pdfContent = $this->getPdfContent($html);

        $timeTaken = microtime(true) - $timeStarted;

        $this->trace->debug(
            TraceCode::INVOICE_PDF_GEN_TIME_TAKEN,
            [
                'id'         => $this->invoice->getId(),
                'time_taken' => $timeTaken,
            ]
        );

        return (new FileStore\Creator())
                    ->name($this->invoice->getPdfKey())
                    ->content($pdfContent)
                    ->extension(FileStore\Format::PDF)
                    ->mime('application/pdf')
                    ->store(FileStore\Store::S3)
                    ->entity($this->invoice)
                    ->type(FileStore\Type::INVOICE_PDF)
                    ->save()
                    ->getFullFilePath();
    }

    protected function getPdfContent(string $html)
    {
        $options = [
            // 'binary'         => base_path(self::WKHTMLTOPDF_BIN),
            'ignoreWarnings' => false,
        ];

        $pdf = (new Pdf($options))->addPage($html);

        $pdfContent = $pdf->toString();

        //
        // TODO:
        // Remove these misc trace codes later.
        //

        $this->trace->debug(
            TraceCode::TRACE_MISC_CODE,
            [
                'options'       => $options,
                'error'         => $pdf->getError(),
                'command'       => $pdf->getCommand(),
                'filename'      => $pdf->getPdfFilename(),
                'content_empty' => ($pdfContent === false),
            ]);

        if ($pdfContent === false)
        {
            throw new Exception\LogicException('Pdf generation failed: Content is empty.');
        }

        $file = new \SplFileObject($pdf->getPdfFilename());

        $this->trace->debug(
            TraceCode::TRACE_MISC_CODE,
            [
                $file->getExtension(),
                $file->getSize(),
                $file->getType(),
                $file->isFile(),
                $file->isReadable(),
                mime_content_type($pdf->getPdfFilename())
            ]);

        return $pdfContent;
    }

    protected function getHtml(array $viewPayload)
    {
        $result = $this->getFilesFromRedisOrRemote();

        $template = $result[self::TEMPLATE_FILE];
        $css      = $result[self::CSS_FILE];

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

        $this->redis->setex(self::INVOICE_PDF_TEMPLATES_KEY, self::REDIS_DEFAULT_TTL, json_encode($result));

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
