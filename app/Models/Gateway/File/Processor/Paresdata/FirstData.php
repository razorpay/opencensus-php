<?php

namespace RZP\Models\Gateway\File\Processor\Paresdata;

use Cache;
use Carbon\Carbon;
use Illuminate\Cache\RedisStore;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Base\RuntimeManager;
use RZP\Gateway\FirstData\Gateway;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\Gateway\File\Processor\Base;
use RZP\Services\Beam\Service as BeamService;
use RZP\Services\Beam\Constants as BeamConstants;

class FirstData extends Base
{
    const FILE_METADATA  = [
        'gid'   => '10000',
        'uid'   => '10011',
        'mode'  => '33188'
    ];

    const FILE_NAME = 'firstdata/outgoing/{$storeId}__Auth_Reponse_{$date}';

    /** @var $cache RedisStore */
    protected $cache;

    protected $fileStoreIds;

    public function __construct()
    {
        parent::__construct();

        $this->cache = $this->app['cache'];

        RuntimeManager::setMemoryLimit('4096M');

        RuntimeManager::setTimeLimit(7200);

        RuntimeManager::setMaxExecTime(7200);
    }

    public function checkIfValidDataAvailable(PublicCollection $payments)
    {
        if ($payments->count() === 0)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    public function fetchEntities(): PublicCollection
    {
        // if a file is created through cron, its begin & end is set automatically to previous day's starting date &
        // ending date. There's no param to ignore this behaviour.
        // for firstdata, we need to create file every hour since payments count is too high to accommodate lakhs of
        // payments in one go. so, cron needs to be triggered every hour. but since the time will be set to previous day
        // automatically, begin & end timestamps are being changed here instead based on cron hit time.

        $begin = Carbon::createFromTimestamp($this->gatewayFile->getCreatedAt(), Timezone::IST)->subHour(1)->getTimestamp();

        $end = Carbon::createFromTimestamp($this->gatewayFile->getCreatedAt(), Timezone::IST)->getTimestamp();

        $paymentIds = $this->repo->payment->findFirstDataAuthSeparatedPaymentIdsBetween($begin, $end);

        $this->trace->info(
            TraceCode::PAYMENTS_SELECTED,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'count'           => count($paymentIds),
                'begin'           => $begin,
                'end'             => $end,
                'gateway'         => 'first_data',
                'entity_ids'      => $paymentIds,
            ]);

        return new PublicCollection($paymentIds);
    }

    public function generateData(PublicCollection $ids)
    {
        $ids = $ids->toArray();

        array_walk($ids, function(&$item)
        {
            $item = Gateway::PARES_DATA_CACHE_KEY . $item;
        });

        $ids =  array_chunk($ids, 100);

        $data = [];

        foreach ($ids as $chunk)
        {
            $data  += $this->cache->many($chunk);
        }

        $this->trace->info(
            TraceCode::PAYMENTS_SELECTED,
            [
                'gateway_file_id' => $this->gatewayFile->getId(),
                'count'           => count($data),
                'message'         => 'pares data retrieved from cache.',
            ]);

        return $data;
    }

    protected function formatDataForFile($rows)
    {
        $files = [];

        foreach ($rows as $row)
        {
            if (empty($row) === true)
            {
                // payments with no pares data. like international, recurring, moto etc
                continue;
            }

            $storeId = explode('|', $row)[0];

            if (empty($files[$storeId]) === true)
            {
                $files[$storeId]  = "MID|OrderId|PaRes\n";
            }

            $files[$storeId] = $files[$storeId] . $row . "\n";
        }

        return $files;
    }

    protected function getFileToWriteNameWithoutExt(array $data)
    {
        $date = Carbon::now(Timezone::IST)->format('y-m-d-H-i-s');

        $fileName = strtr(static::FILE_NAME, ['{$date}' => $date, '{$storeId}' => $data['storeId']]);

        return $fileName;
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
            $allFilesData = $this->formatDataForFile($data);

            $fileStoreIds = [];

            foreach ($allFilesData as $key => $fileData)
            {
                // since file data is grouped based on storeId, it will be part of the file name
                $fileName = $this->getFileToWriteNameWithoutExt(['storeId' => $key]);

                $creator = new FileStore\Creator;

                $creator->extension(FileStore\Format::TXT)
                        ->content($fileData)
                        ->name($fileName)
                        ->store(FileStore\Store::S3)
                        ->type(FileStore\Type::FIRST_DATA_PARES_FILE)
                        ->entity($this->gatewayFile)
                        ->metadata(static::FILE_METADATA)
                        ->save();

                $file = $creator->getFileInstance();

                $fileStoreIds[] = $file->getId();

                $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());
            }

            $this->fileStoreIds = $fileStoreIds;

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    public function sendFile($data)
    {
        $files = $this->gatewayFile
                      ->files()
                      ->whereIn(FileStore\Entity::ID, $this->fileStoreIds)
                      ->get();

        $fileInfo = [];

        foreach ($files as $file)
        {
            $fullFileName = $file->getName() . '.' . $file->getExtension();

            $fileInfo[] = $fullFileName;
        }

        $data =  [
            BeamService::BEAM_PUSH_FILES   => $fileInfo,
            BeamService::BEAM_PUSH_JOBNAME => BeamConstants::FIRST_DATA_PARES_FILE_JOB_NAME
        ];

        // In seconds
        $timelines = [900, 10 * 900, 20 * 900, 30 * 900, 100 * 900];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'tech_alerts',
            'filetype'  => FileStore\Type::FIRST_DATA_PARES_FILE,
            'subject'   => 'File Send failure',
            'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::GATEWAY_POD]
        ];

        try
        {
            $this->app['beam']->beamPush($data, $timelines, $mailInfo);

            $this->gatewayFile->setFileSentAt(Carbon::now()->getTimestamp());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'    => $this->gatewayFile->getId(),
                    'data'  => $fileInfo
                ],
                $e);
        }
    }
}
