<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Services\Beam\Constants as BeamConstants;

class Pnb extends Base
{
    const BANK_NAME       = 'Pnb';
    const BEAM_FILE_TYPE  = 'combined';
    const FILE_TYPE       = FileStore\Type::PNB_NETBANKING_CLAIMS;

    protected function formatDataForMail(array $data)
    {
        $amount = [
            'claims'  => 0,
            'refunds' => 0,
            'total'   => 0,
        ];

        if (isset($data['refunds']) === true)
        {
            $amount['refunds'] = array_reduce($data['refunds'], function ($sum, $item)
            {
                $sum += $item['refund']['amount'];

                return $sum;
            });
        }

        if (isset($data['claims']) === true)
        {
            foreach ($data['claims'] as $claim)
            {
                if ($claim['is_reversed'] === true)
                {
                    $amount['refunds'] += $claim['payment']->getAmount();
                }

                $amount['claims'] += $claim['payment']->getAmount();
            }
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
            'emails'      => $this->gatewayFile->getRecipients(),
            'account'     => $account,
        ];
    }

    public function sendFile($data)
    {
        try
        {
            $fileInfo = [];

            if (isset($data['refunds']) === true)
            {
                $fileInfo[] = $this->getFileData(FileStore\Type::PNB_NETBANKING_REFUND);
            }

            if (isset($data['claims']) === true)
            {
                $fileInfo[] = $this->getFileData(FileStore\Type::PNB_NETBANKING_CLAIMS);
            }

            $bucketConfig = $this->getBucketConfig();

            $beamData =  [
                Service::BEAM_PUSH_FILES         => $fileInfo,
                Service::BEAM_PUSH_JOBNAME       => BeamConstants::PNB_NB_COMBINED_FILE_JOB_NAME,
                Service::BEAM_PUSH_BUCKET_NAME   => $bucketConfig['name'],
                Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
            ];

            // In seconds
            $timelines = [];

            $mailInfo = [
                'fileInfo'  => $fileInfo,
                'channel'   => 'tech_alerts',
                'filetype'  => self::BEAM_FILE_TYPE,
                'subject'   => 'Pnb Combined File send failure',
                'recipient' => MailConstants::MAIL_ADDRESSES[MailConstants::SETTLEMENT_ALERTS]
            ];

            $this->app['beam']->beamPush($beamData, $timelines, $mailInfo);

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
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
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

    public function generateData(PublicCollection $entities)
    {
        $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

        $refundPaymentsArray = [];
        $filteredRefunds = [];
        $reversedPayments = [];

        if ($entities->get('refunds')->isNotEmpty() === true)
        {
            $data['refunds'] = $refundFileProcessor->generateData($entities->get('refunds'));

            /*
             * PNB Claims file has to be same as their recon file with the status column filled for every payment
             * if a payment was refunded on the same day it was authorized it has to be sent as 'failed' in the claim file
             *
             * Please note that the payment sent as failed in claims file CANNOT be sent in the refund file.
             *
             * A payment can be there in claims file as 'success' and have a corresponding refund to that payment in the refund file
             * only if it is a partial refund
            */

            /*
             * Following code traverses through the refunds and calculates the total refund amount for that payment
             *
             * if the refunded amount is not equal to the payment amount or authorized_at is less than begin date of gateway file
             * then it means that the payment is partially refunded therefore we add it to the filteredRefunds
             * else it will mean that the payment was fully refunded on the same day (with either partial refunds or a full refund)
             * therefore we maintain a different array of all those payments which were reversed
             */

            foreach ($data['refunds'] as $refund)
            {
                $totalRefunds = $refund['refund']['amount'];

                $refundId = $refund['refund']['id'];

                $paymentId = $refund['payment']['id'];

                if (in_array($paymentId, $refundPaymentsArray) === false)
                {
                    foreach ($data['refunds'] as $ref)
                    {
                        if (($ref['refund']['id'] != $refundId) and ($ref['payment']['id'] === $paymentId))
                        {
                            $totalRefunds += $ref['refund']['amount'];
                        }
                    }

                    $refundPaymentsArray [] = $paymentId;

                    if (($totalRefunds != $refund['payment']['amount']) or ($refund['payment']['authorized_at'] < $this->gatewayFile->getBegin()))
                    {
                        $filteredRefunds[] = $refund;
                    }
                    else
                    {
                        $reversedPayments[] = $paymentId;
                    }
                }
            }

            $data['refunds'] = $filteredRefunds;

            if (empty($filteredRefunds) === true)
            {
                unset($data['refunds']);
            }
        }

        if ($entities->get('claims')->isNotEmpty() === true)
        {
            $data['claims'] = $claimFileProcessor->generateData($entities->get('claims'));

            $data = $this->updateClaimsWithReversedFlag($data, $reversedPayments);
        }

        return $data;
    }

    public function updateClaimsWithReversedFlag($data, $reversedPayments)
    {
        /*
         * The payment's which were seen as reversed before are marked here with flag 'is_reversed' => true
         */

        foreach ($data['claims'] as $key => $value)
        {
            if (in_array($value['payment']['id'], $reversedPayments))
            {
                $data['claims'][$key]['is_reversed'] = true;
            }
            else
            {
                $data['claims'][$key]['is_reversed'] = false;
            }
        }

        return $data;
    }

    protected function getFormattedAmount($amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
