<?php

namespace RZP\Models\Gateway\File\Processor\Combined;

use Mail;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Models\Gateway\File\Type;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\ProcessorFactory;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    public function fetchEntities(): PublicCollection
    {
        $entities = new PublicCollection;

        $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

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
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }

        if ($this->isTotalAmountValid($refunds, $claims) === false)
        {
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS);
        }
    }

    public function generateData(PublicCollection $entities): array
    {
        $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

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
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            $refundFileProcessor->createFile();
        }

        if (isset($this->data['claims']) === true)
        {
            $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

            $claimFileProcessor->createFile();
        }
    }

    public function sendFile()
    {
        try
        {
            $mailData = $this->formatDataForMail();

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);
        }
        catch (\Throwable $e)
        {
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

    protected function isTotalAmountValid(PublicCollection $refunds, PublicCollection $claims): bool
    {
        $totalRefundAmount = $refunds->sum('amount');

        $totalClaimAmount = $claims->sum('amount');

        return ($totalClaimAmount >= $totalRefundAmount);
    }

    protected function getFileProcessor(string $type)
    {
        $target = $this->gatewayFile->getTarget();

        $processor = $this->app['gateway_file']
                          ->getProcessor($type, $target)
                          ->setGatewayFile($this->gatewayFile);

        return $processor;
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return (in_array($code,
                [
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND,
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS
                ],
                true) === true);
    }

    protected function getComment(string $code): string
    {
        if ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND)
        {
            return 'Valid data not available for file processing';
        }
        else if ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS)
        {
            return 'File not generated as claims amount is lesser than refunds amount.';
        }
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
            $failureCode = $this->gatewayFile->getErrorCode();

            return (in_array($failureCode,
                    [ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND,
                        ErrorCode::SERVER_ERROR_GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS], true) === false);
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
