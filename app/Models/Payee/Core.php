<?php

namespace RZP\Models\Payee;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

/**
 * Class Core
 *
 * @package RZP\Models\Payee
 */
class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::PAYEE_CREATE_REQUEST, ['input' => $input]);

        $payee = (new Entity)->build($input);

        $payee->merchant()->associate($merchant);

        $this->repo->saveOrFail($payee);

        return $payee;
    }

    public function update(Entity $payee, array $input): Entity
    {
        $this->trace->info(
            TraceCode::PAYEE_UPDATE_REQUEST,
            [
                'id'    => $payee->getId(),
                'input' => $input,
            ]);

        $payee->edit($input);

        $this->repo->saveOrFail($payee);

        return $payee;
    }

    public function delete(Entity $payee)
    {
        $this->trace->info(TraceCode::PAYEE_DELETE_REQUEST, ['id' => $payee->getId()]);

        return $this->repo->transaction(function () use ($payee)
        {
            return $this->repo->deleteOrFail($payee);
        });
    }
}
