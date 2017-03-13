<?php

namespace RZP\Models\Settlement;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Models\Report\BasicEntityReport;
use RZP\Constants\Entity as E;
use RZP\Models\Settlement;
use RZP\Models\Settlement\Icici;
use RZP\Models\Settlement\Kotak;
use RZP\Models\Transaction;
use RZP\Exception;

class Service extends Base\Service
{
    public function initiateSettlements($input, $channel = null)
    {
        $data = (new Settlement\Processor)->process($input, $channel);

        return $data;
    }

    public function generateSettlementFile($input)
    {
        (new Settlement\Validator)->validateInput('batch_fetch', $input);

        $batchSettlementId = $input['batch_settlement_id'];

        $setls = $this->repo->settlement->getSettlementsByBatchSettlementId($batchSettlementId);

        $urls = (new Kotak\Service)->generateSettlementFile($setls);

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
