<?php


namespace RZP\Models\BankingAccount\Activation\Detail;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array $input
     *
     * @return Entity
     */
    public function create(array $input): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_DETAIL_CREATE,
            [
                'input' => $input
            ]
        );

        $activationDetail = new Entity;

        $activationDetail->build($input);

        (new Validator())->validateAccountTypeForChannel($activationDetail->bankingAccount, $input);

        $this->repo->saveOrFail($activationDetail);

        return $activationDetail;
    }

    public function update(Entity $activationDetail, array $input): Entity
    {
        $this->trace->info(
            TraceCode::BANKING_ACCOUNT_ACTIVATION_DETAIL_UPDATE_REQUEST,
            [
                'banking_account_id'    => $activationDetail->getBankingAccountId(),
                'input' => $input,
            ]);

        $validator = new Validator;

        $validator->validateInput('edit', $input);

        $validator->validateAccountTypeForChannel($activationDetail->bankingAccount, $input);

        $activationDetail->edit($input);

        $this->repo->saveOrFail($activationDetail);

        return $activationDetail;
    }
}
