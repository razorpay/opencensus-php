<?php

namespace RZP\Models\FundAccount\Validation;

use Carbon\Carbon;
use RZP\Constants\Timezone;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\FundAccount;
use RZP\Models\Pricing\Fee;
use RZP\Models\FundTransfer\Attempt;

class Core extends Base\Core
{

    protected $fundAccountCore;
    private $mutex;

    public function __construct()
    {
        parent::__construct();

        $this->mutex = $this->app['api.mutex'];

        $this->fundAccountCore = new FundAccount\Core();
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     * @throws \Throwable
     */
    public function create(array $input, Merchant\Entity $merchant): Entity
    {
        $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_REQUEST, [
            'input' => $input
        ]);

        try
        {
            $fundAccountValidation = $this->createValidationEntity($input, $merchant);

            $processor = Processor\Factory::get($fundAccountValidation);

            $processor->preProcessValidation();

            (new Metric)->pushCreatedMetrics();
        }
        catch (\Throwable $e)
        {
            (new Metric)->pushExceptionMetrics($e, Metric::FUND_ACCOUNT_VALIDATION_FAILED);

            throw $e;
        }

        return $fundAccountValidation;
    }

    public function retry(array $input): array
    {
        (new Validator())->validateInput('retry', $input);

        $favIds = $input[Entity::FUND_ACCOUNT_VALIDATION_IDS];

        $processed = [];
        $failed    = [];

        foreach ($favIds as $favId)
        {
            try
            {
                $this->retryFundAccountValidation($favId);

                $processed[] = $favId;
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Logger::ERROR,
                    TraceCode::FUND_ACCOUNT_VALIDATION_RETRY_FAILED,
                    [
                        'fund_account_validation_id' =>  $favId
                    ]);

                $failed[] = $favId;
            }
        }

        return [
            'processed'         => $processed,
            'failed'            => $failed,
        ];
    }

    public function retryAllFundAccountValidations(array $input): array
    {
        $count = $input['count'] ?? 200;

        $delay = 300;

        $currentTimestamp = Carbon::now(Timezone::IST)->subSeconds($delay)->getTimestamp();

        $fund_account_validation_ids = $this->repo->fund_account_validation->getFundAccountValidationsToRetry($currentTimestamp, $count);

        return $this->retry([Entity::FUND_ACCOUNT_VALIDATION_IDS => $fund_account_validation_ids]);
    }

    /**
     * @param $favId
     * @return bool
     */
    protected function retryFundAccountValidation($favId): bool
    {
        return $this->mutex->acquireAndRelease(
            $favId,
            function () use ($favId)
            {
                // We are fetching entity inside the transaction because it could have been updated by another such process.
                $fundAccountValidation = $this->repo->fund_account_validation->findOrFail($favId);

                $processor = Processor\Factory::get($fundAccountValidation);

                $processor->validateRetry();

                $attempt = $fundAccountValidation->getAttempts() + 1;

                $processor->preProcessValidation();

                $fundAccountValidation->setAttempts($attempt);

                $fundAccountValidation->setRetryAt(null);

                $this->repo->saveOrFail($fundAccountValidation);

                return true;
            },
            18000,
            ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_RETRY_IN_PROGRESS);
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    protected function buildValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = new Entity;

        $validation->build($input);

        $validation->merchant()->associate($merchant);

        $this->associateBalance($validation, $input);

        return $validation;
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return FundAccount\Entity
     * @throws Exception\BaseException
     */
    protected function createOrGetFundAccount(array $input, Merchant\Entity $merchant): FundAccount\Entity
    {
        assertTrue(isset($input['fund_account']) === true);

        try
        {
            if (empty($input['fund_account']['id']) === false)
            {
                return $this->fundAccountCore->findByPublicIdAndMerchant($input['fund_account']['id'], $merchant);
            }

            return $this->fundAccountCore->create($input['fund_account'], $merchant);
        }
        catch (Exception\BaseException $e)
        {
            $e->appendFieldToError('fund_account');

            throw $e;
        }
    }

    /**
     * @param array $input
     * @param Merchant\Entity $merchant
     * @return Entity
     */
    protected function createValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = $this->buildValidationEntity($input, $merchant);

        $validation = $this->repo->transaction(function () use ($input, $validation, $merchant)
        {
            $fundAccount = $this->createOrGetFundAccount($input, $merchant);

            $validation->associateFundAccount($fundAccount);

            $processor = Processor\Factory::get($validation);

            $processor->setDefaultValuesForValidation();

            // We are saving here because when when creating transaction,
            // it is assumed that source already exist.
            $this->repo->saveOrFail($validation);

            $this->verifyFeesLessThanApplicableBalance($validation, $merchant);

            // Transaction might fail because of concurrent request verifying and changing balance at the same time.
            try
            {
                $txn = $processor->createTransaction();
            }
            catch (Exception\LogicException $e)
            {
                if ($e->getMessage() === 'Something very wrong is happening! Balance is going negative')
                {
                    $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_INITIATED, $e->getData());

                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
                        null,
                        null);
                }

                throw $e;
            }

            $validation->setFees($txn->getFee());

            $validation->setTax($txn->getTax());

            $this->repo->saveOrFail($validation);

            return $validation;
        });

        return $validation;
    }

    /**
     * @param Entity $validation
     * @param Attempt\Entity $fta
     * @throws Exception\LogicException, If account Type not supported
     */
    public function updateStatusAfterFtaInitiated(Entity $validation, Attempt\Entity $fta)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_INITIATED, [
            'validation_id' => $validation->getId(),
            'fta_id'        => $fta->getId(),
        ]);

        $processor = Processor\Factory::get($validation);

        $processor->updateStatusAfterFtaInitiated($fta);
    }

    /**
     * Updates validation entity status before FTA recon
     *
     * @param Entity $validation
     * @param array $input
     * @throws Exception\LogicException, If account Type not supported
     */
    public function updateWithDetailsBeforeFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_WITH_DETAILS_BEFORE_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        $processor = Processor\Factory::get($validation);

        $processor->updateWithDetailsBeforeFtaRecon($input);
    }

    /**
     * Updates validation entity status after FTA recon
     *
     * @param Entity $validation
     * @param array $input
     */
    public function updateStatusAfterFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        $processor = Processor\Factory::get($validation);

        $processor->updateStatusAfterFtaRecon($input);
    }

    private function verifyFeesLessThanApplicableBalance(Entity $validation, Merchant\Entity $merchant)
    {
        if ($merchant->getFeeModel() === Merchant\FeeModel::POSTPAID)
        {
            return;
        }

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($validation);

        if ($validation->hasBalance() === true)
        {
            $balance = $this->repo->balance->findByIdAndMerchant($validation->getBalanceId(), $merchant);
        }
        else
        {
            $balance = $this->repo->balance->getMerchantBalance($merchant);
        }

        if ($balance->getFeeCredits() >= $fee)
        {
            return;
        }

        if ($balance->getBalance() >= $fee)
        {
            return;
        }
        throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_FUND_ACCOUNT_VALIDATION_INSUFFICIENT_BALANCE,
                null,
                [
                    'fees'      => $fee,
                    'fee_credits'    =>  $balance->getFeeCredits(),
                    'balance'    =>  $balance->getBalance(),
                ]);
    }

    public function updateEntityWithFtsTransferId(Entity $entity, $ftsTransferId)
    {
        $entity->setFTSTransferId($ftsTransferId);

        $this->repo->saveOrFail($entity);
    }

    protected function associateBalance(Entity $fundAccValidation, array $input)
    {
        $balanceId = $input[Entity::BALANCE_ID] ?? null;

        if (empty($balanceId) === true)
        {
            $balance = $this->merchant->primaryBalance;
        }
        else
        {
            $balance = $this->repo->balance->findByIdAndMerchant($balanceId, $this->merchant);
        }

        $fundAccValidation->balance()->associate($balance);
    }
}
