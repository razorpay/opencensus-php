<?php

namespace RZP\Jobs\Ledger;

use App;
use Exception;
use RZP\Constants\Mode;
use RZP\Jobs\Job;
use Ramsey\Uuid\Uuid;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Refund;

use Razorpay\Trace\Logger as LoggerTrace;
use RZP\Services\Ledger as LedgerService;
use RZP\Models\Ledger\Constants as LedgerConstants;
use RZP\Models\Payment\Refund\Entity as RefundEntity;
use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\LedgerOutbox\Constants as LedgerOutboxConstants;

class CreateMissingRefundTransactionsForReverseShadow extends Job
{
    const RETRY_INTERVAL    = 300;
    const MAX_RETRY_ATTEMPT = 1;

    protected $app;
    protected $repo;

    protected $refundId;

    public function __construct(string $mode, string $refundId)
    {
        parent::__construct($mode);

        $this->refundId = $refundId;
    }

    public function handle()
    {
        parent::handle();

        $this->app =  App::getFacadeRoot();
        $this->repo = $this->app['repo'];
        $this->trace = $this->app['trace'];

        if($this->mode === Mode::TEST)
        {
            return;
        }

        try
        {
            $refund = $this->repo->refund->findOrFail($this->refundId);

            $this->validateAndCreateMissingRefundTransaction($refund);

        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                500,
                TraceCode::MISSING_REFUND_TRANSACTION_CREATION_FAILED,
                [
                    "refund_id" => $this->refundId
                ]);
        }
    }


    /**
     * @throws BadRequestValidationFailureException
     * @throws Exception
     */
    public function validateAndCreateMissingRefundTransaction(RefundEntity $refund)
    {
        if($refund->merchant->isFeatureEnabled(FeatureConstants::PG_LEDGER_REVERSE_SHADOW) === false)
        {
            throw(new \Exception("reverse shadow not enabled"));
        }
        else
        {
            app('worker.ctx')->setLedgerDualWriteFlow(true);
        }

        try
        {
            $ledgerService = $this->app['ledger'];

            $publicId = $refund->getPublicId();

            $transactorEvent = "refund_processed";

            $journal = $this->getJournalByTransactorInfo($publicId, $transactorEvent, $ledgerService);

            if($journal === null)
            {
                return [
                    "refund_id" => $publicId,
                    "message"   => "journal not present for refund, won't create transaction"
                ];
            }

            $transactionCreateInput = [
                "id"                => $refund->getId(),
                "payment_id"        => $refund->getPaymentId(),
                "amount"            => $refund->getAmount(),
                "base_amount"       => $refund->getBaseAmount(),
                "speed_decisioned"  => $refund->getSpeedDecisioned(),
                "gateway"           => $refund->getGateway(),
                "fee"               => $refund->getFee(),
                "tax"               => $refund->getTax(),
                "journal_id"        => $journal['id']
            ];

            if($refund->getModeRequested() != null)
            {
                $transactionCreateInput["mode"] = $refund->getModeRequested();
            }

            (new Refund\Service())->scroogeRefundsTransactionCreate($transactionCreateInput);
        }
        catch (\Exception $e)
        {
            $this->trace->traceException($e, LoggerTrace::ERROR, TraceCode::MISSING_REFUND_TRANSACTION_CREATION_FAILED, [
                "refund_id" => $refund->getId()
            ]);
        }

        return [
            "refund_id" => $refund->getId()
        ];
    }

    private function getJournalByTransactorInfo(string $transactorId, string $transactorEvent, $ledgerService)
    {
        $app = App::getFacadeRoot();

        $trace = $app['trace'];

        $fetchJournalPayload = [
            LedgerConstants::TRANSACTOR_ID        => $transactorId,
            LedgerConstants::TRANSACTOR_EVENT     => $transactorEvent
        ];

        $requestHeaders = [
            LedgerService::LEDGER_TENANT_HEADER    => LedgerConstants::TENANT_PG,
            LedgerService::IDEMPOTENCY_KEY_HEADER  => Uuid::uuid1()
        ];

        try
        {
            $response = $ledgerService->fetchByTransactor($fetchJournalPayload, $requestHeaders, true);

            return $response['body'];
        }
        catch(\Exception $e)
        {
            $trace->debug(TraceCode::FETCH_JOURNAL_FAILED, [
                LedgerConstants::MESSAGE            => $e->getMessage(),
                LedgerOutboxConstants::PAYLOAD      => $fetchJournalPayload
            ]);

            return null;
        }
    }
}
