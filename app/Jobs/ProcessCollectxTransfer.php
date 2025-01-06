<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Services\RazorXClient;
use RZP\Models\BankTransfer;
use RZP\Models\UpiTransfer;

class ProcessCollectxTransfer extends Job
{
    protected $queueConfigKey = 'process_collectx_transfer';

    protected array $bankCallbackInput;

    protected string $bankProvider;

    public function __construct(string $mode = null, array $input, string $provider)
    {
        parent::__construct($mode);
        parent::setPassportTokenForJobs();

        $this->bankCallbackInput = $input;

        $this->bankProvider = $provider;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::COLLECTX_PROCESS_TRANSFER_JOB_START, [
                "input"=> $this->bankCallbackInput,
                "provider"=> $this->bankProvider
            ]);

            $response = [];

            $input = $this->bankCallbackInput;

            $provider = $this->bankProvider;

            $transferMode = $input[BankTransfer\Entity::MODE];

            $bankTransferService = (new BankTransfer\Service());
            $upiTransferService = (new UpiTransfer\Service());


            switch ($transferMode){
                case $bankTransferService::TRANSFER_TYPE_UPI:
                    // only Yesbank as UPI Providers is allowed
                    $bankTransferService->validateProviderForCollectxUPI($provider, $input);

                    $response = $upiTransferService->processUpiTransferPayment($input, $provider, isCollectXPayment: true);
                    break;
                case $bankTransferService::TRANSFER_TYPE_IMPS:
                case $bankTransferService::TRANSFER_TYPE_NEFT:
                case $bankTransferService::TRANSFER_TYPE_RTGS:
                case $bankTransferService::TRANSFER_TYPE_FT:
                case $bankTransferService::TRANSFER_TYPE_IFT:
                    $response =  $bankTransferService->routeForCollectXBankTransferRequestViaWorkerFlow($input, $provider, "bank_transfer_process");
                    break;
            }
            $this->trace->info(Tracecode::COLLECTX_PROCESS_TRANSFER_JOB_RESPONSE, [
                "response" => $response
            ]);
        }
        catch (\Throwable $ex)
        {
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
            $this->trace->info(TraceCode::COLLECTX_PROCESS_TRANSFER_JOB_SUCCESS, [
                "provider" => $this->bankProvider
            ]);
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
