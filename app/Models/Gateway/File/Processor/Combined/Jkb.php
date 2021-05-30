<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\Beam\Constants as BeamConstants;

class Jkb extends Base
{
    const BANK_NAME       = 'Jkb';
    const BEAM_FILE_TYPE  = 'combined';
    const FILE_TYPE       = FileStore\Type::JKB_NETBANKING_REFUND;

    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0
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

        $amount['total']   = number_format($amount['total'] / 100, 2, '.', '');
        $amount['refunds'] = number_format($amount['refunds'] / 100, 2, '.', '');
        $amount['claims']  = number_format($amount['claims'] / 100, 2, '.', '');

        $fromDate = Carbon::createFromTimestamp($this->gatewayFile->getBegin(), Timezone::IST)->format('d.m.Y');
        $toDate = Carbon::createFromTimestamp($this->gatewayFile->getEnd(), Timezone::IST)->format('d.m.Y');

        $date = Carbon::yesterday(Timezone::IST)->format('d.m.Y');

        $config = $this->app['config']->get('nodal.axis');

        $account = [
            'accountNumber' => $config['account_number'],
            'accountName'   => 'Razorpay Software Private Limited',
            'ifsc'          => $config['ifsc_code'],
            'bankName'      => 'Axis Bank Ltd',
            'branch'        => 'Koramangala 4th Block'
        ];

        $emailIds = [
            'recon'       => 'finances.recon@razorpay.com, amit.mohanty@razorpay.com',
            'l1'          => 'settlements@razorpay.com',
            'l2'          => 'chandrababu.g@razorpay.com',
            'transaction' => 'support@razorpay.com'
        ];

        return [
            'bankName'    => self::BANK_NAME,
            'amount'      => $amount,
            'count'       => $count,
            'date'        => $date,
            'from'        => $fromDate,
            'to'          => $toDate,
            'account'     => $account,
            'rzpEmailId'  => $emailIds,
            'emails'      => $this->gatewayFile->getRecipients(),
        ];
    }

    public function sendFile($data)
    {
        try
        {
            $refundsFile = [];

            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFileMail($mailData);

            if (isset($data['refunds']) === true)
            {
                $refundsFile = $this->getFileData(FileStore\Type::JKB_NETBANKING_REFUND);

                $fileInfo = [$refundsFile['name']];

                $bucketConfig = $this->getBucketConfig();

                $beamData =  [
                    Service::BEAM_PUSH_FILES         => $fileInfo,
                    Service::BEAM_PUSH_JOBNAME       => BeamConstants::JKB_NB_REFUND_FILE_JOB_NAME,
                    Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                    Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
                ];

                $timelines = [];

                $mailInfo = [
                    'fileInfo'  => $fileInfo,
                    'channel'   => 'tech_alerts',
                    'filetype'  => self::BEAM_FILE_TYPE,
                    'subject'   => 'J&K Refund File send failure',
                    'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
                ];

                $beamResponse = $this->app['beam']->beamPush($beamData, $timelines, $mailInfo, true);

                if ((isset($beamResponse['success']) === false) or
                    ($beamResponse['success'] === null))
                {
                    throw new GatewayErrorException(
                        ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                        null,
                        null,
                        [
                            'beam_response' => $beamResponse,
                            'gateway_file'  => $this->gatewayFile->getId(),
                            'gateway'       => 'netbanking_jkb',
                        ]
                    );
                }
            }

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
                    'id'        => $this->gatewayFile->getId(),
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

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $fileData = [
            'url'  => $signedUrl,
            'name' => $file->getLocation(),
        ];

        return $fileData;
    }
}
