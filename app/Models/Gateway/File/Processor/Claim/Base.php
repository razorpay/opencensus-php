<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FileStore;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base\PublicCollection;
use RZP\Models\Gateway\File\Constants;
use RZP\Exception\GatewayFileException;
use RZP\Models\Gateway\File\Processor\Base as BaseProcessor;

class Base extends BaseProcessor
{
    /**
     * Banks for which we need to fetch only the payments which have been successfully
     * reconciled while fetching for generating claim file
     */
    const RECONCILED_PAYMENTS_REQUIRED_TARGETS = [
        Constants::KOTAK,
        Constants::RBL
    ];

    public function fetchEntities(): PublicCollection
    {
        $statuses = [
            Payment\Status::AUTHORIZED,
            Payment\Status::CAPTURED,
            Payment\Status::REFUNDED
        ];

        $begin = $this->gatewayFile->getBegin();
        $end = $this->gatewayFile->getEnd();
        $gateway = static::GATEWAY;
        $tpv = $this->gatewayFile->getTpv();

        if ($this->shouldFetchReconciledPayments() === true)
        {
            $claims = $this->fetchReconciledPayments($begin, $end, $statuses);
        }
        else
        {
            $claims = $this->repo->payment->fetchPaymentsWithStatus($begin, $end, static::GATEWAY, $statuses);
        }

        return $claims;
    }

    protected function fetchReconciledPayments(int $begin, int $end, array $statuses)
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end = Carbon::createFromTimestamp($end)->addDay()->timestamp;
        $tpv = $this->gatewayFile->getTpv();

        if ($tpv === null)
        {
            $claims = $this->repo->payment
                                 ->fetchReconciledPaymentsForGateway($begin,
                                                                    $end,
                                                                    static::GATEWAY,
                                                                    $statuses);
        }
        else
        {
            $claims = $this->repo->payment->fetchReconciledPaymentsForTpv(
                            $begin,
                            $end,
                            static::GATEWAY,
                            $statuses,
                            $tpv);
        }

        return $claims;
    }

    protected function shouldFetchReconciledPayments(): bool
    {
        $target = $this->gatewayFile->getTarget();

        return (in_array($target, self::RECONCILED_PAYMENTS_REQUIRED_TARGETS, true) === true);
    }

    protected function shouldNotReportFailure(string $code): bool
    {
        return ($code === ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
    }

    public function checkIfValidDataAvailable(PublicCollection $claims)
    {
        if ($claims->isEmpty() === true)
        {
            throw new GatewayFileException(
                    ErrorCode::SERVER_ERROR_GATEWAY_FILE_NO_DATA_FOUND);
        }
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
        return;
    }
}
