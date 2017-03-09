<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Report\BasicEntityReport;
use RZP\Constants\Entity as E;
use RZP\Models\Settlement;
use RZP\Models\FundTransfer\Icici;
use RZP\Models\FundTransfer\Kotak;
use RZP\Models\Transaction;
use RZP\Exception;

class Service extends Base\Service
{
    public function initiateSettlements($input, $channel = null)
    {
        $settler = new Settler();

        return $settler->settle($input, $channel);
    }

    public function initiateSettlementsV2($input, $channel)
    {
        $data = (new Settlement\Processor)->process($input, $channel);

        return $data;
    }

    public function processFailedSettlements($input, $channel)
    {
        $data = (new Settlement\Processor)->processFailedSettlements($input, $channel);

        return $data;
    }

    /** Generates settlement file for a given batch_settlement_id
      * Uses settlement entities / fund_transfer_attempt entities to generate
      * file depending on the created_at timestamp of the batch.
      * If the batch was created before the timestamp (i.e. before rolling out
      * attempt base file generation) settlement entities are used.
      * Else corresponding attempt entities are used.
      */
    public function generateSettlementFile($input)
    {
        (new Settlement\Validator)->validateInput('batch_fetch', $input);

        $batchId = $input['batch_settlement_id'];

        $batch = $this->repo->batch_settlement->findOrFailPublic($batchId);

        $versionV2RolloutTimestamp = 1488326400; // Date 1st March 2017

        $currentTimestamp = Carbon::now('Asia/Kolkata')->timestamp;

        if ($batch->getCreatedAt() < $versionV2RolloutTimestamp)
        {
            $entities = $this->repo->settlement->getSettlementsByBatchSettlementId($batchId);
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

    public function getSettlementTransactions($id)
    {
        $setl = $this->repo->settlement->findByPublicIdAndMerchant($id, $this->merchant);

        $txns = $this->repo->transaction->fetchBySettlement($setl);

        return $txns->toArrayPublic();
    }

    public function reconcileSettlements($input)
    {
        return (new Kotak\Service)->reconcileSettlements($input);
    }

    public function reconcileH2HSettlements($input)
    {
        return (new Kotak\Service)->reconcileH2HSettlements($input);
    }

    public function generateSettlementReconciliation($input)
    {
        return (new Kotak\Service)->generateSettlementReconciliation($input);
    }

    public function returnSettlements($input)
    {
        return (new Kotak\Service)->returnSettlements($input);
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

    public function postInitiateTransfer($input)
    {
        (new Settlement\Validator)->validateInput('nodal_transfer', $input);

        $amount = $input['amount']/100;

        return (new Icici\NodalAccount)->generateTransferFile($amount);
    }

    public function calculatePrevousSettlementFees()
    {
        $settlements = $this->repo->settlement->getSettlementWithFeesAsNullOrZero();

        $totalFees = 0;
        $totalCount = 0;

        foreach ($settlements as $setl)
        {
            $txns = $setl->setlTransactions;

            $fees = 0;

            foreach ($txns as $txn)
            {
                $fees += $txn->getFee();
            }

            $setl->setFees($fees);

            $this->repo->saveOrFail($setl);

            $totalFees += $fees;
            $totalCount += $setl->count();
        }

        return ['fees' => $totalFees, 'count' => $totalCount];
    }

    public function calculatePreviousSettlementServiceTax()
    {
        $settlements = $this->repo->settlement->getSettlementWithServiceTaxNullOrZero();

        $totalServiceTax = 0;
        $totalCount = 0;

        $this->repo->beginTransaction();

        try
        {
            foreach ($settlements as $setl)
            {
                $txns = $setl->setlTransactions;
                $tax = 0;

                foreach ($txns as $txn)
                {
                    $tax += $txn->getServiceTax();
                }

                $setl->setServiceTax($tax);

                $this->repo->saveOrFail($setl);

                $totalServiceTax += $tax;
                $totalCount ++;
            }

            $this->repo->commit();
       }
       catch (\Exception $e)
       {
            $this->repo->rollback();
            throw new Exception\RuntimeException(
                        'Failed generating Service Tax',
                       $e->getTrace());
       }

        return ['tax' => $totalServiceTax, 'settlement_count' => $totalCount];

    }
}
