<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use Carbon\Carbon;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Gateway\Base\Action;
use RZP\Models\Gateway\File\Status;
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
        Constants::RBL,
        Constants::OBC,
        Constants::VIJAYA,
        Constants::IDFC,
        Constants::CORPORATION,
        Constants::ALLA,
        Constants::CUB,
        Constants::KVB,
        Constants::SCBL,
        Constants::CBI,
        Constants::CANARA,
        Constants::JSB,
        Constants::SVC,
        Constants::PNB,
        Constants::IOB,
        Constants::FSB,
        Constants::IDBI,
        Constants::JKB,
        Constants::FEDERAL,
        Constants::EQUITAS,
        Constants::DCB,
        Constants::UBI,
        Constants::IBK,
        Constants::AUBL,
        Constants::AUBL_CORP,
        Constants::KOTAK_CORP,
        Constants::DLB,
        Constants::TMB,
        Constants::NSDL,
        Constants::INDUSIND,
        Constants::BDBL,
        Constants::SRCB,
        Constants::KARB,
        Constants::UCO,
        Constants::HDFC_CORP,
        Constants::UJVN,
        Constants::RBL_CORP,
        Constants::DBS,
        Constants::YESB,
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

        if ($this->shouldFetchReconciledPayments() === true)
        {
            $claims = $this->fetchReconciledPaymentsToClaim($begin, $end, $statuses);
        }
        else
        {
            $claims = $this->fetchPaymentsToClaim($begin, $end, $statuses);
        }

        return $claims;
    }

    protected function fetchPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $claims = $this->repo->payment->fetchPaymentsWithStatus(
            $begin,
            $end,
            static::GATEWAY,
            $statuses
        );

        return $claims;
    }

    protected function fetchReconciledPaymentsToClaim(int $begin, int $end, array $statuses): PublicCollection
    {
        $begin = Carbon::createFromTimestamp($begin)->addDay()->timestamp;
        $end   = Carbon::createFromTimestamp($end)->addDay()->timestamp;

        $claims = $this->repo->payment
                             ->fetchReconciledPaymentsForGateway($begin,
                                                                $end,
                                                                static::GATEWAY,
                                                                $statuses);
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

    public function generateData(PublicCollection $claims)
    {
        $data = [];

        foreach ($claims as $claim)
        {
            $col['payment'] = $claim;
            $col['terminal'] = $claim->terminal->toArray();

            $data[] = $col;
        }

        $paymentIds = $claims->pluck('id')->toArray();

        $gatewayEntities = $this->fetchGatewayEntities($paymentIds);

        $gatewayEntities = $gatewayEntities->keyBy('payment_id');

        $data = array_map(function($row) use ($gatewayEntities)
        {
            $paymentId = $row['payment']['id'];

            if (isset($gatewayEntities[$paymentId]) === true)
            {
                $row['gateway'] = $gatewayEntities[$paymentId]->toArray();
            }

            return $row;
        }, $data);

        return $data;
    }

    public function createFile($data)
    {
        // Don't process further if file is already generated
        if ($this->isFileGenerated() === true)
        {
            return;
        }

        try
        {
            $fileData = $this->formatDataForFile($data);

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
            throw new GatewayFileException(
                ErrorCode::SERVER_ERROR_GATEWAY_FILE_ERROR_GENERATING_FILE,
                [
                    'id'        => $this->gatewayFile->getId(),
                ],
                $e);
        }
    }

    public function sendFile($data)
    {
        return;
    }

    protected function fetchGatewayEntities($paymentIds)
    {
        return $this->repo->netbanking->fetchByPaymentIdsAndAction($paymentIds, Action::AUTHORIZE);
    }
}
