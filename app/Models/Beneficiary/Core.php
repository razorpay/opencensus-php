<?php

namespace RZP\Models\Beneficiary;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

/**
 * Class Core
 *
 * @package RZP\Models\Beneficiary
 */
class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BENEFICIARY_CREATE_REQUEST, ['input' => $input]);

        $beneficiary = (new Entity)->build($input);

        $beneficiary->merchant()->associate($merchant);

        $this->repo->saveOrFail($beneficiary);

        return $beneficiary;
    }

    public function update(Entity $beneficiary, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BENEFICIARY_UPDATE_REQUEST,
            [
                'id'    => $beneficiary->getId(),
                'input' => $input,
            ]);

        $beneficiary->edit($input);

        $this->repo->saveOrFail($beneficiary);

        return $beneficiary;
    }

    public function delete(Entity $beneficiary)
    {
        $this->trace->info(TraceCode::BENEFICIARY_DELETE_REQUEST, ['id' => $beneficiary->getId()]);

        return $this->repo->deleteOrFail($beneficiary);
    }
}
