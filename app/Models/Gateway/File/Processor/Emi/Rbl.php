<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use App;
use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Models\Emi\Entity;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;

class Rbl extends Base
{
    const BANK_CODE   = IFSC::RATN;
    const FILE_TYPE   = FileStore\Type::RBL_EMI_FILE;
    const FILE_NAME   = 'Rbl_Emi_File';
    const DATE_FORMAT = 'd-m-Y';

    protected $totalTransactions;

    protected function sendEmiFile($data)
    {
        $fullFileName = 'rbl-emi/' . $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data = [
            Service::BEAM_PUSH_FILES          => $fileInfo,
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::RBL_EMI_FILE_JOB_NAME,
            Service::BEAM_PUSH_BUCKET_NAME    => 'rzp-1415-prod-sftp',
            Service::BEAM_PUSH_BUCKET_REGION  => $bucketConfig['region'],
        ];

        // In seconds
        $timelines = [];

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
                    'job_name'      => BeamConstants::RBL_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Rbl',
                ]
            );
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(self::FILE_TYPE, $this->env);

        return $config[$bucketType];
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        $rrn = $this->getRrnNumber($data['items']);

        $totalTransactions = 0;

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount() / 100;

            $rate = $emiPlan[Entity::RATE] / 100;

            $tenure = $emiPlan[Entity::DURATION];

            $issuerPlanId = $emiPlan[Entity::ISSUER_PLAN_ID];

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

            $formattedData[] = [
                'EMIID'                           => $emiPayment->getId(),
                'Card'                            => 'XX' . $emiPayment->card->getLast4(),
                'Issuer'                           => 'RBL',
                'Acquirer'                         => '',
                'Aggregator Merchant Name'         => 'RAZORPAY',
                'Manufacturer'                     => '',
                'RRN'                              => $rrn[$emiPayment->getId()]['rrn'] ?? '',
                'Auth'                             => 'XX' . str_pad($this->getAuthCode($emiPayment), 6, '0', STR_PAD_LEFT),
                'Tx Amount'                        => $principalAmount,
                'tenure'                           => $tenure . ' Months',
                'plan'                             => $issuerPlanId,
                'Customer Name'                    => '',
                'Mobile'                           => '',
                'Address'                          => '',
                'Email'                            => '',
                'Store Name'                       => '',
                'Address1'                         => '',
                'Store City'                       => '',
                'Place'                            => '',
                'MID'                              => '',
                'TID'                              => '',
                'Tx Time'                          => $this->getFormattedDate($emiPayment->getAuthorizeTimestamp()),
                'Subvention Amount (Rs.)'          => '',
                'Interest Rate'                    => $rate,
                'Customer Processing Fee'          => '',
                'Customer Processing Amount (Rs.)' => '',
                'Tx Status'                        => '',
                'Status'                           => 'Success',
                'Description'                      => 'Online',
                'Product Category'                 => '',
                'Product Sub-Category 1'           => '',
                'Product Sub-Category 2'           => '',
                'Model Name'                       => '',
                'Merchant Name'                    => '',
                'EMI Amount'                       => $emiAmount,
                'Loan Amount'                      => $principalAmount,
                'Discount / Cashback %'            => '',
                'Discount / Cashback Amount'       => '',
                'Additional Cashback'              => '',
                'Bonus Reward Points'              => '',
                'EMI Model'                        => 'Y',
            ];

            $this->trace->info(TraceCode::EMI_PAYMENT_SHARED_IN_FILE,
                [
                    'payment_id' => $emiPayment->getId(),
                    'bank'       => static::BANK_CODE,
                ]
            );

            $totalTransactions++;
        }

        $this->totalTransactions = $totalTransactions;

        return $formattedData;
    }

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is a
        // $monthlyRate is a/12 i.e should be treated as 13/1200
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = $annualRate / 1200;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return floor($num / $den);
    }

    protected function getRrnNumber($data)
    {
        $CPS_PARAMS = [
            \RZP\Reconciliator\Base\Constants::RRN
        ];

        $paymentIds = array();

        foreach ($data as $payment)
        {
            array_push($paymentIds, $payment->id);
        }

        $request = [
            'fields'        => $CPS_PARAMS,
            'payment_ids'   => $paymentIds,
        ];

        $response = App::getFacadeRoot()['card.payments']->fetchAuthorizationData($request);

        return $response;
    }

    protected function getFileToWriteName()
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');
        $count = $this->totalTransactions;

        return static::FILE_NAME . $date . $count;
    }
}
