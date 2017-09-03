<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Type;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\FailureCode;
use RZP\Models\Gateway\File\ProcessorFactory;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;

trait GenerateCombinedFile
{
    public function fetchEntities(): PublicCollection
    {
        $entities = new PublicCollection;

        $refundFileProcessor = $this->getFileProcesor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcesor(Type::CLAIM);

        $refunds = $refundFileProcessor->fetchEntities();

        $claims = $claimFileProcessor->fetchEntities();

        $entities->put('refunds', $refunds);

        $entities->put('claims', $claims);

        return $entities;
    }

    public function checkIfValidDataAvailable(PublicCollection $entities)
    {
        $refunds = $entities->get('refunds');

        $claims = $entities->get('claims');

        if (($refunds->isEmpty() === true) and ($claims->isEmpty() === true))
        {
            throw new GatewayFileException(
                FailureCode::NO_DATA_FOR_FILE_GENERATION);
        }

        if ($this->isTotalAmountValid($refunds, $claims) === false)
        {
            throw new GatewayFileException(
                FailureCode::CLAIM_AMOUNT_LESS_THAN_REFUND_AMOUNT);
        }
    }

    public function generateData(PublicCollection $entities): array
    {
        $refundFileProcessor = $this->getFileProcesor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcesor(Type::CLAIM);

        if ($entities->get('refunds')->isNotEmpty() === true)
        {
            $this->data['refunds'] = $refundFileProcessor->generateData($entities->get('refunds'));
        }

        if ($entities->get('claims')->isNotEmpty() === true)
        {
            $this->data['claims'] = $claimFileProcessor->generateData($entities->get('claims'));
        }

        return $this->data;
    }

    public function createFile()
    {
        if (isset($this->data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcesor(Type::REFUND);

            $refundFileProcessor->createFile();
        }

        if (isset($this->data['claims']) === true)
        {
            $claimFileProcessor = $this->getFileProcesor(Type::CLAIM);

            $claimFileProcessor->createFile();
        }
    }

    public function sendMail()
    {
        try
        {
            $mailData = $this->formatDataForMail();

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

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

    protected function isTotalAmountValid(PublicCollection $refunds, PublicCollection $claims): bool
    {
        $totalRefundAmount = $refunds->sum('amount');

        $totalClaimAmount = $claims->sum('amount');

        return ($totalClaimAmount >= $totalRefundAmount);
    }

    protected function getFileProcesor(string $type)
    {
        $source = $this->gatewayFile->getSource();

        $processor = $this->app['gateway']
                          ->getFileProcessor($type, $source)
                          ->setGatewayFile($this->gatewayFile);

        return $processor;
    }

    /**
     * For combined files, the gateway_file entity is retriable if it is not acknowledged
     * or if the previous failure was NOT due to below reasons
     * - No claims / refunds available for file generation
     * - Total claims is less than total refunds
     *
     * @return bool Whether the file can be processed again
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

            return (in_array($failureCode,
                    [FailureCode::NO_DATA_FOR_FILE_GENERATION,
                        FailureCode::CLAIM_AMOUNT_LESS_THAN_REFUND_AMOUNT], true) === false);
        }

        return true;
    }

    protected function getFileData(string $type)
    {
        $file = $this->gatewayFile
                     ->files()
                     ->where(FileStore\Entity::TYPE, $type)
                     ->first();

        $signedUrl = (new FileStore\Accessor)->getSignedUrlOfFile($file);

        $fileData = [
            'url'  => $signedUrl,
            'name' => $file->getLocation(),
        ];

        return $fileData;
    }
}
