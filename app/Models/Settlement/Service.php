<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;

use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Base;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Report\Types\BasicEntityReport;
use RZP\Models\Settlement;

class Service extends Base\Service
{
    public function initiateSettlements($input, $channel = null)
    {
        $data = (new Settlement\Processor)->process($input, $channel);

        return $data;
    }

    public function processFailedSettlements($input)
    {
        $data = (new Settlement\Processor)->processFailedSettlements($input);

        return $data;
    }

    /** Generates settlement file for a given batch_fund_transfer_id
      * Uses settlement entities / fund_transfer_attempt entities to generate
      * file depending on the created_at timestamp of the batch.
      * If the batch was created before the timestamp (i.e. before rolling out
      * attempt base file generation) settlement entities are used.
      * Else corresponding attempt entities are used.
      */
    public function generateSettlementFile($input)
    {
        (new Settlement\Validator)->validateInput('batch_fetch', $input);

        $batchId = $input['batch_fund_transfer_id'];

        $batch = $this->repo->batch_fund_transfer->findOrFailPublic($batchId);

        $versionV2RolloutTimestamp = 1489170600; // Date 1st March 2017 IST

        $currentTimestamp = Carbon::now()->getTimestamp();

        if ($batch->getCreatedAt() < $versionV2RolloutTimestamp)
        {
            $entities = $this->repo->settlement->getSettlementsByBatchFundTransferId($batchId);
        }
        else
        {
            $entities = $this->repo
                             ->fund_transfer_attempt
                             ->getFundTransferAttemptsByBatchIdWithRelations(
                                $batchId,
                                ['source', 'source.merchant', 'source.merchant.bankAccount']);
        }

        $urls = (new Kotak\Service)->generateSettlementFile($entities);

        return $urls;
    }

    public function fetch($id)
    {
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        return $setl->toArrayPublic();
    }

    public function editSettlement($id, $input)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $setl = $this->repo->settlement->findOrFailPublic($id);

        if ((isset($input['status'])) and
            ($input['status'] === Status::FAILED))
        {
            if ($setl->isStatusCreated() === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Settlement status is not created. Status: ' . $setl->getStatus());
            }

            $setl->setStatus(Settlement\Status::FAILED);
            $this->repo->saveOrFail($setl);
        }

        return $setl->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $settlements = $this->repo->settlement->fetch($input, $this->merchant->getKey());

        return $settlements->toArrayPublic();
    }

    public function fetchSettlementTransactions($id)
    {
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        $txns = $this->repo->transaction->fetchBySettlement($setl);

        return $txns->toArrayPublic();
    }

    public function reconcileSettlements($input, string $channel)
    {
        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\FileProcessor';

        return (new $reconNamepsace)->process($input);
    }

    public function reconcileH2HSettlements($input, string $channel)
    {
        $reconNamepsace = 'RZP\\Models\\FundTransfer\\' . ucwords($channel). '\\Reconciliation\\FileProcessor';

        return (new $reconNamepsace)->process($input);
    }

    public function reconcileSettlementsInTestMode($input)
    {
        return (new Kotak\ReconciliationGenerator)->reconcileSettlementsInTestMode($input);
    }

    public function generateSettlementReconciliation($input, string $channel)
    {
        $reconGeneratorNamespace = '\\RZP\\Models\FundTransfer\\' . ucfirst($channel) . '\\ReconciliationGenerator';

        $filename = (new $reconGeneratorNamespace)->generateReconcileFile($input);

        return ['setlReconciliationFile' => $filename];
    }

    public function generateSettlementReturn($input)
    {
        return (new Kotak\Service)->generateSettlementReturn($input);
    }

    public function deleteSetlFile($setlFileType)
    {
        (new Kotak\Service)->deleteSetlFile($setlFileType);
    }

    public function getSettlementCombinedReport($input)
    {
        $report = new BasicEntityReport(E::TRANSACTION);

        return $report->getReport($input);
    }

    public function updateChannelForMultipleSettlements($input)
    {
        $this->trace->info(
            TraceCode::SETTLEMENTS_CHANNEL_BULK_UPDATE_REQUEST,
            $input
        );

        $response = (new Core)->updateChannel($input);

        return $response;
    }

    /**
     * Initiates transfer from one Nodal account to another
     */
    public function postInitiateTransfer(array $input): array
    {
        $response = (new Core)->postInitiateTransfer($input);

        return $response;
    }

    /**
     * Add beneficiary from one Nodal account to another
     */
    public function addBeneficiary(string $channel, array $input): array
    {
        $response = (new Core)->addBeneficiary($channel, $input);

        return $response;
    }

    /**
     * Gets account balance of Nodal Account
     *
     * @param string $channel channel for which the balance has to be fetched
     *
     * @return array
     * [
     *  account_number => account_balance,
     * ]
     */
    public function getAccountBalance(string $channel): array
    {
        $channelAttributeKey = 'balance_' . Entity::CHANNEL;

        (new Validator)->validateInput('canFetchBalance', [
            $channelAttributeKey => $channel
        ]);

        $response = (new Core)->getAccountBalance($channel);

        return $response;
    }
}
