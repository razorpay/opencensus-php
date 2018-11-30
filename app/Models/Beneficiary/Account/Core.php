<?php

namespace RZP\Models\Beneficiary\Account;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;

/**
 * Class Core
 *
 * @package RZP\Models\Beneficiary\Account
 */
class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::BENEFICIARY_ACCOUNT_CREATE_REQUEST, ['input' => $input]);

        $beneficiary = (new Entity)->build($input);

        $beneficiary->merchant()->associate($merchant);

        $this->repo->saveOrFail($beneficiary);

        return $beneficiary;
    }

    public function update(Entity $beneAccount, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BENEFICIARY_ACCOUNT_UPDATE_REQUEST,
            [
                'id'    => $beneAccount->getId(),
                'input' => $input,
            ]);

        $beneAccount->edit($input);

        $this->repo->saveOrFail($beneAccount);

        return $beneAccount;
    }

    public function delete(Entity $beneAccount)
    {
        $this->trace->info(TraceCode::BENEFICIARY_ACCOUNT_DELETE_REQUEST, ['id' => $beneAccount->getId()]);

        return $this->repo->deleteOrFail($beneAccount);
    }
}
