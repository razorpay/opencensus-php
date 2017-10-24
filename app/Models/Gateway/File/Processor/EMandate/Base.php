<?php

namespace RZP\Models\Gateway\File\Processor\EMandate;

use Mail;
use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Constants\Timezone;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayFileException;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Mail\Gateway\EMandate\Base as eMail;

abstract class Base extends BaseProcessor
{
    public function checkIfValidDataAvailable(PublicCollection $payments)
    {
        if ($payments->count() === 0)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
    }

    public function createFile()
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile();

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($fileData)
                    ->name($fileName)
                    ->store(FileStore\Store::S3)
                    ->type(static::FILE_TYPE)
                    ->entity($this->gatewayFile)
                    ->save();

            $file = $creator->getFileInstance();

            $this->gatewayFile->setFileGeneratedAt($file->getCreatedAt());

            $this->gatewayFile->setStatus(Status::FILE_GENERATED);

        }
        catch (\Throwable $e)
        {
            throw $e;
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_ERROR_GENERATING_FILE,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE);
        }
    }

    public function sendFile()
    {
        try
        {
            $recipients = $this->gatewayFile->getRecipients();

            $mailData = $this->formatDataForMail();

            $type = static::GATEWAY . '_' . static::STEP;
            $mailable = new eMail($mailData, $type, $recipients);

            Mail::queue($mailable);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
            throw $e;
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_ERROR_SENDING_FILE,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE);
        }
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('dmYHis');

        if ($this->isTestMode() === true)
        {
            return static::FILE_NAME . '_' . $time . '_' . $this->mode;
        }

        return static::FILE_NAME . '_' . $time;
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    protected function formatDataForMail()
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $mailData = [
            'file_name'     => $file->getLocation(),
            'signed_url'    => $signedUrl,
        ];

        return $mailData;
    }

    protected function getFormattedAmount($amount)
    {
        return number_format($amount / 100, 2, '.', '');
    }
}