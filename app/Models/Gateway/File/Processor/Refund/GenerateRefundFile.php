<?php

namespace RZP\Models\Gateway\File\Processor\Refund;

use Mail;
use Carbon\Carbon;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Constants\Timezone;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\FailureCode;
use RZP\Mail\Gateway\RefundFile\Base as RefundFileMail;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

trait GenerateRefundFile
{
    public function fetchEntities(): PublicCollection
    {
        $from = $this->gatewayFile->getFrom();
        $to = $this->gatewayFile->getTo();
        $gateway = $this->gatewayFile->getGateway();
        $bank = $this->gatewayFile->getBank();

        $refunds = $this->repo->refund->fetchRefundsForGatewayBetweenTimestamps(
                        $this->type,
                        $bank,
                        $from,
                        $to,
                        $gateway
                    );

        return $refunds;
    }

    public function checkIfValidDataAvailable(PublicCollection $entites)
    {
        if ($refunds->isEmpty() === true)
        {
            throw new GatewayFileException(
                    FailureCode::NO_DATA_FOR_FILE_GENERATION);
        }
    }

    /**
     * Fetches all necessary refund related data required for generating the file
     * If no refunds are found, we throw an exception with the appropriate error message
     */
    public function generateData(PublicCollection $refunds): array
    {
        $gateway = $this->gatewayFile->getGateway();

        foreach ($refunds as $refund)
        {
            $payment = $refund->payment;
            $terminal = $payment->terminal;

            $col['refund'] = $refund->toArray();
            $col['payment'] = $payment->toArray();
            $col['terminal'] = $terminal->toArray();

            $this->data[] = $col;
        }

        $paymentIds = $refunds->pluck('payment_id')->toArray();

        $gatewayEntities = $this->repo->$gateway->fetchByPaymentIdsAndAction(
                            $paymentIds, Action::AUTHORIZE);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $this->data = array_map(function($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]))
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $this->data);

        return $this->data;
    }

    /**
     * We create the required file and associate it with the gateway_file entity
     * Any exception during file generation etc is caught and handled accordingly
     */
    public function createFile()
    {
        // Don't process further if file is already generated
        if ($this->isRefundFileGenerated() === true)
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
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_FILE_GEN_ERROR,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                FailureCode::ERROR_CREATING_FILE);
        }
    }

    public function sendMail()
    {
        try
        {
            $gateway = $this->gatewayFile->getGateway();

            $recipients = $this->gatewayFile->getRecipients();

            $mailData = $this->formatDataForMail();

            $refundFileMail = new RefundFileMail($mailData, $gateway, $recipients);

            Mail::send($refundFileMail);

            $this->gatewayFile->setMailSentAt(time());

            $this->gatewayFile->setStatus(Status::MAIL_SENT);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            Trace::INFO,
                            TraceCode::GATEWAY_FILE_MAIL_SEND_ERROR,
                            [
                                'id' => $this->gatewayFile->getId()
                            ]);

            throw new GatewayFileException(
                FailureCode::ERROR_SENDING_MAIL);
        }
    }

    protected function getFileToWriteNameWithoutExt()
    {
        $time = Carbon::now(Timezone::IST)->format('d-m-Y');

        return static::FILE_NAME . '_' . $this->mode . '_' . $time;
    }

    /**
     * Checks if the given gateway file can be retried or not. Currently
     * we consider that if the refund gateway_file entity is in acknowledged state
     * then it cannot be retried further.
     * Also if a gateway_file entity was marked as failed earlier as there was no data
     * to process during that interval then also it cannot be retried again.
     *
     * @return bool Whether gateway_file entity can be processed again or not
     */
    protected function canRetry(): bool
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

    protected function isRefundFileGenerated(): bool
    {
        if ($this->gatewayFile->isFileGenerated() === true)
        {
            $refundFile = $this->gatewayFile
                               ->files()
                               ->where(FileStore\Type, static::FILE_TYPE)
                               ->first();

            return $refundFile !== null;
        }

        return false;
    }
}
