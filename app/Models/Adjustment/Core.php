<?php

namespace RZP\Models\Adjustment;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Base;
use RZP\Models\Adjustment;
use RZP\Models\Settlement;
use RZP\Models\Transaction;

class Core extends Base\Core
{
    public function createAdjustment($input, $merchant)
    {
        return $this->repo->transaction(function() use ($input, $merchant)
            {
                return $this->createAdjInTransaction($input, $merchant);
            });
    }

    protected function createAdjInTransaction($input, $merchant)
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

        $adj = (new Adjustment\Entity)->build($input);
        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $this->repo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj, $updateEscrow);

        $this->repo->saveOrFail($txn);
        $this->repo->saveOrFail($adj);

        return $adj;
    }
}