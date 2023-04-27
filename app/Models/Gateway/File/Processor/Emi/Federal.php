<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;
use RZP\Models\Emi;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;

class Federal extends Base
{
    const BANK_CODE         = IFSC::FDRL;
    const FILE_TYPE         = FileStore\Type::FEDERAL_EMI_FILE;
    const FILE_NAME         = 'Federal_Emi_File';
    const DATE_FORMAT       = 'd/m/Y h:i:s A';
    const BEAM_FILE_TYPE    = 'emi';
    const EXTENSION         = FileStore\Format::TXT;
    const COMPRESSION_REQUIRED = false;
    protected $totalAmount;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        return $this->repo
            ->payment
            ->fetchEmiPaymentsOfCobrandingPartnerAndBankWithRelationsBetween(
                $begin,
                $end,
                null,
                static::BANK_CODE,
                [
                    'card.globalCard',
                    'emiPlan',
                    'merchant.merchantDetail',
                    'terminal'
                ]);
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $emiRate = $emiPayment->emiPlan['rate'];

            $emiPercent = $emiRate/100;

            $formattedData[] = [
                'Emi Plan ID'                  => $emiPayment->getId(),
                'Card last 4'                  => str_repeat('*', 12) . $emiPayment -> card -> getLast4(),
                'Auth ID'                      => $this -> getAuthCode($emiPayment),
                'Txn amount'                   => $this -> getFormattedAmount($emiPayment -> getAmount()),
                'Aggregator Merchant name'     => 'RAZORPAY',
                'EMI Tenor'                    => $emiTenure,
                'Store name'                   => $merchant -> getBillingLabel(),
                'Transaction post date'        => $this -> getFormattedDate($emiPayment -> getCaptureTimestamp()),
                'Interest rate'                => $emiPercent.'%',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );
        }

        $data = [];

        foreach ($formattedData as $headers => $body)
        {
            if(count($data) == 0)
            {
                $data[] = implode("|", array_keys($body));
            }
            $data[] = implode("|", $body);
        }

        return implode("\r\n", $data);
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('Ymd');

        $fileToWriteName = 'RAZORPAY_CCEMI_' . $date;

        $filePath = 'federal/outgoing/';

        return $filePath . $fileToWriteName;
    }

    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data = [
            Service::BEAM_PUSH_FILES          => $fileInfo,
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::FEDERAL_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME    => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => 'emi',
            'subject'   => 'File Send failure',
            'recipient' => [
                Constants::MAIL_ADDRESSES[Constants::AFFORDABILITY],
                Constants::MAIL_ADDRESSES[Constants::FINOPS],
                Constants::MAIL_ADDRESSES[Constants::DEVOPS_BEAM],
            ],
        ];

        $beamResponse = $this->app['beam']->beamPush($data, $timelines, $mailInfo, true);

        if ((isset($beamResponse['success']) === false) or
            ($beamResponse['success'] === null))
        {
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
                null,
                null,
                [
                    'beam_response' => $beamResponse,
                    'filestore_id'  => $this->file->getId(),
                    'gateway_file'  => $this->gatewayFile->getId(),
                    'job_name'      => BeamConstants::FEDERAL_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Federal',
                ]
            );
        }
    }

    protected function sendEmiPassword($data)
    {
        return;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount/100, 2, '.', '');
    }

}
