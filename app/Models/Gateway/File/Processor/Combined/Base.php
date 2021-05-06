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
use RZP\Models\FileStore\Storage\Base\Bucket;
use RZP\Mail\Gateway\DailyFile as DailyFileMail;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    /**
     * Error codes for which we don't want to report the failure and mark the processing
     * as failed, as no futher actions can be taken here
     */
    const SUPPRESSED_ERROR_CODES = [
        ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND,
    ];

    public function fetchEntities(): PublicCollection
    {
        try
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
        catch (\Throwable $e)
        {
            $this->trace->traceException($e);

            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id' => $this->gatewayFile->getId(),
                ]);
        }
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
            $this->trace->info(TraceCode::GATEWAY_FILE_CLAIMS_LESSER_THAN_REFUNDS,
                                [
                                    'refund_amount' => $refunds->sum('amount'),
                                    'claim_amount'  => $claims->sum('amount'),
                                    'gateway'       => $this->gatewayFile->getTarget(),
                                ]);
        }
    }

    public function generateData(PublicCollection $entities)
    {
        $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

        $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

        if ($entities->get('refunds')->isNotEmpty() === true)
        {
            $data['refunds'] = $refundFileProcessor->generateData($entities->get('refunds'));
        }

        if ($entities->get('claims')->isNotEmpty() === true)
        {
            $data['claims'] = $claimFileProcessor->generateData($entities->get('claims'));
        }

        return $data;
    }

    public function createFile($data)
    {
        if (isset($data['refunds']) === true)
        {
            $refundFileProcessor = $this->getFileProcessor(Type::REFUND);

            $refundFileProcessor->createFile($data['refunds']);
        }

        if (isset($data['claims']) === true)
        {
            $claimFileProcessor = $this->getFileProcessor(Type::CLAIM);

            $claimFileProcessor->createFile($data['claims']);
        }
    }

    public function sendFile($data)
    {
        try
        {
            $mailData = $this->formatDataForMail($data);

            $dailyFileMail = new DailyFileMail($mailData);

            Mail::send($dailyFileMail);

            $this->gatewayFile->setFileSentAt(time());

            $this->gatewayFile->setStatus(Status::FILE_SENT);

            $this->reconcileNetbankingRefunds($data['refunds'] ?? []);
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
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_SENDING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
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
                self::SUPPRESSED_ERROR_CODES,
                true) === true);
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
            'name' => basename($file->getLocation()),
        ];

        return $fileData;
    }

    public function getBucketConfig()
    {
        $config = $this->app['config']->get('filestore.aws');

        $bucketType = Bucket::getBucketConfigName(static::FILE_TYPE, $this->env);

        $bucketConfig = $config[$bucketType];

        return $bucketConfig;
    }
}
