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
    const REPORT_HEADER = [
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

    const LIMIT = 2000;

    protected $fileName = null;

    protected $fileHandler = null;

    protected $channel = null;

    protected $count = 0;

    protected $startTime = null;

    protected $endTime = null;

    protected $errorReporting = null;

    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        unlink($this->fileName);
    }

    protected function init()
    {
        $this->count = 0;

        $this->fileName = $this->getFileNameForReport();

        $this->fileHandler = $this->initiateFileHandler();

        $this->startTime = Carbon::today(Timezone::IST)->startOfDay()->getTimestamp();

        $this->endTime = Carbon::now(Timezone::IST)->subHour(3)->getTimestamp();
    }

    public function sendFTAReconReport()
    {
        $channels = Channel::getChannels();

        $this->init();

        $this->trace->info(TraceCode::FTA_RECON_REPORT_INITIATED, [
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
        ]);

        foreach ($channels as $channel)
        {
            $this->channel = $channel;

            $this->createReport();

            $this->addCriticalErrorsToReport($channel);
        }

        fclose($this->fileHandler);

        $this->trace->info(TraceCode::FTA_RECON_REPORT_FILE_CREATED);

        $this->sendEmail();

        return [
            'count' => $this->count
        ];
    }

    protected function initiateFileHandler()
    {
        $fileHandler = fopen($this->fileName, 'w');

        fputcsv($fileHandler, self::REPORT_HEADER);

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

        do {
            $records = $this->repo
                            ->fund_transfer_attempt
                            ->getFailedAttemptsInitiatedAtBetweenTime(
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

            $this->count += count($filteredRecords);

        } while ($count === self::LIMIT);
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
            if ($record->source->getBatchFundTransferId() !== $record->getBatchFundTransferId())
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

            $this->count += $count;

        } while ($count === self::LIMIT);

        $this->notify($recordCount);
    }

    protected function notify(int $count)
    {
        if ($count === 0)
        {
            return;
        }

        (new SlackNotification)->success(
            'fta_recon_report',
            [
                'channel' => $this->channel,
                'count' => $count
            ]);
    }

    protected function sendEmail()
    {
        if ($this->count === 0)
        {
            return false;
        }

        $data = [
            'file'      => $this->fileName,
            'date'      => Carbon::today(Timezone::IST)->format('Y-m-d')
        ];

        $reportEmail = new ReportEmail($data);

        //
        // Do not change it to queue
        // Reports are generated locally and deleted once the execution is complete
        //
        Mail::send($reportEmail);

        return true;
    }

    protected function getFileNameForReport()
    {
        $dir  = Utility::getStorageDir();

        $date = Carbon::today(Timezone::IST)->format('Y-M-d');

        return $dir . DIRECTORY_SEPARATOR . 'report_' . $date . '.csv';
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
}
