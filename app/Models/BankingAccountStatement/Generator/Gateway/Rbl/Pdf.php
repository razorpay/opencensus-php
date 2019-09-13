<?php

namespace RZP\Models\BankingAccountStatement\Generator\Gateway\Rbl;

use View;
use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use mikehaertl\wkhtmlto\Pdf as PdfLibrary;

class Pdf extends Generator
{
    protected const TEMPLATE_FILE_NAME = 'bank_account_statement.RBL.statement';

    protected const PDF_MIME_TYPE      = 'application/pdf';

    public function getStatement()
    {
        $input = $this->accountStatementData();

        $htmlAccountStatement = View::make(self::TEMPLATE_FILE_NAME, $input);

        $pdfAccountStatement = $this->getPdfContent($htmlAccountStatement);

        $fileName = $this->accountNumber;

        $fileStoreHandle = (new FileStore\Creator())
                                        ->name($fileName)
                                        ->content($pdfAccountStatement)
                                        ->extension(FileStore\Format::PDF)
                                        ->mime(self::PDF_MIME_TYPE)
                                        ->store(FileStore\Store::S3)
                                        ->type(FileStore\Type::RBL_STATEMENT)
                                        ->save()
                                        ->getFileInstance();

        return $fileStoreHandle;
    }

    protected function getPdfContent(string $html): string
    {
        $options = [
            'print-media-type',
            'footer-font-size' => '6',
            'footer-right'     => 'Page [page] of [topage]',
            'footer-left'      => 'Date and Time: ' . Carbon::createFromTimestamp(time(), Timezone::IST)
                                                            ->format('d/m/Y h:i A'),
            'dpi'              => 290,
            'zoom'             => 1,
            'ignoreWarnings'   => false,
            'encoding'         => 'UTF-8',
            'binary'           => '/usr/local/bin/wkhtmltopdf',
        ];

        $pdf = (new PdfLibrary($options))->addPage($html);

        $pdfContent = $pdf->toString();

        if ($pdfContent === false)
        {
            throw new Exception\LogicException('Pdf generation failed: ' . $pdf->getError());
        }

        return $pdfContent;
    }
}
