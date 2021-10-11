<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use RZP\Models\Payout\Core as PayoutCore;
use RZP\Models\Payout\Entity as PayoutEntity;

class Core extends Base\Core
{
    const MUTEX_LOCK_TIMEOUT = 60;

    /**
     * This is in minutes.
     * This is the time after which we'll do recon on pending intermediate transactions and fix them.
     */
    const DEFAULT_TIME_TILL_RECON = 15;

    protected $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];
    }

    public function create(array $input): Entity
    {
        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CREATE_REQUEST,
                           [
                               'input' => $input,
                           ]
        );

        // building entity
        $payoutIntermediateTransaction = (new Entity)->build($input);

        $payoutIntermediateTransaction->setStatus(Status::PENDING);

        $this->repo->saveOrFail($payoutIntermediateTransaction);

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CREATE_RESPONSE,
                           [
                               'payout_intermediate_transaction' => $payoutIntermediateTransaction->toArray(),
                           ]
        );

        return $payoutIntermediateTransaction;
    }

    public function updatePayoutIntermediateTransactions()
    {
        $response = [
            'payout_ids_marked_completed' => [],
            'payout_ids_marked_reversed'  => [],
        ];

        $time = Carbon::now(Timezone::IST)->subMinutes(self::DEFAULT_TIME_TILL_RECON)->getTimestamp();

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CRON_UPDATE_REQUEST,
                           [
                               'input_time' => $time,
                           ]
        );

        $pendingIntermediateTxns = $this->repo->payouts_intermediate_transactions
                                              ->fetchPendingTransactionsBeforeGivenTime($time);

        foreach ($pendingIntermediateTxns as $pendingIntermediateTxn)
        {
            /** @var PayoutEntity $payout */
            $payout = $pendingIntermediateTxn->payout;

            if ($payout->hasTransaction() === true)
            {
                // call mark success
                $this->markIntermediateTransactionCompleted($pendingIntermediateTxn);

                array_push($response["payout_ids_marked_completed"], $payout->getId());
            }
            else
            {
                // call mark reversed
                $this->markIntermediateTransactionReversedForPayout($payout);

                array_push($response["payout_ids_marked_reversed"], $payout->getId());
            }
        }

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CRON_UPDATE_RESPONSE,
                           [
                               'input_time' => $time,
                               'response'   => $response,
                           ]
        );

        return $response;
    }

    public function fetchIntermediateTransactionForAGivenPayoutId($payoutId)
    {
        return $this->repo->payouts_intermediate_transactions->fetchIntermediateTransactionForAGivenPayoutId($payoutId);
    }

    public function fetchTransaction($txnId)
    {
        return $this->repo->transaction->findById($txnId);
    }

    public function markIntermediateTransactionReversedForPayout(PayoutEntity $payout)
    {
        /** @var Entity $intermediateTxn */
        $intermediateTxn = $this->fetchIntermediateTransactionForAGivenPayoutId($payout->getId());

        $txnId = $intermediateTxn->getTransactionId();

        $txn = $this->fetchTransaction($txnId);

        $this->repo->transaction(function() use($payout, $intermediateTxn, $txn, $txnId){

            $txnCreatedAt   = $intermediateTxn->getTransactionCreatedAt();
            $closingBalance = $intermediateTxn->getClosingBalance();

            if (count($txn) === 0)
            {
                $txn = (new Transaction\Processor\Payout($payout))->createTransactionWithoutBalanceDeduction();

                $txn->setId($txnId);
                $txn->setCreatedAt($txnCreatedAt);
                $txn->setBalance($closingBalance);

                $txn->accountBalance()->associate($payout->balance);

                $this->repo->transaction->saveOrFailWithoutEsSync($txn);

                $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_DEBIT_TRANSACTION_CREATED,
                                   [
                                       'payout_id'      => $payout->getId(),
                                       'transaction_id' => $txn->getId(),
                                   ]);
            }

            $payout->transaction()->associate($txn);

            $this->repo->payout->saveOrFail($payout);

            $this->markIntermediateTransactionReversed($intermediateTxn);

            (new PayoutCore)->handlePayoutReversed($payout, 'REVERSAL');

            $reversal = $payout->reversal;

            $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_REVERSAL_CREATED,
                               [
                                   'payout_id'               => $payout->getId(),
                                   'reversal_id'             => $reversal->getId(),
                                   'reversal_transaction_id' => $reversal->transaction->getId(),
                               ]);
        });
    }

    public function markIntermediateTransactionReversed(Entity &$intermediateTxn)
    {
        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_MARK_REVERSED_REQUEST,
                           [
                               'payout_intermediate_transaction' => $intermediateTxn->toArrayPublic(),
                           ]
        );

        $this->mutex->acquireAndRelease(
            'payout_intermediate_transaction_' . $intermediateTxn->getId(),
            function () use ($intermediateTxn)
            {
                // reloading the payout here to ensure if any other process
                // gets a mutex on payout resource, it gets a fresh copy
                // of payout to work.
                $this->repo->payouts_intermediate_transactions->reload($intermediateTxn);

                $intermediateTxn->setStatus(Status::REVERSED);

                $this->repo->payouts_intermediate_transactions->saveOrFail($intermediateTxn);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_MARK_REVERSED_RESPONSE,
                           [
                               'payout_intermediate_transaction' => $intermediateTxn->toArrayPublic(),
                           ]
        );
    }

    public function markIntermediateTransactionCompleted(Entity &$intermediateTxn)
    {
        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_MARK_COMPLETED_REQUEST,
                           [
                               'payout_intermediate_transaction' => $intermediateTxn->toArrayPublic(),
                           ]
        );

        $this->mutex->acquireAndRelease(
            'payout_intermediate_transaction_' . $intermediateTxn->getId(),
            function () use ($intermediateTxn)
            {
                // reloading the payout here to ensure if any other process
                // gets a mutex on payout resource, it gets a fresh copy
                // of payout to work.
                $this->repo->payouts_intermediate_transactions->reload($intermediateTxn);

                $intermediateTxn->setStatus(Status::COMPLETED);

                $this->repo->payouts_intermediate_transactions->saveOrFail($intermediateTxn);
            },
            self::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_MARK_COMPLETED_RESPONSE,
                           [
                               'payout_intermediate_transaction' => $intermediateTxn->toArrayPublic(),
                           ]
        );
    }
}
