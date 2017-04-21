<?php

namespace RZP\Models\Tax\Group;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Tax;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::TAX_GROUP_CREATE_REQUEST, $input);

        $group = (new Entity)->build($input);

        // Id generation is needed for relationship associations in next lines.
        $group->generateId();

        $group->merchant()->associate($merchant);

        $this->processTaxIdsOfInput($group, $input);

        $this->repo->saveOrFail($group);

        return $group;
    }

    public function update(Entity $group, array $input): Entity
    {
        $this->trace->info(
            TraceCode::TAX_GROUP_UPDATE_REQUEST,
            [
                'id'    => $group->getId(),
                'input' => $input,
            ]);

        $group->edit($input);

        $this->processTaxIdsOfInput($group, $input);

        $this->repo->saveOrFail($group);

        return $group;
    }

    public function delete(Entity $group)
    {
        $this->trace->info(
            TraceCode::TAX_GROUP_DELETE_REQUEST,
            [
                'id' => $group->getId(),
            ]);

        return $this->repo->tax_group->deleteOrFail($group);
    }

    // Private methods

    /**
     * Process tax_ids array provided as part of input.
     *
     * @param Entity $group
     * @param array $input
     *
     * @return
     */
    private function processTaxIdsOfInput(Entity $group, array $input)
    {
        $inputTaxIds = $input[Entity::TAX_IDS] ?? [];

        Tax\Entity::verifyIdAndStripSignMultiple($inputTaxIds);

        $group->taxes()->sync($inputTaxIds);

        // TODO:
        // - Softdeletes on n..n relationship is not working?
    }
}
