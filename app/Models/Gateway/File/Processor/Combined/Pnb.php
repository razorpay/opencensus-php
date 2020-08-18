<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Type;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Services\Beam\Service;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants as MailConstants;
use RZP\Services\Beam\Constants as BeamConstants;

class Pnb extends Base
{
    const BANK_NAME = 'Pnb';
    const BEAM_FILE_TYPE  = 'combined';

    public function sendFile($data)
    {
        try
        {
            $refundsFile = [];

            $claimsFile = [];

            if (isset($data['refunds']) === true)
            {
                $refundsFile = $this->getFileData(FileStore\Type::PNB_NETBANKING_REFUND);
            }

            if (isset($data['claims']) === true)
            {
                $claimsFile = $this->getFileData(FileStore\Type::PNB_NETBANKING_CLAIMS);
            }

            $fileInfo = [$claimsFile , $refundsFile];

            $beamData =  [
                Service::BEAM_PUSH_FILES   => $fileInfo,
                Service::BEAM_PUSH_JOBNAME => BeamConstants::PNB_NB_COMBINED_FILE_JOB_NAME
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

        $fileLocation = $file->getLocation();

        return $fileLocation;
    }

    public function generateData(PublicCollection $entities)
    {
        $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

        if ($entities->get('refunds')->isNotEmpty() === true)
        {
            $data['refunds'] = $refundFileProcessor->generateData($entities->get('refunds'));
        }

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

        $refundPaymentsArray = [];
        $filteredRefunds = [];
        $reversedPayments = [];

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

        if ($entities->get('claims')->isNotEmpty() === true)
        {
            $data['claims'] = $claimFileProcessor->generateData($entities->get('claims'));
        }

        return $this->updateClaimsWithReversedFlag($data, $reversedPayments);
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
}
