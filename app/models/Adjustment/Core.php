<?php

namespace Models\Adjustment;

use Models\Base;
use Models\Adjustment;
use Models\Settlement;
use Models\Transaction;

class Core extends Base\Core
{
    public function createAdjustment($input, $merchant)
    {
        $updateEscrow = true;

        if (isset($input['update_escrow']))
        {
            if ($input['update_escrow'] === '1')
                $updateEscrow = true;
            else if ($input['update_escrow'] === '0')
                $updateEscrow = false;
            else
                throw new Exception\BadRequestValidationFailureException(
                    'update_escrow field shoudl be boolean', 'update_escrow');

            unset($input['update_escrow']);
        }

        $adj = (new Adjustment\Entity)->build($input);
        $adj->setChannel(Settlement\Channel::KOTAK);

        $adj->merchant()->associate($merchant);

        $adjRepo = new Adjustment\Repository;
        $adjRepo->saveOrFail($adj);

        $txn = (new Transaction\Core)->createFromAdjustment($adj, $updateEscrow);

        (new Transaction\Repository)->saveOrFail($txn);
        $adjRepo->saveOrFail($adj);

        return $adj;
    }
}