<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Razorpay\Trace\Logger as Trace;
use Carbon\Carbon;
use RZP\Exception;
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


class IciciEmi extends Base
{
    const FILE_NAME = 'Razorpay_EMICancellation';
    const EXTENSION = FileStore\Format::XLSX;
    const FILE_TYPE = FileStore\Type::ICICI_EMI_REFUND_FILE;
    const PAYMENT_TYPE_ATTRIBUTE = Payment\Entity::BANK;
    const BANK_CODE = IFSC::ICIC;

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
        return str_random(15);
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

        $cardNumber = (new Card\CardVault)->getCardNumber($cardToken);

        return $cardNumber;
    }

    protected function formattedDateFromTimestamp($timestamp)
    {
        return Carbon::createFromTimestamp($timestamp, Timezone::IST)->format('d/m/Y');
    }

    protected function pushEmiFileToBeam(string $jobName)
    {
        try {
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
            $timelines = [];

            $mailInfo = [
                'fileInfo' => $fileInfo,
                'channel' => 'settlements',
                'filetype' => 'emi',
                'subject' => 'File Send failure',
                'recipient' => Constants::MAIL_ADDRESSES[Constants::DEVELOPERS]
            ];

            $this->app['beam']->beamPush($data, $timelines, $mailInfo);
        } catch (\Exception $e) {
            $this->trace->error(TraceCode::BEAM_PUSH_FAILED,
                [
                    'job_name' => $jobName,
                    'file_name' => $fullFileName,
                ]);
        }
    }

    public function generateData(PublicCollection $entities)
    {
        $data = $entities->all();

        $this->emiFilePassword = $this->generatePassword();

        return $data;
    }

    protected function sendConfirmationMail()
    {
        $fullFileName = $this->file->getName() . '.' . $this->file->getExtension();

        $body = 'Emi File Uploaded <br />';
        $body = $body . 'File Name : ' . $fullFileName . '<br />';
        $body = $body . 'Password : ' . $this->emiFilePassword . '<br />';

        $mailData = [
            "body" => $body
        ];

        $recipients = $this->gatewayFile->getRecipients();

        $refundFileMail = new RefundFileMail($mailData, "icici_emi", $recipients);

        Mail::queue($refundFileMail);
    }

    protected function getTotalAmount(array $input)
    {
        $sum = 0;
        for ($i = 0; $i < count($input); $i++) {
            $sum += $input[$i]["payment"]["amount"];
        }
        return $sum;
    }

    public function sendFile($fileData, $mailData = null)
    {
        // Push this file to Beam
        $this->pushEmiFileToBeam(BeamConstants::ICIC_EMI_FILE_JOB_NAME);

        $this->gatewayFile->setStatus(Status::FILE_SENT);

        try
        {
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
            $formattedData[] = [
                'EMI ID' => $row->payment_id,
                'Full Card No.' => $this->getCardNumber($row->payment->card),
                'Original Transaction Amount' => $row->payment->amount,
                'Txn date' => $this->formattedDateFromTimestamp($row->payment->created_at),
                'Auth ID' => $this->getAuthCode($row->payment),
                'Merchant Name' => $row->merchant->name,
                'MID' => $row->merchant->id,
                'Refund Amount' => $row->amount,
                'Refund Date' => $this->formattedDateFromTimestamp($row->created_at),
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
                ->store(FileStore\Store::S3)
                ->type(static::FILE_TYPE)
                ->entity($this->gatewayFile);

            $creator->password($this->emiFilePassword)
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

    protected function formatDataForMail(array $data)
    {
        $file = $this->gatewayFile
            ->files()
            ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
            ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name' => basename($file->getLocation()),
            'signed_url' => $signedUrl
        ];

        return $mailData;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $date = Carbon::now(Timezone::IST)->format('dmy');

        $fileName = self::FILE_NAME . '_' . $date;


        // for sftp we put the file in a H2H path
        $filePath = 'icici/outgoing/';

        return $filePath . $fileName;
    }
}
