<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;

use RZP\Trace\TraceCode;
use RZP\Models\Base\PublicCollection;
use RZP\Models\FundTransfer\Attempt;

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
        $response  = [];

        if ($attempts->count() === 0)
        {
            return [
                'message' => 'No attempts to reconcile'
            ];
        }

        $summary = $this->startReconciliation($attempts);

        $this->updateResponse($response, $summary);

        return $response;
    }

    /**
     * Takes lock on 100 attempts and process the same
     * If the attempt is already locked then ignore them
     *
     * @param array $input
     */
    protected function processReconciliation(array $input)
    {
        $lock = new Attempt\Lock(static::$channel);

        //
        // We fetch 150 attempts considering there would be some attempt which is already in process
        // even though we reconcile only 100 attempts at a time
        //
        $batchSize = 150;

        $attempts = $this->repo
                         ->fund_transfer_attempt
                         ->getAttemptsBetweenTimestampsWithStatus(
                             static::$channel,
                             Attempt\Status::INITIATED,
                             null,
                             null,
                             $batchSize);

        $lockedAttempts = $lock->lockAttempts($attempts);

        $response = $this->reconcile($lockedAttempts);

        $lock->releaseAttempts($attempts);

        $this->trace->info(
            TraceCode::ATTEMPT_RECONCILIATION_STATUS,
            [
                'channel' => static::$channel,
            ] + $response);
    }

    protected function updateResponse(array & $response, array $summary)
    {
        if (empty($response) === true)
        {
            $response = $summary;

            return;
        }

        $response['total_count'] += $summary['total_count'];

        $response['unprocessed_count'] += $summary['unprocessed_count'];
    }
}
