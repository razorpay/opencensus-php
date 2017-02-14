<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use Requests;
use Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use RZP\Models\Payment\Gateway;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\Orchestrator;
use RZP\Reconciliator\FileProcessor;
use RZP\Trace\TraceCode;

class Reconciliate extends Base\Reconciliate
{
    /**
     * Figures out what kind of reconciliation is it
     * depending on the file name. It should be either
     * 'refund', 'payment' or 'combined'.
     * 'combined' is used when a file has both payments and refunds reports.
     * In case of excel sheets, the file name is the sheet name
     * and not the excel file name.
     *
     * @param string $fileName
     * @return null|string
     */
    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getNumLinesToSkip()
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 0,
            FileProcessor::LINES_FROM_BOTTOM => 3
        ];
    }

    public function getSettlementFileLink(string $text)
    {
        //
        // Link lies between 'VIEW REPORT' and  'Best, Team Freecharge'
        // By splitting the 'stripped-text', get the link
        //
        $rawText = trim(explode('VIEW REPORT', $text)[1]);
        $rawText = trim(explode('Best', $rawText)[0]);

        return stripcslashes(trim($rawText, '<>'));
    }

    public function getSettlementFileFromLink(string $link)
    {
        $request = [
            'url'     => stripcslashes($link),
            'method'  => 'GET',
            'headers' => [],
            'content' => [],
            'options' => [
                'timeout'          => 60,
                'follow_redirects' => true,
                'verify'           => true,
            ],
        ];

        $response = Requests::request(
            $request['url'],
            $request['headers'],
            $request['content'],
            $request['method'],
            $request['options']);

        $now = Carbon::now('Asia/Kolkata')->toDateString();

        $fileName = 'freecharge-settlement-' . $now . '.zip';

        $filePath = storage_path('files/settlement') . '/' . $fileName;

        file_put_contents($filePath, fopen($response->url, 'r'));

        return new UploadedFile(
            $filePath, $fileName, 'application/zip', filesize($filePath));
    }
}
