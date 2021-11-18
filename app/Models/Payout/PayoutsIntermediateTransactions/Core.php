<?php

namespace RZP\Models\Payout\PayoutsIntermediateTransactions;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Transaction;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
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
            'payout_intermediate_txn_marked_completed' => [],
            'payout_intermediate_txn_marked_reversed'  => [],
            'payout_intermediate_txn_update_failed'    => []
        ];

        $time = Carbon::now(Timezone::IST)->subMinutes(self::DEFAULT_TIME_TILL_RECON)->getTimestamp();

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CRON_UPDATE_REQUEST,
                           [
                               'input_time' => $time,
                           ]
        );

        // fetched from slave. $pendingIntermediateTxns->payout->getConnectionName would return slave-live. This would
        // cause issue as updates would go to slave-replica then
        $pendingIntermediateTxns = $this->repo->payouts_intermediate_transactions
                                              ->fetchPendingTransactionsBeforeGivenTime($time);

        foreach ($pendingIntermediateTxns as $pendingIntermediateTxn)
        {
            try
            {
                // taking mutex on payout id because egress flow also takes on payout id and we want only one of them
                // to operate on a given payout id at a time.
                $response = $this->mutex->acquireAndRelease(
                    $pendingIntermediateTxn->getPayoutId(),
                    function () use ($pendingIntermediateTxn, $response)
                    {
                        // reloading it from master
                        $intermediateTxn = $this->repo->payouts_intermediate_transactions->findOrFail($pendingIntermediateTxn->getId());

                        /** @var PayoutEntity $payout */
                        $payout = $intermediateTxn->payout;

                        // DEBUGGED AN ISSUE WHERE INTERMEDIATE TXN LOADED FROM SLAVE CONNECTION EARLIER AND THEIR RELATIONS
                        // WERE ALSO GETTING FETCHED FROM SLAVE, BECAUSE OF WHICH UPDATE TO THEM WAS GOING TO SLAVE-REPLICA.
                        // ENSURE THAT CONNECTIONS HERE ARE TO LIVE(MASTER) FOR ALL ENTITIES.
                        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CONNECTION_DEBUG,
                                           [
                                               'payout_connection_name'  => $payout->getConnectionName(),
                                               'balance_connection_name' => $payout->balance->getConnectionName(),
                                               'intermediate_txn_name'   => $intermediateTxn->getConnectionName()
                                           ]
                        );

                        if ($payout->hasTransaction() === true)
                        {
                            $this->transaction([$this, 'markIntermediateTransactionCompleted'], $intermediateTxn);

                            array_push($response["payout_intermediate_txn_marked_completed"], $intermediateTxn->getId());
                        }
                        else
                        {
                            $this->markIntermediateTransactionReversedForPayout($payout, $intermediateTxn);

                            array_push($response["payout_intermediate_txn_marked_reversed"], $intermediateTxn->getId());
                        }

                        return $response;
                    },
                    self::MUTEX_LOCK_TIMEOUT,
                    ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
                );
            }
            catch (\Throwable $exception)
            {
                $this->trace->traceException(
                    $exception,
                    Trace::ERROR,
                    TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_CRON_UPDATE_REQUEST_FAILED,
                    [
                        'intermediate_txn_id' => $pendingIntermediateTxn->getId()
                    ]);

                array_push($response["payout_intermediate_txn_update_failed"], $pendingIntermediateTxn->getId());
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

    public function markIntermediateTransactionReversedForPayout(PayoutEntity $payout, Entity &$intermediateTxn = null)
    {
        if ($intermediateTxn === null)
        {
            /** @var Entity $intermediateTxn */
            $intermediateTxn = $this->fetchIntermediateTransactionForAGivenPayoutId($payout->getId());
        }

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

            // this has mutex which is in a db txn , but it still works , because there is a mutex outside on payout id
            (new PayoutCore)->handlePayoutReversedForHighTpsMerchants($payout, 'REVERSAL');

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

        // mutex should be outside of db txn because if its inside db txn , other processes wont see changes.
        // therefore using lock for update because there are validations in setStatus function.

        $intermediateTxn = $this->repo->payouts_intermediate_transactions->lockForUpdate($intermediateTxn->getId());

        $intermediateTxn->setStatus(Status::REVERSED);

        $this->repo->payouts_intermediate_transactions->saveOrFail($intermediateTxn);

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

        // mutex should be outside of db txn because if its inside db txn , other processes wont see changes.
        // therefore using lock for update because there are validations in setStatus function.
        $intermediateTxn = $this->repo->payouts_intermediate_transactions->lockForUpdate($intermediateTxn->getId());

        $intermediateTxn->setStatus(Status::COMPLETED);

        $this->repo->payouts_intermediate_transactions->saveOrFail($intermediateTxn);

        $this->trace->info(TraceCode::PAYOUT_INTERMEDIATE_TRANSACTIONS_MARK_COMPLETED_RESPONSE,
                           [
                               'payout_intermediate_transaction' => $intermediateTxn->toArrayPublic(),
                           ]
        );
    }
}
