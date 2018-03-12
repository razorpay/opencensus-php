<?php

namespace RZP\Models\FundTransfer\Attempt;

use Mail;
use Carbon\Carbon;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
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

    const LIMIT             = 2000;

    protected $fileName     = null;

    protected $fileHandler  = null;

    protected $channel      = null;

    protected $count        = 0;

    protected $startTime    = null;

    protected $endTime      = null;

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

        $this->fileName    = $this->getFileNameForReport();

        $this->fileHandler = $this->initiateFileHandler();

        $this->startTime = Carbon::today(Timezone::IST)->startOfDay()->getTimestamp();

        $this->endTime   = Carbon::now(Timezone::IST)->subHour(3)->getTimestamp();
    }

    public function sendNullUtrReport()
    {
        $channels = Channel::getChannels();

        $this->init();

        $this->trace->info(TraceCode::NULL_UTR_REPORT_INITIATED, [
            'start_time'    => $this->startTime,
            'end_time'      => $this->endTime,
        ]);

        foreach ($channels as $channel)
        {
            $this->channel = $channel;

            $this->createReport();
        }

        fclose($this->fileHandler);

        $this->trace->info(TraceCode::NULL_UTR_REPORT_FILE_CREATED, [
            'record_count'  => $this->count,
        ]);

        $this->sendEmail();
    }

    protected function initiateFilehandler()
    {
        $fileHandler = fopen($this->fileName, 'w');

        fputcsv($fileHandler, self::REPORT_HEADER);

        return $fileHandler;
    }

    protected function createReport()
    {
        $offset    = 0;

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
            'null_utr_report',
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
            'date'      => Carbon::yesterday(Timezone::IST)->format('Y-m-d')
        ];

        $reportEmail = new ReportEmail($data);

        Mail::send($reportEmail);

        return true;
    }

    protected function getFileNameForReport()
    {
        $dir  = storage_path('files/settlement');

        $date = Carbon::yesterday(Timezone::IST)->format('Y-M-d');

        return $dir . DIRECTORY_SEPARATOR . 'null_utr_report_' . $date . '.csv';
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
