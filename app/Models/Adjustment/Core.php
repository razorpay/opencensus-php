<?php

namespace RZP\Models\Adjustment;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createAdjustment(array $input, $merchant)
    {
        $traceData = [
            'input'    => $input,
            'merchant' => $merchant->getId(),
        ];

        $this->trace->info(
            TraceCode::ADJUSTMENT_CREATE_REQUEST, $traceData);

        $updateEscrow = true;

        if (isset($input['update_escrow']))
        {
            if ($input['update_escrow'] === '1')
                $updateEscrow = true;
            else if ($input['update_escrow'] === '0')
                $updateEscrow = false;
            else
                throw new BadRequestValidationFailureException(
                    'update_escrow field should be boolean', 'update_escrow');

            unset($input['update_escrow']);
        }

        $adj = (new Adjustment\Entity)->build($input);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        return $this->repo->transaction(function() use ($adj, $merchant, $updateEscrow)
            {
                $adj = $this->createAdjInTransaction($adj, $merchant, $updateEscrow);

                $this->trace->info(
                    TraceCode::ADJUSTMENT_CREATE_SUCCESS,
                    $adj->toArrayPublic());

                return $adj;
            });
    }

    protected function createAdjInTransaction($adj, $merchant, $updateEscrow)
    {
        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj, $updateEscrow);

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($adj);

        return $adj;
    }
}
