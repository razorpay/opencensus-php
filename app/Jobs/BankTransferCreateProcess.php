<?php

namespace RZP\Jobs;

use App;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;
use RZP\Models\VirtualAccount\Metric;
use RZP\Error\PublicErrorDescription;

class BankTransferCreateProcess extends Job
{
    protected $queueConfigKey = 'bank_transfer_create';

    protected $bankTransferRequestId;

    public function __construct(string $mode = null, $bankTransferRequestId)
    {
        parent::__construct($mode);
        parent::setPassportTokenForJobs();

        $this->bankTransferRequestId = $bankTransferRequestId;
    }

    public function handle()
    {
        parent::handle();

        $isDeleteFromQueue = true;

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_QUEUE_INITIATED,
            [
                'bankTransferRequestId' => $this->bankTransferRequestId,
            ]
        );

        $errorMessage = null;

        try
        {
            $bankTransferRequest = $this->getBankTransferRequestEntity();

            $bankTransferCreatedAt = $bankTransferRequest->getCreatedAt();

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_QUEUE,
                $bankTransferRequest->toArrayTrace()
            );

            (new BankTransfer\Service())->processBankTransfer($bankTransferRequest);

            if ($bankTransferRequest->getErrorMessage() === PublicErrorDescription::BAD_REQUEST_VIRTUAL_ACCOUNT_OPERATION_IN_PROGRESS)
            {
                $isDeleteFromQueue = false;
            }
            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_QUEUE_COMPLETED,
                [
                    'bankTransferRequestId' => $this->bankTransferRequestId,
                    'retryCount'            => $this->attempts(),
                ]
            );
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESSING_FAILED,
                [
                    'message'                  => 'bank transfer failed',
                    'bank_transfer_request_id' => $this->bankTransferRequestId,
                ]
            );

            $errorMessage = $ex->getMessage();
        }
        finally
        {
            (new Metric())->pushQueueTimeMetrics($bankTransferCreatedAt,
                                                 Carbon::now()->getTimestamp(),
                                                 $bankTransferRequest->getGateway(),
                                                 $errorMessage);

            if ($isDeleteFromQueue === true)
            {
                $this->delete();
            }
        }
    }

    private function getBankTransferRequestEntity()
    {
        $app = App::getFacadeRoot();

        $this->repo = $app['repo'];

        try
        {
            return $this->repo->bank_transfer_request->findOrFailPublic($this->bankTransferRequestId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESS_REQUEST_NOT_FOUND,
                [
                    'message'                  => 'Bank Transfer Request not found',
                    'bank_transfer_request_id' => $this->bankTransferRequestId,
                ]
            );

            throw $ex;
        }
    }
}
