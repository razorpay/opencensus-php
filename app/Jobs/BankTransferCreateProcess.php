<?php

namespace RZP\Jobs;

use App;

use RZP\Trace\TraceCode;
use RZP\Models\BankTransfer;

class BankTransferCreateProcess extends Job
{
    protected $queueConfigKey = 'bank_transfer_create';

    protected $bankTransferRequestId;

    public function __construct(string $mode = null, $bankTransferRequestId)
    {
        parent::__construct($mode);

        $this->bankTransferRequestId = $bankTransferRequestId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::BANK_TRANSFER_PROCESS_QUEUE_INITIATED,
            [
                'bankTransferRequestId' => $this->bankTransferRequestId,
            ]
        );

        try
        {
            $bankTransferRequest = $this->getBankTransferRequestEntity();

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_QUEUE,
                $bankTransferRequest->toArrayTrace()
            );

            (new BankTransfer\Service())->processBankTransfer($bankTransferRequest);

            $this->trace->info(
                TraceCode::BANK_TRANSFER_PROCESS_QUEUE_COMPLETED,
                [
                    'bankTransferRequestId' => $this->bankTransferRequestId,
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
        }
        finally
        {
            $this->delete();
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
