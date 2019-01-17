<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Transaction;
use RZP\Models\FundAccount;
use RZP\Models\Pricing\Fee;
use RZP\Models\FundTransfer\Attempt as AttemptStatus;

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

        $validation->generateId();

        return $validation;
    }

    protected function createOrGetFundAccount(array $input, Merchant\Entity $merchant): FundAccount\Entity
    {
        try
        {
            if (isset($input['fund_account']['id']) === true)
            {
                return $this->repo->fund_account->findByPublicIdAndMerchant($input['fund_account']['id'], $merchant);
            }

            return (new FundAccount\Core())->create($input['fund_account'], $merchant);
        }
        catch (Exception\BaseException $e)
        {
            $e->appendFieldToError('fund_account');

            throw $e;
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

            $fundAccValidationTxnProcessor = (new Transaction\Processor\FundAccountValidation($validation));

            list ($txn, $feeSplit) = $fundAccValidationTxnProcessor->createTransaction();

            (new Transaction\Core)->saveFeeDetails($txn, $feeSplit);

            $this->repo->saveOrFail($txn);

            $validation->setFees($txn->getFee());
            $validation->setTax($txn->getTax());

            $this->repo->saveOrFail($validation);

            call_user_func($callback, $validation);

            return $validation;
        });
    }

    /**
     * Updates validation entity status after FTA recon
     *
     * @param Entity $validation
     * @param string $ftaStatus
     * @param string|null $ftaFailureReason
     */
    public function updateStatusAfterFtaRecon(Entity $validation, string $ftaStatus, string $ftaFailureReason = null)
    {
        $validation->setStatus(Status::COMPLETED);

        switch ($ftaStatus)
        {
            case AttemptStatus::PROCESSED:
                $validation->setAccountStatus(Status::ACTIVE);
                $this->repo->saveOrFail($validation);
                break;

            case AttemptStatus::FAILED:
                $validation->setAccountStatus(Status::INVALID);
                $this->repo->saveOrFail($validation);
                break;

            case AttemptStatus::CREATED:
                break;

            case AttemptStatus::INITIATED:
                break;

            default:
                $this->trace->error(
                    TraceCode::UNKNOWN_FTA_STATUS_SENT_TO_REFUND,
                    [
                        'validation_id'      => $validation->getId(),
                        'fta_status'         => $ftaStatus,
                        'fta_failure_reason' => $ftaFailureReason,
                    ]);
        }

        // TODO: Send a webhook here
    }
}
