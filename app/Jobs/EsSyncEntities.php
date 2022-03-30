<?php

namespace RZP\Jobs;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base;
use RZP\Models\Base\EsRepository;
use RZP\Trace\TraceCode;
use RZP\Models\Payout;
use RZP\Models\Transaction;
use RZP\Exception\LogicException;
use Razorpay\Trace\Logger as Trace;

/**
 * Es sync job class.
 * Receives insert/update/delete events of models and syncs the same change to ES.
 */
class EsSyncEntities extends Job
{
    const MAX_JOB_ATTEMPTS = 3;
    const JOB_RELEASE_WAIT = 30;
    const MAX_BATCH_SIZE = 1000;

    private $action;
    private $entity;
    private $ids;
    private $id;

    private $repo;
    private $esRepo;
    private $rearch;

    private $startTime;
    private $endTime;
    private $batchSize;
    private $merchantIds;
    protected $mode;

    public $timeout = 4000;

    //changing queue to pg_invoice queue instead of the default queue
    /**
     * @var string
     */
    protected $queueConfigKey = 'pg_einvoice';

    public function __construct(
        string $mode,
        string $action,
        string $entity,
        $startTime,
        $endTime,
        $batchSize,
        $merchantIds,
        bool $rearch = false)
    {
        parent::__construct($mode);

        $this->mode = $mode;
        $this->action = $action;
        $this->entity = $entity;
        // incase of rearch payments we have to call findOrFail instead of
        // findManyForIndexingByIds
        $this->rearch = $rearch;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->batchSize = $batchSize;
        $this->merchantIds = $merchantIds;
    }

    public function handle()
    {
        $this->handleBackwardCompatibility();

        parent::handle();

        $skip = 0;

        $totalCount = 0;

        if($this->entity === 'payout')
        {
            $this->repo = (new Payout\Repository());

            do {
                $payouts = $this->repo
                    ->fetchPayoutsWithSkip($skip, $this->batchSize, $this->merchantIds, $this->startTime, $this->endTime);

                $this->ids = array_wrap($payouts);

                $count = count($payouts);

                $skip += $count;

                try {
                    EsSyncPgEinvoiceQueue::dispatch($this->mode,
                        EsRepository::UPDATE, 'payout',
                        $payouts)->delay(0.2);
                } catch (\Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::ES_SYNC_PUSH_FAILED);
                }

                $totalCount += $count;

                $this->trace->debug(TraceCode::ES_DEBUG_RESPONSE, [
                    'count' => $count,
                ]);

            }   while ($this->batchSize === $count);

            $this->trace->debug(TraceCode::ES_DEBUG_TOTAL_COUNT, [
                'totalPayouts'  => $totalCount,
            ]);
        }
        else if($this->entity === 'transaction')
        {
            $this->repo = (new Transaction\Repository());

            do {
                $transactions = $this->repo
                    ->fetchTransactionsWithSkip($skip, $this->batchSize, $this->merchantIds, $this->startTime, $this->endTime);

                $this->ids = array_wrap($transactions);

                $count = count($transactions);

                $skip += $count;

                try {
                    EsSyncPgEinvoiceQueue::dispatch($this->mode,
                        EsRepository::UPDATE, 'transaction',
                        $transactions)->delay(0.2);
                } catch (\Throwable $e) {
                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::ES_SYNC_PUSH_FAILED);
                }

                $totalCount += $count;

                $this->trace->debug(TraceCode::ES_DEBUG_RESPONSE, [
                    'count' => $count,
                ]);

            }   while ($this->batchSize === $count);

            $this->trace->debug(TraceCode::ES_DEBUG_TOTAL_COUNT, [
                'totalPayouts'  => $totalCount,
            ]);
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_NO_MATCHING_ENTITY);
        }
    }

    /**
     * Handles backward compatibility , remove this function after deployment
     *
     * If this->id is set => request came from deserialization
     *
     */
    private function handleBackwardCompatibility()
    {
        if (isset($this->id) === true)
        {
            $this->ids = array_wrap($this->id);
        }
    }
}
