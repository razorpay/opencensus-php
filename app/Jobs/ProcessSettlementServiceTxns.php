<?php

namespace RZP\Jobs;

use Config;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use Razorpay\Trace\Logger as Trace;
use RZP\Jobs\Transfers\TransferRecon;
use RZP\Models\Settlement\SlackNotification;

class ProcessSettlementServiceTxns extends Job
{
    const MODE                  = 'mode';
    const SETTLED_AT            = 'settled_at';
    const SETTLEMENT_ID         = 'settlement_id';
    const TRANSACTION_IDS       = 'transaction_ids';
    const TRANSACTIONS_COUNT    = 'transactions_count';
    const MAX_RETRY_ATTEMPTS    = 5;
    const JOB_RELEASE_WAIT      = 120;

    protected $queueConfigKey = 'settlement_service_txns';

    protected $mode;

    protected $data;

    /**
     * Create a new job instance.
     * @param $payload array
     *
     * @return void
     */
    public function __construct(array $payload)
    {
        $this -> setMode($payload);

        parent::__construct($this->mode);

        $this->data = $this->getSettlementsData($payload);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        parent::handle();

        $values = [
            Transaction\Entity::SETTLED_AT      => $this->data[self::SETTLED_AT],
            Transaction\Entity::SETTLED         => true,
            Transaction\Entity::SETTLEMENT_ID   => $this->data[self::SETTLEMENT_ID],
        ];

        $traceData = $values + [
                self::TRANSACTIONS_COUNT => sizeof($this->data[self::TRANSACTION_IDS]),
                self::MODE               => $this->mode,
            ];

        try
        {
            $this->trace->info(TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATE_HANDLER_INIT);
            $settlement = $this->repoManager->settlement->findOrFail($this->data[self::SETTLEMENT_ID]);

            $this->repoManager->transaction->updateAsSettled(
                $this->data[self::TRANSACTION_IDS],
                $values,
                true);

            if ($settlement->merchant->isLinkedAccount() === true)
            {
                $this->trace->info(
                    TraceCode::TRANSFER_SETTLEMENT_PROCESS_SQS_PUSH_INIT,
                    [
                        'txn_ids' => $this->data[self::TRANSACTION_IDS],
                    ]
                );

                $startTime = microtime(true);

                $txnIds = array_chunk($this->data[self::TRANSACTION_IDS], 500);

                foreach ($txnIds as $txnIdsChunk)
                {
                    $input['transaction_ids'] = $txnIdsChunk;

                    TransferRecon::dispatch($input, $this->mode);
                }

                $endTime = microtime(true);

                $this->trace->info(
                    TraceCode::TRANSFER_SETTLEMENT_SQS_PUSH_COMPLETE,
                    [
                        'count'         => count($this->data[self::TRANSACTION_IDS]),
                        'time_taken'    => $endTime - $startTime,
                    ]
                );
            }
            $this->delete();
            $this->trace->info(TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATED, $traceData);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATE_FAILED,
                $traceData);

            $this->checkAndRetry();
        }
    }

    /**
     * Set mode for job.
     * @param $payload array
     *
     * @return void
     */
    protected function setMode(array $payload)
    {
        if (array_key_exists(self::MODE, $payload) === true)
        {
            $this->mode = $payload[self::MODE];
        }
    }

    /**
     * Get settlement details from message payload.
     * @param $payload array
     *
     * @return array
     */
    protected function getSettlementsData(array $payload) : array
    {
        return [
            self::SETTLED_AT      => $payload[self::SETTLED_AT],
            self::SETTLEMENT_ID   => $payload[self::SETTLEMENT_ID],
            self::TRANSACTION_IDS => $payload[self::TRANSACTION_IDS],
        ];
    }

    /**
     * handle retires in case of failures.
     *
     * @return void
     */

    protected function checkAndRetry()
    {
        try {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATE_RETRY_INIT,
                [
                    'attempts' => $this->attempts(),
                ]
            );

            if ($this->attempts() > self::MAX_RETRY_ATTEMPTS) {
                $values = [
                    Transaction\Entity::SETTLED_AT => $this->data[self::SETTLED_AT],
                    Transaction\Entity::SETTLED => true,
                    Transaction\Entity::SETTLEMENT_ID => $this->data[self::SETTLEMENT_ID],
                ];

                $traceData = $values + [
                        self::TRANSACTIONS_COUNT => sizeof($this->data[self::TRANSACTION_IDS]),
                        self::MODE => $this->mode,
                    ];

                $this->trace->error(
                    TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATE_FAILED_AFTER_RETRIES,
                    $traceData);

                $operation = 'Transactions update failed for settlement: ' . $traceData[self::SETTLEMENT_ID];
                (new SlackNotification)->send(
                    $operation,
                    $traceData,
                    null,
                    1,
                    'settlement_alerts');

                $this->delete();

            } else {
                $this->release(self::JOB_RELEASE_WAIT);
            }
        }
        catch (\Throwable $e){
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_TRANSACTIONS_UPDATE_RETRY_FAILED);
            $this->delete();
        }
    }
}
