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

        $adj = (new Adjustment\Entity)->build($input);

        // Workflow
        $this->app['workflow']
             ->setEntityAndId($adj->getEntity(), $merchant->getId())
             ->handle((new \stdClass), $adj);

        return $this->repo->transaction(function() use ($input, $adj, $merchant)
            {
                $adj = $this->createAdjInTransaction($input, $adj, $merchant);

                $this->trace->info(
                    TraceCode::ADJUSTMENT_CREATE_SUCCESS,
                    $adj->toArrayPublic());

                return $adj;
            });
    }

    protected function createAdjInTransaction($input, $adj, $merchant)
    {
        $updateEscrow = true;

        if (isset($input['update_escrow']))
        {
            if ($input['update_escrow'] === '1')
                $updateEscrow = true;
            else if ($input['update_escrow'] === '0')
                $updateEscrow = false;
            else
                throw new BadRequestValidationFailureException(
                    'update_escrow field shoudl be boolean', 'update_escrow');

            unset($input['update_escrow']);
        }

        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj, $updateEscrow);

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($adj);

        return $adj;
    }
}
