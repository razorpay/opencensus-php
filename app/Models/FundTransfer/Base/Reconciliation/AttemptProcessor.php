<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation;

use Mail;

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
    protected function processReconciliation(array $input)
    {
        $response  = [];

        $batchSize = 100;

        $offset    = 0;

        do
        {
            $attempts = $this->repo
                             ->fund_transfer_attempt
                             ->getAttemptsBetweenTimestampsWithStatus(
                                 static::$channel,
                                 Attempt\Status::INITIATED,
                                 null,
                                 null,
                                 $batchSize,
                                 $offset);

            $count = $attempts->count();

            $offset += $batchSize;

            if ($count !== 0)
            {
                $summary = $this->startReconciliation($attempts);

                $this->updateResponse($response, $summary);
            }

        } while ($count === $batchSize);

        return $response;
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
