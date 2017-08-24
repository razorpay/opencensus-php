<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Gateway\Base\Action;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Gateway\File\Status;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\FailureCode;

trait GenerateClaimFile
{
    public function fetchEntities(): PublicCollection
    {
        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        $from = $this->gatewayFile->getFrom();
        $to = $this->gatewayFile->getTo();
        $gateway = $this->gatewayFile->getGateway();
        $tpv = $this->gatewayFile->getTpv();

        if ($tpv === null)
        {
            if ($gateway === Payment\Gateway::NETBANKING_RBL)
            {
                $claims = $this->fetchReconciledPayments($statuses);
            }
            else
            {
                $claims = $this->repo->payment->fetchPaymentsWithStatus($from, $to, $gateway, $statuses);
            }
        }
        else
        {
            $claims = $this->repo->payment->fetchReconciledPaymentsForTpv($from, $to, $gateway, $statuses, $tpv);
        }

        if ($claims->isEmpty() === true)
        {
            throw new GatewayFileException(
                    FailureCode::NO_DATA_FOR_FILE_GENERATION);
        }

        return $claims;
    }

    public function generateData(PublicCollection $claims): array
    {
        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $this->data[] = $col;
        }

        $paymentIds = $claims->pluck('id')->toArray();

        $gatewayEntities = $this->repo
                                ->netbanking
                                ->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $this->data = array_map(function($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $this->data);

        return $this->data;
    }

    public function createFile()
    {
        // Don't process further if file is already generated
        if ($this->isClaimFileGenerated() === true)
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
        return ;
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

    protected function isClaimFileGenerated(): bool
    {
        if ($this->gatewayFile->isFileGenerated() === true)
        {
            $claimsFile = $this->gatewayFile
                               ->files()
                               ->where(FileStore\Entity::TYPE, static::FILE_TYPE)
                               ->first();

            return $claimsFile !== null;
        }

        return false;
    }
}
