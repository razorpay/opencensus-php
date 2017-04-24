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

        $group = (new Entity)->build($input);

        $group->merchant()->associate($merchant);

        $this->repo->saveOrFail($group);

        return $group;
    }

    // TODO:
    // When making changes wrt taxes around items:
    // - consider giving a warning in UI saying this will cascade to items
    // - cascade(set null) delete to items
    // (For both taxes and tax groups)

    public function update(Entity $group, array $input): Entity
    {
        $this->trace->info(
            TraceCode::TAX_UPDATE_REQUEST,
            [
                'id'    => $group->getId(),
                'input' => $input,
            ]);

        $group->edit($input);

        $this->repo->saveOrFail($group);

        return $group;
    }

    public function delete(Entity $group)
    {
        $this->trace->info(
            TraceCode::TAX_DELETE_REQUEST,
            [
                'id' => $group->getId(),
            ]);

        return $this->repo->tax_group->deleteOrFail($group);
    }
}
