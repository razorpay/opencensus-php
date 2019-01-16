<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount;

class Core extends Base\Core
{
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_REQUEST, [
            'input' => $input
        ]);

        //TODO: Add Metrics
        $validation = $this->createValidationEntity($input, $merchant, function ($fundAccountValidation) {
            $processor = Processor\Factory::get($fundAccountValidation);

            $processor->preProcessValidation();
        });

        return $validation;
    }

    protected function buildValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = new Entity;

        $validation->build($input);

        $validation->merchant()->associate($merchant);

        //TODO: Move this to factory when new account types are added.
        if ($validation->getAmount() === null)
        {
            $validation->setAmount(100);
        }

        return $validation;
    }

    protected function createOrGetFundAccount(array $input, Merchant\Entity $merchant): FundAccount\Entity
    {
        if (isset($input['fund_account']['id']) === true)
        {
            //TODO: try catch and throw right error with right field: PR 2.5
            return $this->repo->fund_account->findByPublicIdAndMerchant($input['fund_account']['id'], $merchant);
        }
        else
        {
            return (new FundAccount\Core())->create($input['fund_account'], $merchant);
        }
    }

    protected function createValidationEntity(array $input, Merchant\Entity $merchant, callable $callback): Entity
    {
        //TODO: Add Metrics
        $validation = $this->buildValidationEntity($input, $merchant);

        return $this->repo->transaction(function () use ($input, $validation, $callback, $merchant)
        {
            $fundAccount = $this->createOrGetFundAccount($input, $merchant);

            $validation->associateFundAccount($fundAccount);

            //TODO: Add Fee validation here: PR: 3

            $this->repo->saveOrFail($validation);

            call_user_func($callback, $validation);

            return $validation;
        });
    }
}
