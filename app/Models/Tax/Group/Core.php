<?php

namespace RZP\Models\Tax\Group;

use RZP\Models\Base;
use RZP\Models\Tax;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::TAX_GROUP_CREATE_REQUEST, $input);

        $group = (new Entity)->build($input);

        // Id generation is needed for relationship associations in
        // processTaxIdsOfInput() method.
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

        //
        // This should be inside a transaction, as we are also modifying
        // child entities here. Keeping it inside a transaction makes it atomic.
        //
        return $this->repo->transaction(function () use ($group)
        {
            return $this->repo->tax_group->deleteOrFail($group);
        });
    }

    /**
     * Process tax_ids array provided as part of input.
     *
     * @param Entity $group
     * @param array $input
     *
     * @return
     */
    protected function processTaxIdsOfInput(Entity $group, array $input)
    {
        if (array_key_exists(Entity::TAX_IDS, $input) === false)
        {
            return;
        }

        $inputTaxIds = $input[Entity::TAX_IDS] ?? [];

        Tax\Entity::verifyIdAndStripSignMultiple($inputTaxIds);

        $this->repo->tax->validateExists($inputTaxIds);

        $group->taxes()->sync($inputTaxIds);
    }
}
