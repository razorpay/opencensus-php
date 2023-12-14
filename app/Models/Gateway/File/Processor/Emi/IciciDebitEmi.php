<?php

namespace RZP\Models\Gateway\File\Processor\Emi;

use App;
use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Bank\IFSC;
use RZP\Constants\Timezone;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Exception\GatewayErrorException;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;

class IciciDebitEmi extends Base
{
    const BANK_CODE   = IFSC::ICIC;
    const FILE_TYPE   = FileStore\Type::ICICI_DEBIT_EMI_FILE;
    const FILE_NAME   = 'icici_rzp_dcemi_txn';
    const DATE_FORMAT = 'dmy';
    const ICICI_DEBIT_EMI       = 'icici_debit_emi';

    public function fetchEntities(): PublicCollection
    {

        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
        $gateway = Payment\Gateway::HITACHI;

        $emiPayments = $this->repo
            ->payment
            ->fetchEmiPaymentsWithGatewayAndCardType(
                $begin,
                $end,
                static::BANK_CODE,
                $gateway,
                'debit'
            );

        return $emiPayments;
    }

    protected function getFileToWriteName()
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
            Service::BEAM_PUSH_JOBNAME        => BeamConstants::ICICI_DEBIT_EMI_FILE_JOB_NAME,
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
                    'job_name'      => BeamConstants::ICICI_DEBIT_EMI_FILE_JOB_NAME,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Icici Debit Emi',
                ]
            );
        }
    }

    // Don't zip the file so don't need to send password
    protected function sendEmiPassword($data)
    {
        return;
    }


    public function generateData(PublicCollection $emiPayments): array
    {
        $data['items'] = $emiPayments->all();

        return $data;
    }

    public function createFile($data)
    {
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteName();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->headers(false)
                ->entity($this->gatewayFile)
                ->metadata(static::FILE_METADATA);

            $creator->save();

            $this->file = $creator->getFileInstance();


            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE, [
                'id'        => $this->gatewayFile->getId(),
            ],
                $e);
        }
    }

    protected function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(self::FILE_TYPE, $this->env);

        return $config[$bucketType];
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

    protected function getEmiAmount($amount, $annualRate, $tenureInMonths)
    {
        // $annualRate is rate/100, say a
        // $monthlyRate is a/12 i.e should be treated as .14/12
        // E = P x r x (1+r)^n/((1+r)^n – 1)
        // tenure in months

        $monthlyRate = ($annualRate / 100) / 12;

        $expression = pow((1 + $monthlyRate), $tenureInMonths);

        $num = $amount * $monthlyRate * $expression;

        $den = $expression - 1;

        return (round($num / $den));
    }

    protected function formatDataForFile($data)
    {
        $formattedData = [];

        $headers = [
            'Merchant Track ID',
            'CardNumber',
            'TransactionDate',
            'Issuer',
            'RRN',
            'Approval Code/Auth Code',
            'Amount',
            'Tenure',
            'Scheme Code',
            'Merchant Name',
            'Merchant Name',
            'Manufacturer',
            'Address1',
            'Store City',
            'Store State',
            'Acquirer Name',
            'MID',
            'TID',
            'Settlement Date',
            'Settlement Time',
            'Bank Txns id',
            'Interest Rate',
            'Status',
            'Product Category',
            'Product Sub-Category 1',
            'Product Sub-Category 2',
            'Model Name',
            'EMI Amount',
            'Merchant Subvention Rate',
            'Merchant Subvention Amount',
            'Additional Offer Description',
            'Merchant Type'
        ];

        $formattedData[] = $headers;

        $rrn = $this->getRrnNumber($data['items']);

        $totalTransactions = 0;

        foreach ($data['items'] as $emiPayment)
        {

            $principalAmount = $emiPayment->getAmount();

            $emiPlan = $emiPayment->emiPlan;

            $emiTenure = $emiPlan->getDuration();

            $rate = $emiPlan->getRate() / 100;

            $last4 = $emiPayment->card->getLast4();

            $emiAmount = $this->getEmiAmount($principalAmount, $rate, $emiTenure);

            $mid = substr(hash("sha256",$emiPayment->merchant['id']), 0, 15);

            $verificationFields = [
                'gateway' => self::ICICI_DEBIT_EMI,
                'payment_id' => $emiPayment->getId(),
                'action' => 'loan_booking'

            ];

            $cpsReferencesIds = App::getFacadeRoot()['card.payments']->fetchEmiGatewayReferenceIdsFromPaymentId($verificationFields);

            $formattedData[] = [
                isset($cpsReferencesIds['gateway_transaction_id'])?$cpsReferencesIds['gateway_transaction_id']:'',
                $last4,
                $this->getFormattedDate($emiPayment->getAuthorizeTimestamp()),
                'ICICI',
                $rrn[$emiPayment->getId()]['rrn'] ?? '',
                isset($cpsReferencesIds['gateway_reference_id1'])?$cpsReferencesIds['gateway_reference_id1']:'',
                number_format($emiPayment->getAmount() / 100, 2,'.', ''),
                $emiTenure,
                $emiTenure,
                'Razorpay',
                $emiPayment->merchant->getDbaName() ?: 'Razorpay Payments',
                '',
                '',
                '',
                '',
                '',
                $mid,
                '',
                $this->getFormattedDate($emiPayment->getAuthorizeTimestamp()),
                Carbon::createFromTimestamp($emiPayment->getAuthorizeTimestamp(), Timezone::IST)->format('d/m/Y H:i:s'),
                isset($cpsReferencesIds['gateway_reference_id1'])?$cpsReferencesIds['gateway_reference_id1']:'',
                $rate,
                'Success',
                '',
                '',
                '',
                '',
                number_format($emiAmount / 100, 2,'.', ''),
                '',
                '',
                '',
                '5596'
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

}
