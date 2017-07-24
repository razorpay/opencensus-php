<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;
use RZP\Models\FileStore;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\FailureCode;
use RZP\Mail\Gateway\RefundFile\V2 as RefundFileMailV2;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

trait GenerateRefundFile
{
    public function generateData()
    {
        $refunds = $this->fetchRefunds();

        if ($refunds->isEmpty() === true)
        {
            throw new GatewayFileException(
                    FailureCode::NO_DATA_FOR_FILE_GENERATION);
        }

        try
        {
            $this->data = $this->fetchAdditionalDataForFileGeneration($refunds);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                FailureCode::ERROR_GENERATING_FILE_DATA);
        }
    }

    public function createFile()
    {
        if ($this->gatewayFile->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $data = $this->formatDataForFile();

            $fileName = $this->getFileToWriteNameWithoutExt();

            $creator = new FileStore\Creator;

            $creator->extension(static::EXTENSION)
                    ->content($data)
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
            throw new GatewayFileException(
                FailureCode::ERROR_CREATING_FILE);
        }
    }

    public function sendMail()
    {
        if ($this->gatewayFile->isMailSent() === true)
        {
            return;
        }

        try
        {
            $gateway = $this->gatewayFile->getGateway();

            $recipients = $this->gatewayFile->getRecipients();

            $data = $this->formatDataForMail();

            $refundFileMail = new RefundFileMailV2($data, $gateway, $recipients);

            Mail::send($refundFileMail);

            $this->gatewayFile->setMailSentAt(time());

            $this->gatewayFile->setStatus(Status::MAIL_SENT);
        }
        catch (\Throwable $e)
        {
            throw new GatewayFileException(
                FailureCode::ERROR_SENDING_MAIL);
        }
    }

    public function fetchRefunds()
    {
        $from = $this->gatewayFile->getFrom();
        $to = $this->gatewayFile->getTo();
        $gateway = $this->gatewayFile->getGateway();

        $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                        $this->type,
                        $this->gatewayCode,
                        $from,
                        $to,
                        $gateway
                    );

        return $refunds;
    }

    protected function fetchAdditionalDataForFileGeneration($refunds)
    {
        $gateway = $this->gatewayFile->getGateway();

        $data = [];

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $payment->toArray();
            $col['terminal'] = $terminal->toArray();

            $data[] = $col;
        }

        $paymentIds = $refunds->pluck('payment_id')->toArray();

        $gatewayEntities = $this->repo->$gateway->fetchByPaymentIdsAndAction(
                            $paymentIds, Action::AUTHORIZE);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $data = array_map(function($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]))
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }

    protected function canProcess(): bool
    {
        if ($this->gatewayFile->isAcknowledged() === true)
        {
            return false;
        }

        if ($this->gatewayFile->isFailed() === true)
        {
            $failureCode = $this->gatewayFile->getFailureCode();

            return ($failureCode !== FailureCode::NO_DATA_FOR_FILE_GENERATION);
        }

        return true;
    }
}
