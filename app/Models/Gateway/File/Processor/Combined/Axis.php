<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\Beam\Constants as BeamConstants;

class Axis extends Base
{
    const BEAM_FILE_TYPE  = 'combined';
    const FILE_TYPE       = FileStore\Type::AXIS_NETBANKING_CLAIMS;

    protected function formatDataForMail(array $data)
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

        $claimsFile = [];
        $refundsFile = [];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += ($item['refund']['amount'] / 100);

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);

            $refundsFile = $this->getFileData(FileStore\Type::AXIS_NETBANKING_REFUND);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += ($item['payment']->getAmount() / 100);

                return $sum;
            });

            $count['claims'] = count($data['claims']);

            $claimsFile = $this->getFileData(FileStore\Type::AXIS_NETBANKING_CLAIMS);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $count['total'] = $count['refunds'] + $count['claims'];

        $date = Carbon::now(Timezone::IST)->format('jS F Y');

        return [
            'bankName'    => 'Axis',
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'claimsFile'  => $claimsFile,
            'refundsFile' => $refundsFile,
            'corporate'   => $this->gatewayFile->getCorporate(),
            'emails'      => $this->gatewayFile->getRecipients()
        ];
    }

    protected function getFileLocation(string $type)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, $type)
                     ->first();

        $fileLocation = $file->getLocation();

        return $fileLocation;
    }

    public function sendFile($data)
    {
        try
        {
            $refundfileInfo = [];
            $claimfileInfo = [];
            $bucketConfig = $this->getBucketConfig();

            if (isset($data['refunds']) === true)
            {
                $refundfileInfo[] = $this->getFileLocation(FileStore\Type::AXIS_NETBANKING_REFUND);

                $beamrefundData =  [
                    Service::BEAM_PUSH_FILES         => $refundfileInfo,
                    Service::BEAM_PUSH_JOBNAME       => BeamConstants::AXIS_NB_REFUND_FILE_JOB_NAME,
                    Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                    Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
                ];
                $mailInfo = [
                    'fileInfo'  => $refundfileInfo,
                    'channel'   => 'tech_alerts',
                    'filetype'  => self::BEAM_FILE_TYPE,
                    'subject'   => 'Axis Refund File send failure',
                    'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::NBPLUS_TECH]
                ];

                $this->sendBeamRequest($beamrefundData, [], $mailInfo, true);

            }

            if (isset($data['claims']) === true)
            {
                $claimfileInfo[] = $this->getFileLocation(FileStore\Type::AXIS_NETBANKING_CLAIMS);

                $beamclaimData =  [
                    Service::BEAM_PUSH_FILES         => $claimfileInfo,
                    Service::BEAM_PUSH_JOBNAME       => BeamConstants::AXIS_NB_CLAIM_FILE_JOB_NAME,
                    Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                    Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
                ];
                $mailInfo = [
                    'fileInfo'  => $claimfileInfo,
                    'channel'   => 'tech_alerts',
                    'filetype'  => self::BEAM_FILE_TYPE,
                    'subject'   => 'Axis Claim File send failure',
                    'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::NBPLUS_TECH]
                ];

                $this->sendBeamRequest($beamclaimData, [], $mailInfo, true);
            }


            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

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
}
