<?php

namespace RZP\Models\Gateway\File\Processor\Refund;
use App;
use Mail;
use Illuminate\Support\Facades\Config;
use Razorpay\Trace\Logger as Trace;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Exception\GatewayErrorException;
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


class AublEmi extends Base
{
    const FILE_NAME = 'Razorpay_EMI_Refund_';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::AUBL_EMI_REFUND_FILE;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE = IFSC::AUBL;

    public function fetchEntities(): PublicCollection
    {
        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();

        return $this->repo
            ->refund
            ->fetchEmiRefundsWithCardTerminalsBetween(
                $begin,
                $end,
                static::BANK_CODE);
    }

    protected function generatePassword()
    {
        $publicKey = Config::get('applications.emi.aubl_cc_emi_file_password');
        return $publicKey;
    }
    private function getAuthCode($payment)
    {
        $authCode = $payment['reference2'];

        if (empty($authCode) === true) {
            throw new Exception\LogicException(
                'Authorization Code cannot be empty.', null,
                [
                    'payment_id' => $payment->getPublicId(),
                    'auth_code' => $authCode
                ]);
        }

        return $authCode;
    }

    private function getCardNumber($card)
    {
        if ($card->globalCard !== null) {
            $card = $card->globalCard;
        }

        $cardToken = $card->getVaultToken();

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken,$card->toArray());

        return $cardNumber;
    }

    protected function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('M d,Y');
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

        // In seconds
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
                    'Bank'          => 'AUBL CC EMI',
                ]
            );
        }
    }

    protected function sendConfirmationMail()
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . $fullFileName . '<br />';
        $body = $body . 'Password : ' . $this->generatePassword() . '<br />';

        $mailData = [
            "body" => $body
        ];

        $recipients = $this->gatewayFile->getRecipients();

        $refundFileMail = new RefundFileMail($mailData, "aubl_emi", $recipients);

        Mail::queue($refundFileMail);
    }

    public function sendFile($fileData, $mailData = null)
    {

        try
        {
            // Push this file to Beam
            $this->pushEmiFileToBeam(BeamConstants::AUBL_EMI_FILE_JOB_NAME);

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->gatewayFile->setFileSentAt(time());

            $this->sendConfirmationMail();
        }
        catch (\Throwable $e)
        {
            $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

            $this->trace->traceException($e,
                Trace::ERROR,
                TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'file_name' => $fullFileName,
                ]);
        }
    }

    protected function formatDataForFile(array $data)
    {
        $formattedData = [];

        foreach ($data as $index => $row) {

            $card = $row->payment->card;

            if (isset($row->payment->card->trivia) && isset($row->payment->token))
            {
                $card = $row->payment->token->card;
            }

            $formattedData[] = [
                'Masked Card No' => $card->getLast4(),
                'EMI ID' => $row->payment_id,
                'Txn Amount' => $this->getFormattedAmount($row->payment->amount/100),
                'Txn Date' => $this->formattedDateFromTimestamp($row->payment->created_at),
                'Auth_Code' => $this->getAuthCode($row->payment),
                'Merchant Name' => $row->merchant->name,
                'Refund Type' => $this->getRefundType($row),
                'Refund Amount' => $this->getFormattedAmount($row->amount/100),
                'Refund Date' => $this->formattedDateFromTimestamp($row->created_at),
                'Refund Auth Code' => $row->id,
            ];
        }

        return $formattedData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount, 2,'.', '');
    }

    protected function getRefundType($row)
    {
        $paymentAmount = $row->payment->amount;

        $refundAmount = $row->amount;

        if ($paymentAmount === $refundAmount)
        {
            return "Full";
        }
        else
        {
            return "Partial";
        }
    }

    public function generateData(PublicCollection $entities)
    {
        $data = $entities->all();

        $this->emiFilePassword = $this->generatePassword();

        return $data;
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
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile);

            $creator->password($this->generatePassword())
                ->compress();

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

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmYHi');

        // the serial no is hardcoded as the file is generated only once
        return self::FILE_NAME . $date;
    }
}
