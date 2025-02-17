<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Models\UpiTransfer;
use RZP\Models\BankTransfer;
use RZP\Services\RazorXClient;
use RZP\Models\BankTransferRequest\Entity;
use RZP\Models\BankTransfer\Constants as BankTransferConstants;

class ProcessCollectxTransfer extends Job
{
    protected $queueConfigKey = 'process_collectx_transfer';
    protected array $bankCallbackInput;
    protected string $bankProvider;
    protected string $requestEntityId;
    protected $repo;
    protected $app;

    public function __construct(string $mode = null, array $input, string $provider, $requestEntityId)
    {
        parent::__construct($mode);

        parent::setPassportTokenForJobs();

        $this->bankCallbackInput = $input;

        $this->bankProvider = $provider;

        $this->requestEntityId = $requestEntityId;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::COLLECTX_PROCESS_TRANSFER_JOB_START, [
                "input"                         => $this->bankCallbackInput,
                "provider"                      => $this->bankProvider,
                "transfer_request_entity_id"    => $this->requestEntityId
            ]);

            $success = true;

            $input = $this->bankCallbackInput;

            $provider = $this->bankProvider;

            $transferMode = $input[BankTransfer\Entity::MODE];

            if (in_array($transferMode, BankTransferConstants::COLLECTX_UPI_TRANSFER_MODES))
            {
                [$terminal, $gatewayResponse] = (new UpiTransfer\Service())->computeGatewayResponseAndTerminalForCollectX($input, $provider);

                (new UpiTransfer\Core())->processPayment($gatewayResponse, $terminal, $this->requestEntityId, isCollectXPayment: true);
            }
            else if (in_array($transferMode, BankTransferConstants::COLLECTX_BANK_TRANSFER_MODES))
            {
                $bankTransferRequest = $this->getBankTransferRequestEntityById($this->requestEntityId);

                $bankTransferRequest->markAsCollectXBankTransfer();

                (new BankTransfer\Service())->processBankTransfer($bankTransferRequest);
            }
        }
        catch (\Throwable $ex)
        {
            $success = false;

            $this->trace->traceException(
                $ex,
                null,
                TraceCode::COLLECTX_PROCESS_TRANSFER_JOB_FAILED,
                [
                    "message" => $ex->getMessage(),
                    "trace" => $ex->getTraceAsString()
                ]
            );
        }
        finally
        {
            $this->trace->info(Tracecode::COLLECTX_PROCESS_TRANSFER_JOB_RESPONSE, [
                "input"                         => $this->bankCallbackInput,
                "transfer_request_entity_id"    => $this->requestEntityId,
                "valid"                         => $success
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    private function getBankTransferRequestEntityById($bankTransferRequestId)
    {
        try
        {
            $app = App::getFacadeRoot();

            $this->repo = $app['repo'];

            return $this->repo->bank_transfer_request->findOrFail(Entity::verifyIdAndStripSign($bankTransferRequestId));
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESS_REQUEST_NOT_FOUND,
                [
                    'message'                  => 'Bank Transfer Request not found',
                    'bank_transfer_request_id' => $bankTransferRequestId,
                ]
            );

            throw $ex;
        }
    }

    /**
     * @throws \Exception
     */
    private function getUpiTransferRequestEntityById($upiTransferRequestId)
    {
        try
        {
            $app = App::getFacadeRoot();

            $this->repo = $app['repo'];

            return $this->repo->upi_transfer_request->findOrFail($upiTransferRequestId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_TRANSFER_PROCESS_REQUEST_NOT_FOUND,
                [
                    'message'                  => 'UPI Transfer Request not found',
                    'upi_transfer_request_id' => $upiTransferRequestId,
                ]
            );

            throw $ex;
        }
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        parent::beforeJobKillCleanUp($variant);

        $context = [
        ];

        $this->handleWorkerTimeoutGracefully($context);
    }
}
