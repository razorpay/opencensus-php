<?php

namespace RZP\Reconciliator\Freecharge;

use Carbon\Carbon;
use RZP\Http\Request\Requests;
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

}
