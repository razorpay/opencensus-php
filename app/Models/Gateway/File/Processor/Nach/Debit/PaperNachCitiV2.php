<?php

namespace RZP\Models\Gateway\File\Processor\Nach\Debit;

use Mail;
use Carbon\Carbon;

use RZP\Gateway\Enach;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Gateway\File\Metric;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\ServerErrorException;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as Headings;


class PaperNachCitiV2 extends PaperNachCiti
{
    const FILE_NAME         = 'citi/nach/ACH-DR-CITI-CITI137272-{$date}-RZP{$serialNumber}-INP';
    const SUMMARY_FILE_NAME = 'citi/nach/ACH-DR-CITI-CITI137272-{$date}-RZP{$serialNumber}-INP-SUMMARY';

    public function __construct()
    {
        parent::__construct();
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }
        try
        {
            if ($this->isTestMode() === true)
            {
                $this->pageCount = 3;
            }

            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                $totalCount = count($fileData);

                $presentCount = 0;

                $serialNumber = 1;

                $this->getCacheKeySerialNumber($serialNumber, $cacheKey);

                while ($presentCount < $totalCount)
                {
                    if ($presentCount % 50000 === 0)
                    {
                        $this->trace->info(TraceCode::NACH_DEBIT_REQUEST, [
                            'index'        => $presentCount,
                            'utility_code' => $key,
                            'total_count'  => $totalCount,
                            'serialNumber' => $serialNumber,
                        ]);
                    }

                    $fileDataPerSheet = array_slice($fileData, $presentCount, $this->pageCount, true);

                    $fileHeader = $this->getFileHeader($key, $fileDataPerSheet);

                    $fileHeaderText = $this->getTextData($fileHeader, "", "");

                    $fileDataText = $this->getTextData($fileDataPerSheet, $fileHeaderText, "");

                    $fileName = $this->getFileToWriteNameWithoutExt(
                        [
                            'fileName'     => static::FILE_NAME,
                            'utilityCode'  => $key,
                            'serialNumber' => $serialNumber,
                        ]);

                    $creator = new FileStore\Creator;

                    $creator->extension(static::EXTENSION)
                        ->content($fileDataText)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(static::FILE_TYPE)
                        ->entity($this->gatewayFile)
                        ->metadata(static::FILE_METADATA)
                        ->save();

                    $file = $creator->getFileInstance();

                    $fileStoreIds[] = $file->getId();

                    $amount = 0;

                    foreach ($fileDataPerSheet as $data)
                    {
                        $amount = $amount + $data[Headings::AMOUNT];
                    }

                    $date = $this->getHeaderDate();

                    $summaryRow = [
                        0 => [
                            Headings::UTILITY_CODE    => $key,
                            Headings::NO_OF_RECORDS   => count($fileDataPerSheet),
                            Headings::TOTAL_AMOUNT    => $amount,
                            Headings::SETTLEMENT_DATE => $date,
                        ]
                    ];

                    $summaryFileName = $this->getFileToWriteNameWithoutExt(
                        [
                            'fileName'     => static::SUMMARY_FILE_NAME,
                            'utilityCode'  => $key,
                            'serialNumber' => $serialNumber,
                        ]);

                    $creatorSummary = new FileStore\Creator;

                    $creatorSummary->extension(static::SUMMARY_EXTENSION)
                        ->content($summaryRow)
                        ->name($summaryFileName)
                        ->store(FileStore\Store::S3)
                        ->type(static::SUMMARY_FILE_TYPE)
                        ->entity($this->gatewayFile)
                        ->metadata(static::FILE_METADATA)
                        ->save();

                    $file = $creatorSummary->getFileInstance();

                    $fileStoreIds[] = $file->getId();

                    // cache index of utility code for 24hours (in seconds)
                    $serialNumber = $this->cache->increment($cacheKey);

                    $this->trace->info(TraceCode::CACHE_KEY_SET, [
                        'key'    => $cacheKey,
                        'result' => $serialNumber,
                    ]);

                    $presentCount = $presentCount + $this->pageCount;
                }
            }

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->fileStore = $fileStoreIds;

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

            $this->trace->info(
                TraceCode::NACH_DEBIT_FILE_GENERATED,
                [
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);

            $this->generateMetric(Metric::EMANDATE_FILE_GENERATED);

            $this->fileGenerationProcessAsync($this->gatewayFile->getId(), "GEN_CITI");
        }
        catch (\Throwable $e)
        {
            $this->generateMetric(Metric::EMANDATE_FILE_GENERATION_ERROR);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ],
                $e);
        }
    }

    /**
     * @throws GatewayFileException
     */
    public function fetchEntities(): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->getTimestamp();

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_INIT);

        try
        {
            $tokens = $this->repo->token->fetchPendingNachOrMandateDebit(
                [Payment\Gateway::ENACH_NPCI_NETBANKING, Payment\Gateway::NACH_CITI],
                $begin,
                $end,
                Payment\Gateway::ACQUIRER_CITI);
        }
        catch (ServerErrorException $e)
        {
            $this->generateMetric(Metric::EMANDATE_DB_ERROR);

            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'target' => $this->gatewayFile->getTarget(),
                    'type'   => $this->gatewayFile->getType()
                ]);
        }

        $this->trace->info(TraceCode::GATEWAY_FILE_QUERY_COMPLETE);

        foreach ($tokens as $key => $token)
        {
            $tokenBegin = $begin;

            if ($token->merchant->isEarlyMandatePresentmentEnabled() === true)
            {
                while ($tokenBegin < $end)
                {
                    $nineAM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(9);
                    $threePM = Carbon::createFromTimestamp($tokenBegin, Timezone::IST)->startOfDay()->addHours(15);
                    $createdAt = $token['payment_created_at'];
                    /*
                     * the payments done from previous day 9am to 3pm should not be considered here as
                     * these payments will be part of mutual fund exclusive timing cycle (9am to 3pm)
                     */
                    if (($createdAt >= $nineAM->timestamp) and ($createdAt < $threePM->timestamp))
                    {
                        unset($tokens[$key]);
                    }
                    $tokenBegin = $tokenBegin + Carbon::HOURS_PER_DAY * Carbon::MINUTES_PER_HOUR * Carbon::SECONDS_PER_MINUTE;
                }
            }
        }

        $paymentIds = $tokens->pluck('payment_id')->toArray();

        $this->trace->info(
            TraceCode::NACH_DEBIT_REQUEST,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'begin'           => $begin,
                'end'             => $end,
                'entity_count'    => count($paymentIds),
                'target' => $this->gatewayFile->getTarget(),
                'type'   => $this->gatewayFile->getType()
            ]);

        return $tokens;
    }

    protected function getFileToWriteNameWithoutExt(array $data): string
    {
        $date = $this->getDate();

        $formatSerialNumber = $this->formatSerialNumber($data['serialNumber']);

        $fileName = strtr($data['fileName'], ['{$date}' => $date, '{$serialNumber}' => $formatSerialNumber]);

        if (isset($data['utilityCode']) === true)
        {
            $fileName = strtr($fileName, ['{$utilityCode}' => $data['utilityCode']]);
        }

        if ($this->isTestMode() === true)
        {
            return $fileName . '_' . $this->mode;
        }

        return $fileName;
    }

    protected function getHeaderDate(): string
    {
        $offset = (int) $this->gatewayFile->getSubType();

        return Carbon::now(Timezone::IST)->addDays($offset)->format('dmY');
    }

}
