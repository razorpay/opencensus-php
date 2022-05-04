<?php

namespace RZP\Models\Settlement\Details;

use Razorpay\Trace\Logger as Trace;
use RZP\Models\Base;
use RZP\Models\Settlement;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function addSettlementDetailsForOldTxns($input)
    {
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $setls = $this->repo->settlement->getFewSettlementsWithNoCorrespondingSettlementDetails();

        foreach ($setls as $setl)
        {
            try
            {
                $merchant = $setl->merchant;

                $setlDetails = $this->repo->settlement_details->getSettlementDetails($setl->getId(), $merchant);

                if($setlDetails->count() === 0)
                {
                    (new Settlement\Merchant($merchant, $setl->getChannel(), $this->repo))
                        ->createSettlementDetails($setl);

                    $processed++;
                }
                else
                {
                    $skipped++;
                }
            }
            catch (\Exception $ex)
            {
                $failed++;
            }
        }

        $data = array(
            'skipped'   => $skipped,
            'processed' => $processed,
            'failed'    => $failed,
        );

        return $data;
    }

    public function getSettlementDetails($id, $merchant)
    {
        try {
            if ($this->app['basicauth']->isOptimiserDashboardRequest() === true)
            {
                return $this->getOptimiserSettlementDetails($id);
            }
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::WARNING,
                TraceCode::FETCH_OPTIMISER_SETTLEMENT_DETAILS_FAILED,
                [
                    'settlement_id' => $id,
                ]);
        }

        $setlDetails = $this->repo->settlement_details
            ->getSettlementDetails($id, $merchant)
            ->toArrayPublic();

        $hasAggregatedFeeAndTax = false;

        $componentFeeAndTax = $this->handleComponentFeeAndTax($setlDetails, $hasAggregatedFeeAndTax);

        foreach ($setlDetails['items'] as &$details)
        {
            $componentKey = $details[Entity::COMPONENT];

            if($componentKey === Component::FEE || $componentKey === Component::TAX)
            {
                continue;
            }

            if(array_key_exists($componentKey, $componentFeeAndTax) === true)
            {
                $details[Component::FEE] =
                    (array_key_exists(Component::FEE, $componentFeeAndTax[$componentKey]) === true) ?
                        $componentFeeAndTax[$componentKey][Component::FEE] : 0;

                $details[Component::TAX] =
                    (array_key_exists(Component::TAX, $componentFeeAndTax[$componentKey]) === true) ?
                        $componentFeeAndTax[$componentKey][Component::TAX] : 0;
            }
            else if($hasAggregatedFeeAndTax == false)
            {
                $details[Component::FEE] = 0;
                $details[Component::TAX] = 0;
            }
        }

        return [
            'setl_details'            => $setlDetails,
            'has_aggregated_fee_tax'  => $hasAggregatedFeeAndTax
        ];
    }

    public function getOptimiserSettlementDetails($id)
    {
        $fetchInput = [
            'id' => $id,
            'entity_name' => 'settlement',
        ];

        $settlement = app('settlements_dashboard')->fetch($fetchInput);

        $details = json_decode($settlement['entity']['details'], true);

        $setlDetails = [
            'entity'    => 'collection',
            'count'     => 0,
            'items'     => [],
        ];

        foreach ($details as  $component => $detail )
        {
            $setlDetail = [];

            if ($component === 'external')
            {
                $setlDetail['component'] = 'unreconciled';
            }
            else
            {
                $setlDetail['component'] = $component;
            }

            $setlDetail['count'] = $detail['count'];
            $setlDetail['fee'] = $detail['fee'];
            $setlDetail['tax'] = $detail['tax'];

            if( $detail['amount'] < 0)
            {
                $setlDetail['type'] = 'debit';
                $setlDetail['amount'] = -1* ($detail['amount'] + $detail['fee'] + $detail['tax']);
            }
            else
            {
                $setlDetail['type'] = 'credit';
                $setlDetail['amount'] = $detail['amount'] + $detail['fee'] + $detail['tax'];
            }

            array_push($setlDetails['items'], $setlDetail);
            $setlDetails['count']++;
        }

        return [
            'setl_details'            => $setlDetails,
            'has_aggregated_fee_tax'  => false
        ];
    }

    protected function handleComponentFeeAndTax(array &$setlDetails, bool &$hasAggregatedFeeAndTax) : array
    {
        $componentFeeAndTax = [];

        $components = &$setlDetails['items'];

        foreach ($components as $index => $component)
        {
            $componentKey = $component[Entity::COMPONENT];

            if($componentKey === Component::FEE || $componentKey === Component::TAX)
            {
                $hasAggregatedFeeAndTax = true;

                continue;
            }

            $keys = explode("_", $componentKey);

            $lastKey = array_pop($keys);

            if($lastKey === Component::FEE || $lastKey === Component::TAX)
            {
                $key = implode("_", $keys);

                $componentFeeAndTax[$key][$lastKey] =
                    ($component[Entity::TYPE] ===  'credit') ? -1 * $component[Entity::AMOUNT] : $component[Entity::AMOUNT];

                unset($components[$index]);
            }
        }

        $components = array_values($components);

        $setlDetails['count'] = count($components);

        return $componentFeeAndTax;
    }
}
