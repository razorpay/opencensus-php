<?php

namespace RZP\Models\FundTransfer\Attempt;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\FileStore\Utility;
use RZP\Models\Settlement\Channel;
use RZP\Models\Settlement\SlackNotification;
use RZP\Mail\Settlement\Report as ReportEmail;

class Report extends Base\Core
{
    const FTA_PROGRESS = 'fta_progress';

    const FTA_FAILURES = 'fta_failures';

    const PROGRESS_REPORT_HEADER = [
        'Channel',
        'Attempt ID',
        'Source',
        'Source ID',
        'UTR',
        'Status',
        'Bank Status Code',
        'Remarks',
        'Merchant ID',
        'Merchant Name',
        'Merchant Email'
    ];

    const FAILURE_REPORT_HEADER = [
        'Source ID',
        'Source',
        'Merchant ID',
        'Merchant Name',
        'Bank Status Code',
        'Remarks',
        'Failure Reason',
        'Created On'
    ];

    const REPORT_DATA = [
        self::FTA_PROGRESS => [
            'headers'  => self::PROGRESS_REPORT_HEADER,
        ],
        self::FTA_FAILURES => [
            'headers'  => self::FAILURE_REPORT_HEADER,
        ]
    ];

    const LIMIT = 2000;

    protected $fileName = null;

    protected $fileHandler = null;

    protected $channel = null;

    protected $startTime = null;

    protected $endTime = null;

    protected $errorReporting = null;

    protected $count = 0;

    protected $summary = [];

    protected $merchantInfo = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        unlink($this->fileName); // nosemgrep : php.lang.security.unlink-use.unlink-use
    }

    protected function initialize(string $type)
    {
        $this->count = 0;

        $this->fileName    = $this->getFileNameForReport($type);

        $this->fileHandler = $this->initiateFileHandler($type);

        $this->startTime   = Carbon::today(Timezone::IST)->startOfDay()->getTimestamp();

        $this->endTime     = Carbon::now(Timezone::IST)->subMinute(30)->getTimestamp();
    }

    public function sendFTAReconReport(string $type)
    {
        $channels = Channel::getChannels();

        $this->initialize($type);

        $this->trace->info(TraceCode::FTA_RECON_REPORT_INITIATED, [
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
        ]);

        foreach ($channels as $channel)
        {
            $this->channel = $channel;

            $channelCount = $this->createReport();

            $channelCount += $this->addCriticalErrorsToReport($channel);

            $this->count += $channelCount;

            if ($channelCount !== 0)
            {
                $this->summary[] = ['channel' => $channel, 'count' => $channelCount];
            }
        }

        fclose($this->fileHandler);

        $this->trace->info(TraceCode::FTA_RECON_REPORT_FILE_CREATED);
    }

    protected function initiateFileHandler(string $type)
    {
        $fileHandler = fopen($this->fileName, 'w');

        fputcsv($fileHandler, self::REPORT_DATA[$type]['headers']);

        return $fileHandler;
    }

    protected function getStatusClass(string $channel)
    {
        return 'RZP\\Models\\FundTransfer\\'
                . ucfirst($channel)
                . '\\Reconciliation\\Status';
    }

    protected function addCriticalErrorsToReport(string $channel)
    {
        $statusClass = $this->getStatusClass($channel);

        if (class_exists($statusClass) === false)
        {
            return;
        }

        $offset = 0;

        $recordCount =0;

        do {
            $records = $this->repo
                            ->fund_transfer_attempt
                            ->getFailedAttemptsCreatedBetweenTime(
                                $channel,
                                $this->startTime,
                                $this->endTime,
                                self::LIMIT,
                                $offset);

            $filteredRecords = $this->filterRecordsForErrors(
                                            $statusClass,
                                            $records);

            $offset += self::LIMIT;

            $this->createOrUpdateFile($filteredRecords);

            $count = count($records);

            $recordCount += count($filteredRecords);

        } while ($count === self::LIMIT);

        return $recordCount;
    }

    /**
     * It will check for the type of error in remark field of given records
     * If the given error type is not defined will return all the
     * If the given error type is defined then records which match the errors will be returned
     *
     * @param string $statusClass
     * @param $records
     *
     * @return array
     */
    protected function filterRecordsForErrors(string $statusClass, $records)
    {
        $filteredRecords = [];

        $hasCriticalErrors = $statusClass::hasCriticalErrors();

        if ($hasCriticalErrors === false)
        {
            return [];
        }

        foreach ($records as $record)
        {
            if (($record->getSourceType() !== Type::REFUND) and ($record->source->getBatchFundTransferId() !== $record->getBatchFundTransferId()))
            {
                continue;
            }

            $isCriticalError = $statusClass::isCriticalError($record);

            if ($isCriticalError === true)
            {
                $filteredRecords[] = $record;
            }
        }

        return $filteredRecords;
    }

    protected function createReport()
    {
        $offset = 0;

        $recordCount = 0;

        do
        {
            $records = $this->repo
                            ->fund_transfer_attempt
                            ->getSettlementsWithNoUtr(
                                $this->channel,
                                $this->startTime,
                                $this->endTime,
                                self::LIMIT,
                                $offset);

            $offset += self::LIMIT;

            $this->createOrUpdateFile($records);

            $count = count($records);

            $recordCount += $count;

        } while ($count === self::LIMIT);

        return $recordCount;
    }

    public static function notify(Report $progressReport, Report $failureReport)
    {
        if (($progressReport->count === 0) and ($failureReport->count === 0))
        {
            return;
        }

        $result          = [];

        $count           = $progressReport->count + $failureReport->count;

        $progressSummary = $progressReport->getSummary();

        $failureSummary  = $failureReport->getSummary();

        foreach ($progressSummary as $reportInfo)
        {
            $result['channel'][] = $reportInfo['channel'];

            $result['count'][]   = $reportInfo['count'];
        }

        $result += $failureSummary;

        (new SlackNotification)->send(
            'fta_recon_report',
            $result,
            null,
            $count);
    }

    public static function sendEmail(Report $progressReport, Report $failureReport)
    {
        $info = 'Fund Transfer Potential Failures';

        $data = [
            'header'  => $info,
            'subject' => $info . Carbon::today(Timezone::IST)->format('Y-m-d'),
            'date'    => Carbon::today(Timezone::IST)->format('Y-m-d'),
            'summary' => [],
        ];

        $attachments = [];

        if ($progressReport->count !== 0)
        {
            $attachments[] = $progressReport->getFileName();
        }

        if ($failureReport->count !== 0)
        {
            $attachments[] = $failureReport->getFileName();

            $data['summary'] = $failureReport->getMerchantInfo();
        }

        if (empty($attachments) === true)
        {
            return false;
        }

        $data['attachments'] = array_values($attachments);

        $reportEmail = new ReportEmail($data);

        //
        // Do not change it to queue
        // Reports are generated locally and deleted once the execution is complete
        //
        Mail::send($reportEmail);

        return true;
    }

    protected function getFileNameForReport(string $type)
    {
        $dir  = Utility::getStorageDir();

        $date = Carbon::today(Timezone::IST)->format('Y-M-d');

        return $dir . DIRECTORY_SEPARATOR . $type . '_' . $date . '.csv';
    }

    protected function createOrUpdateFile($records)
    {
        foreach ($records as $record)
        {
            $data = [
                $this->channel,
                $record->getId(),
                $record->getSourceType(),
                $record->getSourceId(),
                $record->getUtr(),
                $record->getStatus(),
                $record->getBankStatusCode(),
                $record->getRemarks(),
                $record->merchant->getId(),
                $record->merchant->getName(),
                $record->merchant->getEmail(),
            ];

            fputcsv($this->fileHandler, $data);
        }
    }

    public function sendFTAFailureReport(string $type)
    {
        $this->initialize($type);

        $this->trace->info(TraceCode::FTA_FAILURE_REPORT_INITIATED, [
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
        ]);

         $this->count = $this->createFailureReport();

        if ($this->count !== 0)
        {
            $this->summary['failureCount'] = $this->count;
        }

        fclose($this->fileHandler);

        $this->trace->info(TraceCode::FTA_FAILURE_REPORT_FILE_CREATED);
    }

    protected function createFailureReport()
    {
        $offset = 0;

        $recordCount = 0;

        do
        {
            $records = $this->repo
                            ->fund_transfer_attempt
                            ->getFailedSettlements(
                                $this->startTime,
                                $this->endTime,
                                self::LIMIT,
                                $offset);

            $offset += self::LIMIT;

            $this->createOrUpdateFailureFile($records);

            $count = count($records);

            $recordCount += $count;

        } while ($count === self::LIMIT);

        return $recordCount;
    }

    protected function createOrUpdateFailureFile($records)
    {
        foreach ($records as $record)
        {
            $createdDate = Carbon::createFromTimestamp($record->getCreatedAt(), Timezone::IST);

            $merchantName = $record->merchant->getName();

            $data = [
                $record->getSourceId(),
                $record->getSourceType(),
                $record->merchant->getId(),
                $merchantName,
                $record->getBankStatusCode(),
                $record->getRemarks(),
                $record->getFailureReason(),
                $createdDate->format('d-m-Y')
            ];

            $this->merchantInfo[] = [
                'merchant_name' => $merchantName,
                'amount'        => $record->source->getAmount()/100
            ];

            fputcsv($this->fileHandler, $data);
        }
    }

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getSummary()
    {
        return $this->summary;
    }

    public function getMerchantInfo()
    {
        return $this->merchantInfo;
    }
}
