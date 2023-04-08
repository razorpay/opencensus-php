<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Services\Beam\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\Beam\Constants as BeamConstants;

class Ubi extends Base
{
    const BANK_NAME       = 'Ubi';
    const BEAM_FILE_TYPE  = 'combined';
    const FILE_TYPE       = FileStore\Type::UBI_NETBANKING_REFUND;

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
        ];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });

            $count['refunds'] = count($data['refunds']);
        }

        if (isset($data['claims']) === true)
        {
            $amount['claims'] = array_reduce($data['claims'], function ($sum, $item)
            {
                $sum += $item['payment']->getAmount();

                return $sum;
            });

            $count['claims'] = count($data['claims']);
        }

        $amount['total'] = $amount['claims'] - $amount['refunds'];

        $amount['total'] = $this->getFormattedAmount($amount['total']);

        $amount['refunds'] = $this->getFormattedAmount($amount['refunds']);

        $amount['claims'] = $this->getFormattedAmount($amount['claims']);

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited - Axis Bank Nodal A/c',
            'ifsc'          => $config['ifsc_code'],
            'bank'          => 'Axis Bank Limited',
        ];

        return [
            'bankName'    => self::BANK_NAME,
            'amount'      => $amount,
            'count'       => $count,
            'emails'      => $this->gatewayFile->getRecipients(),
            'account'     => $account,
        ];
    }

    public function sendFile($data)
    {
        try
        {

            if (isset($data['refunds']) === true)
            {
                $refundsFile = $this->getFileData(FileStore\Type::UBI_NETBANKING_REFUND);

                $fileInfo = [$refundsFile];

                $bucketConfig = $this->getBucketConfig();

                $beamData =  [
                    Service::BEAM_PUSH_FILES         => $fileInfo,
                    Service::BEAM_PUSH_JOBNAME       => BeamConstants::UBI_NB_REFUND_FILE_JOB_NAME,
                    Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                    Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
                ];

                // In seconds
                $timelines = [];

                $mailInfo = [
                    'fileInfo'  => $fileInfo,
                    'channel'   => 'tech_alerts',
                    'filetype'  => self::BEAM_FILE_TYPE,
                    'subject'   => 'Ubi Combined File send failure',
                    'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::NBPLUS_TECH]
                ];

                $this->app['beam']->beamPush($beamData, $timelines, $mailInfo);

            }
            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

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
                    'id' => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function getFileData(string $type)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, $type)
            ->first();

        $fileLocation = $file->getLocation();

        return $fileLocation;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
