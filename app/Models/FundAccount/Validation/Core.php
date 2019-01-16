<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount;

class Core extends Base\Core
{
    public function create(array $input): E
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_ASYNC_REQUEST, ['input' => $input]);

        //TODO: Add Metrics
        $validation = $this->createValidationEntity($input, function ($fundAccountValidation) {
            $processor = Processor\Factory::get($fundAccountValidation);

            $processor->preProcessValidation();
        });

        return $validation;
    }

    protected function buildValidationEntity(array $input): E
    {
        $validation = new Entity;

        $validation->build($input);

        $validation->merchant()->associate($this->merchant);

        //TODO: Move this to factory when new account types are added.
        if ($validation->getAmount() === null)
        {
            $validation->setAmount(100);
        }

        return $validation;
    }

    protected function createOrGetFundAccount($input): FundAccount\Entity
    {
        if (isset($input['fund_account']['id']) === true)
        {
            //TODO: try catch and throw right error with right field: PR 2.5
            return $this->repo->fund_account->findByPublicIdAndMerchant($input['fund_account']['id'], $this->merchant);
        }
        else
        {
            return (new FundAccount\Core())->create($input['fund_account'], $this->merchant);
        }
    }

    protected function createValidationEntity(array $input, callable $callback): E
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_SYNC_REQUEST, ['input' => $input]);

        //TODO: Add Metrics
        $validation = $this->buildValidationEntity($input);

        return $this->repo->transaction(function () use ($input, $validation, $callback)
        {
            $fundAccount = $this->createOrGetFundAccount($input);

            $validation->associateFundAccount($fundAccount);

            //TODO: Add Fee validation here: PR: 3

            $this->repo->saveOrFail($validation);

            call_user_func($callback, $validation);

            return $validation;
        });
    }
}
