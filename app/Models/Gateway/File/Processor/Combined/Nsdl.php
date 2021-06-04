<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Services\Beam\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;

class Nsdl extends Base
{
    const BANK_NAME       = 'Nsdl';
    const BEAM_FILE_TYPE  = 'combined';
    const FILE_TYPE       = FileStore\Type::NSDL_NETBANKING_CLAIM;

    public function sendFile($data)
    {
        try
        {
            $fileInfo = [];

            if (isset($data['refunds']) === true)
            {
                $fileInfo[] = $this->getFileData(FileStore\Type::NSDL_NETBANKING_REFUND);
            }

            if (isset($data['claims']) === true)
            {
                $fileInfo[] = $this->getFileData(FileStore\Type::NSDL_NETBANKING_CLAIM);
            }

            $bucketConfig = $this->getBucketConfig();

            $beamData =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => BeamConstants::NSDL_NB_COMBINED_FILE_JOB_NAME,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];


            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'tech_alerts',
                'filetype'  => self::BEAM_FILE_TYPE,
                'subject'   => 'Nsdl Combined File send failure',
                'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
            ];

            $this->app['beam']->beamPush($beamData, $timelines, $mailInfo);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($data['refunds'] ?? []);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::INFO,
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id' => $this->gatewayFile->getId()
                ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
