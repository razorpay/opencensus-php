<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Reconciliator\Base\Reconciliate as BaseReconciliate;

class IndusindDebit extends Base
{
    const BANK_CODE   = IFSC::INDB;
    const GATEWAY     = "indusind_debit_emi";
    const FILE_NAME   = 'IndusInd_Emi_File';
    const FILE_TYPE   = FileStore\Type::INDUS_IND_DEBIT_EMI_FILE;
    const COMPRESSION_REQUIRED = false;
    const GATEWAY_REFERENCE_ID_1 = 'gateway_reference_id1';

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
        $gateway = Payment\Gateway::INDUSIND_DEBIT_EMI;

        $emiPayments = $this->repo
            ->payment
            ->fetchDebitEmiPaymentsWithRelationsBetween(
                $begin,
                $end,
                static::BANK_CODE,
                $gateway);

        $refundPayments = $this->repo
            ->refund
            ->fetchDebitEmiRefundsWithRelationsBetween(
                $begin,
                $end,
                static::BANK_CODE,
                $gateway);

        return $emiPayments->merge($refundPayments);
    }

    protected function getFileToWriteName(): string
    {
        $date = Carbon::now(Timezone::IST)->format('dmY');

        return self::FILE_NAME . '_' . $date . '_' . $this->totalTransactions;
    }

    protected function sendEmiFile($data)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig();

        $data = [
            Service::BEAM_PUSH_FILES          => $fileInfo,
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::INDUS_IND_DEBIT_EMI_FILE_JOB_NAME,
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
                    'job_name'      => BeamConstants::INDUS_IND_DEBIT_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Indusind Debit',
                ]
            );
        }
    }

    public function generateData(PublicCollection $emiPayments): array
    {
        $data['items'] = $emiPayments->all();

        return $data;
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(self::FILE_TYPE, $this->env);

        return $config[$bucketType];
    }


    protected function formatDataForFile($data): array
    {
        $formattedData = [];

        $totalTransactions = 0;

        $cpsAuthorizationDetails = $this->fetchCPSAuthorizationData($data, [BaseReconciliate::GATEWAY_TRANSACTION_ID, self::GATEWAY_REFERENCE_ID_1]);

        foreach ($data['items'] as $emiPayment)
        {
            $emiPlan = $emiPayment->emiPlan;

            $principalAmount = $emiPayment->getAmount() / 100;

            $ncEmiOfferPercent = 0;
            $ncEmiOfferAmount = 0;
            $rate = 0;
            $tenure = 0;
            $emiAmount = 0;

            if($emiPlan !== null)
            {
                $rate = $emiPlan->getRate() / 100;
                $tenure = $emiPlan->getDuration();
                $emiAmount = $this->getEmiAmount($principalAmount, $rate, $tenure);

                $offerDetails = $emiPayment->getOffer();

                $emiSubvention = $offerDetails ? $offerDetails->getEmiSubvention() : null;

                // We have to populate only NC EMI Offer Details
                if ($emiSubvention === true and $emiPayment->hasOrder())
                {
                    $order = $this->repo->order->fetchForPayment($emiPayment);
                    $ncEmiOfferAmount = ($order->getAmount() - $order->getAmountPaid()) / 100;
                    $ncEmiOfferPercent = ($ncEmiOfferAmount / $order->getAmount()) * 100;
                }
            }

            $entityId = null;

            if($emiPayment->getEntity() === BaseReconciliate::PAYMENT)
            {
                $entityId = $emiPayment['id'];
            }
            else
            {
                $entityId = $emiPayment[BaseReconciliate::PAYMENT_ID];
            }

            $txnRefNumber = $cpsAuthorizationDetails[$entityId][BaseReconciliate::GATEWAY_TRANSACTION_ID] ?? '';
            $loanEAgreementNumber = $cpsAuthorizationDetails[$entityId][self::GATEWAY_REFERENCE_ID_1] ?? '';

            $formattedData[] = [
                'EMI ID'                       => $emiPayment->getId(),
                'IBL_Txn_Ref_Number'           => $txnRefNumber,
                'IBL_Loan_E_Agreement_Number'  => $loanEAgreementNumber,
                'Card Hash'                    => '',
                'Loan Amount'                  => $principalAmount,
                'EMI Amount'                   => $emiAmount,
                'Tx Time'                      => $emiPayment->getEntity() === 'refund'? $this->formattedDateFromTimestamp($emiPayment->getCreatedAt()) :
                                                    $this->formattedDateFromTimestamp($emiPayment->getAuthorizeTimestamp()),
                'Issuer'                       => 'Indusind Debit',
                'RRN'                          => '',
                'Auth Code'                    => '',
                'Tx Amount'                    => $principalAmount,
                'EMI_Offer'                    => $tenure . ' Months',
                'Manufacturer'                 => '',
                'Acquirer'                     => '',
                'MID'                          => '',
                'TID'                          => '',
                'Settlement Time'              => $emiPayment->getEntity() === 'refund'? $this->formattedDateFromTimestamp($emiPayment->getCreatedAt()) :
                                                   $this->formattedDateFromTimestamp($emiPayment->getCaptureTimestamp()),
                'Customer Processing Fee'      => '',
                'Customer Processing Amount (Rs.)' => '',
                'Other Discount %'             => '',
                'Other discount Amount'        => '',
                'Other discount Amount'        => '',
                'Interest Rate'                => $rate,
                'Tx Status'                    => $emiPayment->getEntity() === 'refund'? 'Refund' : 'Settled',
                'Product Category'             => '',
                'Product Sub-Category 1'       => '',
                'Product Sub-Category 2'       => '',
                'Model Name'                   => '',
                'Discount / Cashback %'        => $ncEmiOfferPercent,
                'Discount / Cashback Amount'   => $ncEmiOfferAmount,
                'Is New Model'                 => '',
                'Offers / Additional Cashbacks'=> 'NA',
                'Reward Point'                 => '',
                'Merchant Name'                => $emiPayment->merchant->getDbaName() ?: 'Razorpay Payments',
                'Address1'                     => '',
                'Store City'                   => '',
                'Store State'                  => '',
                'Card Pan'                     => '',
                'Partner Name'                 => 'Razorpay',
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

    protected function sendEmiPassword($data)
    {
        return;
    }

    private function formattedDateFromTimestamp($timestamp): string
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
    }
}
