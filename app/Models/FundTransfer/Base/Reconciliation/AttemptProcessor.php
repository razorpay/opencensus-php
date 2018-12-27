<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;
use Carbon\Carbon;

use Razorpay\Trace\Logger;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Jobs\AttemptStatusCheck;
use RZP\Exception\LogicException;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base\PublicCollection;
/**
 * Class AttemptProcessor
 * @package RZP\Models\FundTransfer\Base\Reconciliation
 *
 * API based channels should send request to bank to ge the status of transactions.
 * This class is responsible for providing attempts which has to be reconciled for API based channels.
 * It will check for the attempts which are already been initiated and reconcile them.
 * Status check request is made on every initiated attempts.
 */
abstract class AttemptProcessor extends Processor
{
    /**
     * Makes status request for the ids mentioned and updated the status based on the response received
     *
     * @param PublicCollection $attempts
     * @return array
     */
    public function reconcile(PublicCollection $attempts): array
    {
        $unprocessedCount = 0;

        if ($attempts->count() === 0)
        {
            return [
                'message' => 'No attempts to reconcile'
            ];
        }

        foreach ($attempts as $attempt)
        {
            $status = $this->dispatchForStatusCheck($attempt);

            if ($status === false)
            {
                $unprocessedCount++;
            }
        }

        return [
            'channel'               => static::$channel,
            'total_count'           => $attempts->count(),
            'unprocessed_count'     => $unprocessedCount,
        ];
    }

    protected function dispatchForStatusCheck(Attempt\Entity $attempt): bool
    {
        try
        {
            AttemptStatusCheck::dispatch($this->mode, $attempt->getId());
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FTA_DISPATCH_FOR_STATUS_CHECK_FAILED,
                [
                    'fta_id' => $attempt->getId(),
                ]);

            return false;
        }

        return true;
    }

    /**
     * Takes lock on 100 attempts and process the same
     * If the attempt is already locked then ignore them
     *
     * @param array $input
     */
    protected function processReconciliation(array $input)
    {
        $attempts = $this->getAttemptsToReconcile($input);

        $response = $this->reconcile($attempts);

        $this->trace->info(
            TraceCode::ATTEMPT_RECONCILIATION_STATUS,
            [
                'channel' => static::$channel,
            ] + $response);
    }

    protected function getTimestampRangeFromDuration(int $duration): array
    {
        if ($duration === 0)
        {
            return [null, null];
        }

        $currentTimestamp = Carbon::now(Timezone::IST);

        $toTimestamp = $currentTimestamp->getTimestamp();

        $fromTimestamp = $currentTimestamp->subSeconds($duration)->getTimestamp();

        return [$fromTimestamp, $toTimestamp];
    }

    protected function getAttemptsToReconcile(array $input): PublicCollection
    {
        $ftaIds = $input['fta_ids'] ?? [];

        if (empty($ftaIds) === false)
        {
            // in case if the fta_ids present then we dont need check for current status for fta.
            // because this would be done to verify the status again if the final state of the attempt has changed
            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getAttemptsWithIds(
                                    static::$channel,
                                    $ftaIds);
        }
        else
        {
            $status = $input['status'] ?? Attempt\Status::INITIATED;

            $duration = $input['duration'] ?? 0;

            list($fromTime, $toTime) = $this->getTimestampRangeFromDuration($duration);

            $attempts = $this->repo
                              ->fund_transfer_attempt
                              ->getAttemptsWithStatusBetweenTimestamps(
                                  static::$channel,
                                  $status,
                                  $fromTime,
                                  $toTime);
        }

        return $attempts;
    }

    /**
     * @param array $input
     * @return array
     * @throws LogicException
     */
    protected function verifySettlements(array $input)
    {
        $lock = new Attempt\Lock(static::$channel);

        $attempts = $this->fetchAttempts($input);

        $response = $lock->acquireLockAndProcessAttempts(
            $attempts,
            function(PublicCollection $collection)
            {
                return $this->startVerification($collection);
            });

        $this->trace->info(
            TraceCode::ATTEMPT_RECONCILIATION_STATUS,
            [
                'channel' => static::$channel,
            ] + $response);

        return $response;
    }

    /**
     * @param array $input
     * @return array
     */
    protected function getTimestamps(array $input): array
    {
        list($from, $to) = [null, null];

        if ((isset($input['from']) === true) and (isset($input['to']) === true))
        {
            $from = $input['from'];
            $to = $input['to'];
        }
        else
        {
            $from = Carbon::yesterday(Timezone::IST)->getTimestamp();

            $to = Carbon::today(Timezone::IST)->getTimestamp() - 1;
        }

        return [$from, $to];
    }

    protected function fetchAttempts($input)
    {
        $batchSize = 150;

        if (empty($input['fta_ids']) === false)
        {
            return $this->repo->fund_transfer_attempt
                        ->getAttemptsWithIds(
                            static::$channel,
                            $input['fta_ids'],
                            $batchSize
                        );
        }

        list($from, $to) = $this->getTimestamps($input);

        $status = $input['status'] ?? null;

        return $this->repo->fund_transfer_attempt
            ->getAttemptsWithStatusBetweenTimestamps(
                static::$channel,
                $status,
                $from,
                $to,
                $batchSize
            );

        throw new LogicException('Invalid input', null, ['input' => $input]);
    }
}
