<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\GatewayFileException;
use RZP\Mail\Base\Constants;
use RZP\Models\Bank\IFSC;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity as EmiPlanEntity;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Models\Gateway\File\Status;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Services\Beam\Service;
use RZP\Trace\TraceCode;
use RZP\Models\Card;

class AxisCiti extends Base
{
    const BANK_CODE   = IFSC::UTIB;
    const EXTENSION   = FileStore\Format::CSV;
    const FILE_TYPE   = FileStore\Type::AXIS_EMI_FILE;
    const FILE_NAME   = 'Citi_Emi_File';
    const DATE_FORMAT = 'd-m-Y';
    const BEAM_FILE_TYPE = 'emi';
    const COMPRESSION_REQUIRED     = false;
    const CITI_BINS = [
        '407439',
        '540706',
        '471861',
        '540899',
        '418688',
        '365594',
        '405450',
        '405451',
        '430463',
        '438106',
        '438587',
        '438628',
        '455038',
        '456407',
        '456822',
        '458448',
        '461795',
        '461796',
        '461797',
        '485903',
        '486541',
        '493714',
        '517700',
        '518371',
        '518936',
        '520386',
        '524133',
        '526421',
        '529117',
        '529495',
        '531662',
        '540165',
        '540175',
        '541497',
        '542556',
        '544170',
        '549852',
        '552093',
        '552137',
        '554619',
        '554637',
    ];

    public function fetchEntities(): PublicCollection
    {
        $emiPayments = parent::fetchEntities();

        $filteredEmiPayments = $emiPayments->reject(function($emiPayment) {

            $cardActualIin = $this->getCardActualIin($emiPayment['vault_token'], $emiPayment['token_iin']);

            $isCitiBin = in_array($cardActualIin, self::CITI_BINS);

            if(!$isCitiBin)
            {
                return true;
            }
            return false;
        });

        return $filteredEmiPayments;
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        foreach ($data['items'] as $emiPayment)
        {
            if ($emiPayment->terminal->isOptimizer())
            {
                continue;
            }

            $emiTenure = $emiPayment->emiPlan['duration'];

            $merchant = $emiPayment->merchant;

            $rateofinterest = $emiPayment->emiPlan[EmiPlanEntity::RATE] / 100;

            $txn = $this->repo->transaction->findByEntityIdWithoutMerchantTidb($emiPayment->getId());

            $formattedData[] = [
                'Card Number'                  => str_repeat("X", 12) . $emiPayment->card->getLast4(),
                'Transaction Amount'           => $emiPayment->getAmount() / 100,
                'Transaction Date'             => $this->getFormattedDate($emiPayment->getCaptureTimestamp()),
                'Settlement Date'              => $this->getFormattedDate($txn->getSettledAt()),
                'Authorisation Id'             => $this->getAuthCode($emiPayment),
                'Merchant Name'                => $emiPayment->merchant->getDbaName() ?: 'Razorpay Payments',
                'MCC (Merchant Category Code)' => $merchant->getCategory(), // Non Mandatory,
                'Tenure'                       => $emiTenure,
                'Rate of Interest'             => number_format($rateofinterest, 2, '.', ''),
                'Source'                       => 'Razorpay',
                'EMI ID'                       => $emiPayment->getId(), // Non Mandatory, filling with our payment id
                'Identifier'                   => 'Citi',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => 'Axis Citi',
                ]
            );
        }

        return $formattedData;
    }

    protected function getCardActualIin($vaultToken, $tokenIin)
    {
        $card = [
            'token_iin'     => $tokenIin,
            'vault_token'   => $vaultToken,
        ];

        $card = (new Card\Entity)->fill($card);

        return $card->getIin();
    }

    protected function sendEmiPassword($data)
    {
        return;
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
        $date = Carbon::now(Timezone::IST)->format('d-m-Y');

        return self::FILE_NAME . '_' . $date;
    }

    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data = [
            Service::BEAM_PUSH_FILES          => $fileInfo,
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::AXIS_CC_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME    => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo'  => $fileInfo,
            'channel'   => 'settlements',
            'filetype'  => self::BEAM_FILE_TYPE,
            'subject'   => 'AXIS EMI - File Send failure',
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
                    'job_name'      => BeamConstants::AXIS_CC_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Axis',
                ]
            );
        }
    }
}
