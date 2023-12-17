<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use Throwable;
use Carbon\Carbon;
use Monolog\Logger;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Mail\Gateway\DailyFile;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Services\Beam\Constants as BeamConstants;

class Canara extends Base
{
    const BEAM_FILE_TYPE = "combined";
    const FILE_TYPE      = FileStore\Type::CANARA_NETBANKING_REFUND;

    protected function formatDataForMail(array $data): array
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        $count = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0
        ];

        $refundsFile = $claimsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $amount['refunds'] = $amount['refunds'] / 100;

            $amount['refunds'] = number_format($amount['refunds'], 2, '.', '');

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::CANARA_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $amount['claims'] = $amount['claims'] / 100;

            $amount['claims'] = number_format($amount['claims'], 2, '.', '');

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::CANARA_NETBANKING_CLAIMS);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total'] = number_format($amount['total'], 2, '.', '');

        $count['total'] = $count['refunds'] + $count['claims'];

        $date['payment'] = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('jS F Y');

        $date['refund'] = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('jS F Y');

        $txnDate = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('dmY');

        return [
            'bankName'    => 'Canara',
            'subject'     => 'PG RECON DATA ' . $txnDate,
            'amount'      => $amount,
            'count'       => $count,
            'refundsFile' => $refundsFile,
            'claimsFile'  => $claimsFile,
            'date'        => $date,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }

    /**
     * @throws GatewayFileException
     */
    public function sendFile($data): void
    {
        try
        {
            $fileInfo = [];

            if (isset($data['refunds']) === true)
            {
                $fileInfo[] = $this->getFileDataForSftp(FileStore\Type::CANARA_NETBANKING_REFUND);
            }

            if (isset($data['claims']) === true)
            {
                $fileInfo[] = $this->getFileDataForSftp(FileStore\Type::CANARA_NETBANKING_CLAIMS);
            }

            $bucketConfig = $this->getBucketConfig();

            $beamData =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => BeamConstants::CANARA_NETBANKING_COMBINED_PUSH,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'tech_alerts',
                'filetype'  => self::BEAM_FILE_TYPE,
                'subject'   => 'Canara Combined File send failure',
                'recipient' => Constants::MAIL_ADDRESSES[Constants::NBPLUS_TECH]
            ];

            $this->app['beam']->beamPush($beamData, $timelines, $mailInfo);

            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFile($mailData);

            Mail::send($dailyFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($data['refunds'] ?? []);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::INFO,
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE, [
                    'id' => $this->gatewayFile->getId()
                ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE, [
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function getFileDataForSftp(string $type)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, $type)
            ->first();

        return $file->getLocation();
    }
}
