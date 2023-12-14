<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use App;
use Mail;
use Razorpay\Trace\Logger as Trace;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\GatewayFileException;
use RZP\Gateway\Base\Action;
use RZP\Models\Payment\Refund\Constants as RefundConstants;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Payment;
use RZP\Models\Bank\IFSC;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Models\Card as Card;
use RZP\Mail\Base\Constants;
use RZP\Services\Beam\Service;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Services\Beam\Constants as BeamConstants;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;


class IciciDebitEmi extends Base
{
    const FILE_NAME = 'icici_rzp_dcemi_refund';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::ICICI_DEBIT_EMI_REFUND_FILE;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE = IFSC::ICIC;
    const ICICI_DEBIT_EMI       = 'icici_debit_emi';

    protected function getScroogeQuery(int $from, int $to, $refundIds = []): array
    {
        $input = [
            RefundConstants::SCROOGE_QUERY => [
                RefundConstants::SCROOGE_REFUNDS => [
                    RefundConstants::SCROOGE_GATEWAY       => 'hitachi',
                    RefundConstants::SCROOGE_BANK       => static::BANK_CODE,
                    RefundConstants::SCROOGE_METHOD     => Payment\Method::EMI,
                    RefundConstants::SCROOGE_CREATED_AT => [
                        RefundConstants::SCROOGE_GTE => $from,
                        RefundConstants::SCROOGE_LTE => $to,
                    ],
                    RefundConstants::SCROOGE_BASE_AMOUNT => [
                        RefundConstants::SCROOGE_GT => 0,
                    ],
                ],
            ],
            RefundConstants::SCROOGE_COUNT => $this->fetchFromScroogeCount,
        ];

        if (empty($refundIds) === false)
        {
            $input[RefundConstants::SCROOGE_QUERY][RefundConstants::SCROOGE_REFUNDS][RefundConstants::SCROOGE_ID] = $refundIds;
        }

        return $input;
    }

    protected function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('dmy');
    }

    protected function pushEmiFileToBeam(string $jobName)
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $fileInfo = [$fullFileName];

        $bucketConfig = $this->getBucketConfig(self::FILE_TYPE);

        $data = [
            Service::BEAM_PUSH_FILES => $fileInfo,
            Service::BEAM_PUSH_JOBNAME => $jobName,
            Service::BEAM_PUSH_BUCKET_NAME => $bucketConfig['name'],
            Service::BEAM_PUSH_BUCKET_REGION => $bucketConfig['region'],
        ];

        // Retry in 15, 30 and 45 minutes
        $timelines = [900, 1800, 2700];

        $mailInfo = [
            'fileInfo' => $fileInfo,
            'channel' => 'settlements',
            'filetype' => 'refund',
            'subject' => 'File Send failure',
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
                    'job_name'      => $jobName,
                    'file_name'     => $fullFileName,
                    'Bank'          => 'Icici Debit Emi',
                ]
            );
        }

    }

    public function sendFile($fileData, $mailData = null)
    {
        try
        {
            // Push this file to Beam
            $this->pushEmiFileToBeam(BeamConstants::ICICI_DEBIT_EMI_REFUND_FILE_JOB_NAME);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }

    }

    protected function addGatewayEntitiesToDataWithPaymentIds(array $data, array $paymentIds)
    {
        return $data;
    }

    protected function formatDataForFile(array $data)
    {

        $formattedData = [];

        $headers = [
            'TransactionDate',
            'Repayment Initiation Date',
            'CardNumber',
            'Amount',
            'Tenure',
            'Approval Code',
            'Merchant Name',
            'Scheme Code',
            'Merchant Name',
            'Type of Refund',
            'Refund Amount',
            'Account Number',
            'MID',
            'Settlement Date',
            'Bank Txns id',
            'Merchant Track ID',
            'Status',
            'Merchant Type'
        ];

        $formattedData[] = $headers;

        foreach ($data as $index => $row) {

            $payment = $row['payment'];
            $refund = $row['refund'];

            $emiTenure = $payment['emi_plan']['duration'];

            $last4 = $row['card']['last4'];


            $mid = substr(hash("sha256",$row['merchant']['id']), 0, 15); // to be checked and tested

            $verificationFields = [
                'gateway' => self::ICICI_DEBIT_EMI,
                'payment_id' => $payment['id'],
                'action' => 'loan_booking'
            ];

            $cpsReferencesIds = App::getFacadeRoot()['card.payments']->fetchEmiGatewayReferenceIdsFromPaymentId($verificationFields);

            $formattedData[] = [
                $this->formattedDateFromTimestamp($payment['created_at']),
                $this->formattedDateFromTimestamp($refund['created_at']),
                $last4,
                $this->getFormattedAmount($payment['amount']/100),
                $emiTenure,
                isset($cpsReferencesIds['gateway_reference_id1'])?$cpsReferencesIds['gateway_reference_id1']:'',
                'Razorpay',
                $emiTenure,
                $row['merchant']['name'],
                null,
                $this->getFormattedAmount($refund['amount']/100),
                '',
                $mid,
                null,
                isset($cpsReferencesIds['gateway_reference_id1'])?$cpsReferencesIds['gateway_reference_id1']:'',
                isset($cpsReferencesIds['gateway_transaction_id'])?$cpsReferencesIds['gateway_transaction_id']:'',
                'Refund',
                '5596',
            ];
        }

        return $formattedData;
    }


    public function createFile($data)
    {
        $defaultExcelEnclosure = $this->config->get('excel.exports.csv.enclosure');

        $this->config->set('excel.exports.csv.enclosure', '');

        if ($this->isFileGenerated() === true) {
            return;
        }

        try {
            $fileData = $this->formatDataForFile($data);

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                ->content($fileData)
                ->name($fileName)
                ->headers(false)
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile);

            $creator->save();

            $this->file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($this->file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);
        } catch (\Throwable $e) {
            throw new Exception\GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                    'message' => $e->getMessage(),
                ],
                $e);
        }

        $this->config->set('excel.exports.csv.enclosure', $defaultExcelEnclosure);
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount, 2,'.', '');
    }

    protected function shouldRefundsBeFetchedFromScrooge(): bool
    {
        return true;
    }
}
