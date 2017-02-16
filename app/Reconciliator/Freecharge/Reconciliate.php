<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use Requests;
use Storage;
use Symfony\Component\DomCrawler\Crawler;
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

    public function getSettlementFileLink(string $html)
    {
        //
        // The file link is hyperlink to VIEW REPORT
        // Crawl the body-html, fetch the DomElement and
        // extract 'href' attribute value
        //
        $crawler = new Crawler($html);
        $filter = $crawler->filter('a');

        foreach ($filter as $i => $content)
        {
            $element = new Crawler($content);

            if ($element->html() === 'VIEW REPORT')
            {
                return $element->attr('href');
            }
        }
    }
}
