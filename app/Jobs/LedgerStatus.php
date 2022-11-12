<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Models\Reversal\Core as ReversalCore;
use RZP\Models\Adjustment\Core as AdjustmentCore;
use RZP\Models\BankTransfer\Core as BankTransferCore;
use RZP\Models\FundAccount\Validation\Core as FavCore;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\CreditTransfer\Core as CreditTransferCore;

class LedgerStatus extends Job
{
    const MAX_RETRY_ATTEMPTS = 5;
    // the delay is in seconds
    // used in an exponential backoff manner
    const MIN_RETRY_DELAY    = 3;

    protected $trace;

    /**
     * used to map the SQS queue worker to this code
     *
     * @var string
     */
    protected $queueConfigKey = 'ledger_status';

    protected $ledgerRequest;
    protected $feeSplit;
    protected $retryEnabled;
    protected $transactorId;
    protected $transactorEvent;

    const LEDGER_STATUS_MUTEX_RESOURCE = 'LEDGER_STATUS_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 60;

    // ledger transactor id prefix
    const BANK_TRANSFER_PREFIX   = "bt_";
    const ADJUSTMENT_PREFIX      = "adj_";
    const PAYOUT_PREFIX          = "pout_";
    const FAV_PREFIX             = "fav_";
    const REVERSAL_PREFIX        = "rvrsl_";
    const CREDIT_TRANSFER_PREFIX = "ct_";

    // ledger transactor event prefix
    const BANK_TRANSFER_TYPE    = "fund_loading";
    const ADJUSTMENT_TYPE       = "adjustment";
    const PAYOUT_TYPE           = "payout";
    const FAV_TYPE              = "fav";

    // ledger response
    const LEDGER_RESPONSE_BODY      = 'response_body';
    const LEDGER_RESPONSE_MSG       = 'msg';
    const LEDGER_RECORD_NOT_FOUND   = 'record_not_found';

    public function __construct(string $mode, array $ledgerRequest, array $feeSplit = null, bool $retryEnabled = true)
    {
        parent::__construct($mode);

        $this->ledgerRequest = $ledgerRequest;
        $this->feeSplit      = $feeSplit;
        $this->retryEnabled  = $retryEnabled;
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $traceData = [
                'ledgerRequest'     => $this->ledgerRequest,
                'feeSplit'          => $this->feeSplit,
            ];

            $this->trace->info(
                TraceCode::LEDGER_STATUS_QUEUE_JOB_INIT,
                $traceData);

            $this->transactorId = $this->ledgerRequest["transactor_id"];
            $this->transactorEvent = $this->ledgerRequest["transactor_event"];

            $resource = sprintf(self::LEDGER_STATUS_MUTEX_RESOURCE, $this->transactorId, $this->transactorEvent);

            $this->mutex->acquireAndRelease(
                $resource,
                function ()
                {
                    // logic
                    if (in_array($this->transactorEvent, Ledger\Base::LEDGER_DEBIT_EVENTS, true) === true) {
                        // debit scenario
                        $this->checkAndProcessLedgerStatusDebitScenario();
                    }
                    else
                    {
                        // credit scenario
                        $this->checkAndProcessLedgerStatusCreditScenario();
                    }
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_QUEUE_JOB_SUCCESSFUL,
                        [
                            'transactor_id'      => $this->transactorId,
                            'transactor_event'   => $this->transactorEvent,
                        ]
                    );
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
            );

            if ($this->retryEnabled === true)
            {
                // delete job on successful processing
                $this->delete();
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::LEDGER_STATUS_QUEUE_JOB_EXCEPTION,
                [
                    'transactor_id'     => $this->transactorId,
                    'transactor_event'  => $this->transactorEvent,
                    'ledger_request'    => $this->ledgerRequest
                ]);

            if ($this->retryEnabled === true)
            {
                // retry job on failure
                $this->checkRetry($ex);
            }
            else
            {
                throw $ex;
            }
        }
    }

    /**
     * markDebitEntityAsSuccess marks the DEBIT case entities as success if there was a successful entry on ledger
     * @throws BadRequestValidationFailureException
     * @throws \Throwable
     */
    private function markDebitEntityAsSuccess($ledgerResponse)
    {
        $this->trace->info(TraceCode::LEDGER_STATUS_MARK_ENTITY_SUCCESS,
            [
                'transactor_id'      => $this->transactorId,
                'transactor_event'   => $this->transactorEvent,
            ]);

        if (strpos($this->transactorId, self::FAV_PREFIX) !== false)
        {
            $fav = $this->repoManager->fund_account_validation->findByPublicId($this->transactorId);
            // Todo: discussion going on if we can skip fee split part
            (new FavCore)->processFavAfterLedgerStatusCheck($fav, $ledgerResponse);
        }
        else if (strpos($this->transactorId, self::ADJUSTMENT_PREFIX) !== false)
        {
            // Todo: discuss after state changes
            $adj = $this->repoManager->adjustment->findByPublicId($this->transactorId);
            (new AdjustmentCore)->processAdjustmentAfterLedgerStatusCheck($adj, $ledgerResponse);
        }
        else if (strpos($this->transactorId, self::PAYOUT_PREFIX) !== false) {
            $payout = $this->repoManager->payout->findByPublicId($this->transactorId);
            (new PayoutCore)->processPayoutAfterLedgerStatusCheck($payout, $ledgerResponse);
        }
    }

    /**
     * markDebitEntityAsFailed marks the DEBIT cases as failed if there was a failure on ledger
     * @throws \Throwable
     */
    private function markDebitEntityAsFailed()
    {
        $this->trace->info(TraceCode::LEDGER_STATUS_MARK_ENTITY_FAILED,
            [
                'transactor_id'      => $this->transactorId,
                'transactor_event'   => $this->transactorEvent,
            ]);

        if (strpos($this->transactorId, self::FAV_PREFIX) !== false)
        {
            $fav = $this->repoManager->fund_account_validation->findByPublicId($this->transactorId);
            (new FavCore)->failFavAfterLedgerStatusCheck($fav);
        }
        else if (strpos($this->transactorId, self::ADJUSTMENT_PREFIX) !== false)
        {
            // Todo: discuss after state changes
            $adj = $this->repoManager->adjustment->findByPublicId($this->transactorId);
            (new AdjustmentCore)->failAdjustmentAfterLedgerStatusCheck($adj);
        }
        else if (strpos($this->transactorId, self::PAYOUT_PREFIX) !== false)
        {
            $payout = $this->repoManager->payout->findByPublicId($this->transactorId);

            $payoutCoreHandler = new PayoutCore();

            // This is a special case for payouts.
            // We decided that, if due to some indeterminate error, a journal entry was not created on ledger
            // then we shall retry creation of the journal entry for payout debit.
            // These retries shall happen till the penultimate attempt.
            // In the last attempt, we shall fail the payout.
            // This is only supposed to happen in the async job, and not via cron.
            // That is, the cron will always directly fail the payout, whereas async job will retry
            // creation of debit journal entry
            if (($this->attempts() < (self::MAX_RETRY_ATTEMPTS - 1)) and
                ($this->retryEnabled === true))
            {
                $payoutCoreHandler->tryProcessingOfPayoutPostLedgerFailureElseFail($payout);
            }
            else
            {
                $payoutCoreHandler->failPayoutPostLedgerFailure($payout);
            }
        }
    }

    /**
     * markCreditEntityAsSuccess marks the NON DEBIT case entities as success if there was a successful entry on ledger
     * @throws \Throwable
     */
    private function markCreditEntityAsSuccess($ledgerResponse)
    {
        $this->trace->info(TraceCode::LEDGER_STATUS_MARK_ENTITY_SUCCESS,
            [
                'transactor_id'      => $this->transactorId,
                'transactor_event'   => $this->transactorEvent,
            ]);

        if (strpos($this->transactorId, self::BANK_TRANSFER_PREFIX) !== false)
        {
            // Todo: discuss after state changes
            $bankTransfer = $this->repoManager->bank_transfer->findByPublicId($this->transactorId);
            (new BankTransferCore)->processBankTransferAfterLedgerStatusCheck($bankTransfer, $ledgerResponse);
        }
        else if (strpos($this->transactorId, self::ADJUSTMENT_PREFIX) !== false)
        {
            // Todo: discuss after state changes
            $adj = $this->repoManager->adjustment->findByPublicId($this->transactorId);
            (new AdjustmentCore)->processAdjustmentAfterLedgerStatusCheck($adj, $ledgerResponse);
        }
        else if (strpos($this->transactorId, self::CREDIT_TRANSFER_PREFIX) !== false)
        {
            // Todo: discuss after state changes
            $creditTransfer = $this->repoManager->credit_transfer->findByPublicId($this->transactorId);
            (new CreditTransferCore)->processCreditTransferAfterLedgerStatusCheck($creditTransfer, $ledgerResponse);
        }

        // nothing to do here in case of fav_processed, fav_reversed, payout_processed, reward fund_loading events
    }

    /**
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    private function checkAndProcessLedgerStatusDebitScenario()
    {
        try {
            $input = [
                Ledger\Base::TRANSACTOR_ID      => $this->transactorId,
                Ledger\Base::TRANSACTOR_EVENT   => $this->transactorEvent,
            ];
            $response = (new Ledger\Base)->fetchJournalByTransactor($input);

            // successful status checked
            $this->markDebitEntityAsSuccess($response);
        }
        catch (\RZP\Exception\BaseException $ex)
        {
            $exceptionData = $ex->getData();

            // If no journal found
            if (strpos($exceptionData[self::LEDGER_RESPONSE_BODY][self::LEDGER_RESPONSE_MSG], self::LEDGER_RECORD_NOT_FOUND) !== false)
            {
                // mark entity as failed
                $this->markDebitEntityAsFailed();
            }
            else
            {
                throw $ex;
            }
        }
    }

    /**
     * @throws \RZP\Exception\RuntimeException
     * @throws \Throwable
     */
    private function checkAndProcessLedgerStatusCreditScenario()
    {
        try {
            $input = [
                Ledger\Base::TRANSACTOR_ID      => $this->transactorId,
                Ledger\Base::TRANSACTOR_EVENT   => $this->transactorEvent,
            ];
            $response = (new Ledger\Base)->fetchJournalByTransactor($input);

            // successful status checked
            $this->markCreditEntityAsSuccess($response);
        }
        catch (\RZP\Exception\BaseException $ex)
        {
            $exceptionData = $ex->getData();

            // If no journal found
            if (strpos($exceptionData[self::LEDGER_RESPONSE_BODY][self::LEDGER_RESPONSE_MSG], self::LEDGER_RECORD_NOT_FOUND) !== false)
            {
                // mark entity as success
                $ledgerResponse = (new Ledger\Base)->createJournalEntryFromJob($this->ledgerRequest);
                $this->markCreditEntityAsSuccess($ledgerResponse);
            }
            else
            {
                throw $ex;
            }
        }
    }

    protected function checkRetry($ex = null)
    {
        $noOfAttempts = $this->attempts();

        if ($noOfAttempts < self::MAX_RETRY_ATTEMPTS)
        {
            $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));

            $this->trace->info(
                TraceCode::LEDGER_STATUS_QUEUE_JOB_RELEASED,
                [
                    'transactor_id'      => $this->transactorId,
                    'transactor_event'   => $this->transactorEvent,
                    'no_of_attempts'     => $noOfAttempts,
                ]
            );
        }
        else
        {
            // Add sumo alert for trace
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::LEDGER_STATUS_JOB_RETRY_EXHAUSTED,
                [
                    'transactor_id'      => $this->transactorId,
                    'transactor_event'   => $this->transactorEvent,
                    'no_of_attempts'     => $noOfAttempts,
                ]);
            $this->delete();
        }
    }
}
