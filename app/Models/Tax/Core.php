<?php

namespace RZP\Models\Tax;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::TAX_CREATE_REQUEST, $input);

        $tax = (new Entity)->build($input);

        $tax->merchant()->associate($merchant);

        $this->repo->saveOrFail($tax);

        return $tax;
    }

    // TODO:
    // When making changes wrt taxes around items:
    // - consider giving a warning in UI saying this will cascade to items
    // - cascade(set null) delete to items
    // (For both taxes and tax groups)

    public function update(Entity $tax, array $input): Entity
    {
        $this->trace->info(
            TraceCode::TAX_UPDATE_REQUEST,
            [
                'id'    => $tax->getId(),
                'input' => $input,
            ]);

        $tax->edit($input);

        $this->repo->saveOrFail($tax);

        return $tax;
    }

    public function delete(Entity $tax)
    {
        $this->trace->info(
            TraceCode::TAX_DELETE_REQUEST,
            [
                'id' => $tax->getId(),
            ]);

        //
        // This should be inside a transaction, as we are also modifying
        // child entities here. Keeping it inside a transaction makes it atomic.
        //
        return $this->repo->transaction(function () use ($tax)
        {
            return $this->repo->deleteOrFail($tax);
        });
    }
}
