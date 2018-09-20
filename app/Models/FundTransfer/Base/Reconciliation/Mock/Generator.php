<?php

namespace RZP\Models\FundTransfer\Base\Reconciliation\Mock;

use App;
use Excel;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Settlement;
use RZP\Constants\Timezone;
use RZP\Models\FundTransfer;
use RZP\Models\Base\Core as Base;
use RZP\Models\FileStore\Accessor;
use RZP\Models\FundTransfer\Kotak\FileHandlerTrait;

/**
 * This class is used to handle generation of settlement reconciliation
 * files for running tests and in test mode
 */
class Generator extends Base
{
    use FileHandlerTrait;

    protected $generateInternalFailure       = false;

    protected $generateFailedReconciliations = false;

    protected $generateReturnSettledReconciliation = false;

    public function reconcileSettlements(array $input)
    {
        $this->validateRequest();

        list($startTimestamp, $endTimestamp) = $this->getTimestamps($input);

        $nonReconciledAttempts = $this->repo
                                      ->fund_transfer_attempt
                                      ->getAttemptsBetweenTimestamps(
                                          FundTransfer\Attempt\Status::PENDING_RECONCILIATION,
                                          static::CHANNEL,
                                          $startTimestamp,
                                          $endTimestamp);

        // get batch id of all above attempts
        $batchIds = $nonReconciledAttempts->pluck(FundTransfer\Attempt\Entity::BATCH_FUND_TRANSFER_ID)
                                          ->toArray();

        // non-reconciled batches
        $nonReconciledBatches = $this->repo->batch_fund_transfer->findManyByPublicIds($batchIds);

        // for above batch ids, get the txt file ids
        $setlFileIds = $nonReconciledBatches->pluck(FundTransfer\Batch\Entity::TXT_FILE_ID)
                                            ->toArray();

        // read one txt file from s3 at a time and generate recon file
        $response = [];

        foreach ($setlFileIds as $fileId)
        {
            $fileAccessor = (new Accessor)->id($fileId);

            $filePath     = $fileAccessor->getFile();

            $file         = new UploadedFile($filePath, basename($filePath));

            $reconFile    = $this->generateReconcileFile(['file' => $file] + $input);

            $file         = new UploadedFile($reconFile, basename($reconFile));

            $data         = (new Settlement\Service)->reconcileH2HSettlements(
                                ['file' => $file],
                                static::CHANNEL);

            $response[]   = $data;
        }

        return $response;
    }

    /**
     * Allowed only if the mode is test.
     *
     * @throws Exception\LogicException
     */
    protected function validateRequest()
    {
        if ($this->mode !== 'test')
        {
            throw new Exception\LogicException('Only test mode allowed');
        }
    }

    protected function initRequestParams(array $input)
    {
        $this->generateInternalFailure       = ((isset($input['internal_failure']) === true) and
                                                ($input['internal_failure'] === '1'));

        $this->generateFailedReconciliations = ((isset($input['failed_recons']) === true) and
                                                ($input['failed_recons'] === '1'));

        $this->generateReturnSettledReconciliation= ((isset($input['return_settled']) === true) and
                                                     ($input['return_settled'] === '1'));
    }

    /**
     * Gives start and end timestamp to fetch unprocessed settlement files created between them.
     *
     * @param array $input
     *
     * @return array
     */
    protected function getTimestamps(array $input): array
    {
        if (isset($input['on']) === true)
        {
            $timestamp = Carbon::createFromFormat('Y-m-d', $input['on'], Timezone::IST);
        }
        else
        {
            $timestamp = Carbon::today(Timezone::IST);
        }

        $startTimestamp = $timestamp->startOfDay()
                                    ->getTimestamp();

        $endTimestamp   = $timestamp->endOfDay()
                                    ->getTimestamp();

        return [$startTimestamp, $endTimestamp];
    }
}
