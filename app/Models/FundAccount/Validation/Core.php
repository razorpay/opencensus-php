<?php

namespace RZP\Models\FundAccount\Validation;

use RZP\Exception;
use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Trace\Tracer;
use RZP\Services\FTS;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Constants\Product;
use RZP\Jobs\LedgerStatus;
use RZP\Jobs\Transactions;
use RZP\Models\FundAccount;
use RZP\Models\Pricing\Fee;
use RZP\Constants\Timezone;
use RZP\Constants\HyperTrace;
use RZP\Models\Merchant\Balance;
use RZP\Models\Settlement\Channel;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\FundTransfer\Redaction;
use RZP\Exception\GatewayTimeoutException;
use RZP\Models\Transaction\ReconciledType;
use RZP\Constants\Entity as EntityConstant;
use RZP\Models\Transaction\Processor\Ledger;
use RZP\Services\FTS\Constants as FtsConstants;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Balance\Ledger\Core as LedgerCore;
use RZP\Services\FTS\Transfer\RequestFields as FtsRequestFields;
use RZP\Models\Transaction\Processor\Ledger\FundAccountValidation as FavLedgerProcessor;

class Core extends Base\Core
{
    protected $fundAccountCore;
    private $mutex;

    const VALIDATION_UPDATE_MUTEX = "FUND_ACCOUNT_VALIDATION_BEING_UPDATED";

    const VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT = 20;

    const VALIDATION_UPDATE_MUTEX_RETRY_COUNT = 1;

    const FAV_QUEUE_FOR_FTS_MUTEX_LOCK_TIMEOUT = 180;

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

        if ((isset($input['balance_id']) === false) and
            (isset($input['fund_account']['id']) === true))
        {
            Tracer::startSpanWithAttributes(HyperTrace::FAV_REQUEST_WITH_NO_ACCOUNT_NUMBER,
                                            [
                                                'merchant_id' => $merchant->getId()
                                            ]);
        }

        try
        {
            $fundAccountValidation = $this->createValidationEntity($input, $merchant);

            // Skip fts calls for validation in case of ledger failure
            // This will be picked up from async job when ledger status is checked
            if ($fundAccountValidation->getLedgerResponseAwaitedFlag() === false)
            {
                $processor = Processor\Factory::get($fundAccountValidation);
                $processor->preProcessValidation();
            }
        }
        catch (\Throwable $e)
        {
            (new Metric)->pushExceptionMetrics($e, Metric::FUND_ACCOUNT_VALIDATION_FAILED);

            throw $e;
        }

        // Todo: check if pushing this metric is ok in case of ledger reverse shadow failure
        (new Metric)->pushCreatedMetrics($fundAccountValidation->getFundAccountType());

        return $fundAccountValidation;
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
        $this->fundAccountCore->modifyRequestForBackwardCompatibility($input['fund_account']);

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
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    protected function createValidationEntity(array $input, Merchant\Entity $merchant): Entity
    {
        $validation = $this->buildValidationEntity($input, $merchant);

        $fundAccount = $this->createOrGetFundAccount($input, $merchant);

        $validation->associateFundAccount($fundAccount);

        if (self::shouldFavGoThroughLedgerReverseShadowFlow($validation) === true)
        {
            $validation = $this->processFavThroughLedger($validation, $merchant, $input);

            return $validation;
        }

        $validation = $this->repo->transaction(function () use ($input, $validation, $merchant)
        {
            $this->runInputValidations($validation, $input);

            $processor = Processor\Factory::get($validation);

            $processor->setDefaultValuesForValidation();

            $validation->setAttempts(1);

            // We are saving here because when when creating transaction,
            // it is assumed that source already exist.
            $this->repo->saveOrFail($validation);

            $txn = $this->createTransactionIfApplicable($validation, $merchant, $processor);

            if ($txn !== null)
            {
                $validation->setFees($txn->getFee());
                $validation->setTax($txn->getTax());
                $validation->setTransactionId($txn->getId());

                $this->repo->saveOrFail($validation);
            }

            return $validation;
        });

        return $validation;
    }

    public function processFavThroughLedger(Entity $validation, Merchant\Entity $merchant, array $input): Entity
    {
        // Create the entity first, and calculate the pricing changes.
        list($validation, $feesSplit) = $this->repo->transaction(function () use ($input, $validation, $merchant)
        {
            $this->runInputValidations($validation, $input);

            $processor = Processor\Factory::get($validation);

            $processor->setDefaultValuesForValidation();

            $validation->setAttempts(1);

            list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($validation);

            $validation->setFees($fee);
            $validation->setTax($tax);

            $this->repo->saveOrFail($validation);

            return [$validation, $feesSplit];
        });

        try
        {
            $ledgerResponse = (new Ledger\FundAccountValidation())->processValidationAndCreateJournalEntry($validation, [], null, $feesSplit);
        }
        catch (BadRequestException | Exception\IntegrationException $ex)
        {
            $validation->setStatus(Status::FAILED);
            $this->repo->saveOrFail($validation);
            throw $ex;
        }
        catch (\Throwable $ex)
        {
            // the flag is used to skip fts call due to ledger failure
            // the fts calls will be handled later when this fav request gets retried in async
            $validation->setLedgerResponseAwaitedFlag(true);
            // trace and ignore exception as it will be retries in async
            $this->trace->traceException(
                $ex,
                Trace::CRITICAL,
                null,
                [
                    'fav_id'         => $validation->getId(),
                ]
            );
        }

        return $validation;
    }

    public function processFavAfterLedgerStatusCheck($validation, $ledgerResponse, $feesSplit = null)
    {
        $this->trace->info(
            TraceCode::PROCESS_FAV_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'fav_id'            => $validation->getId(),
                'entity_name'       => EntityConstant::FUND_ACCOUNT_VALIDATION,
                'fee_split'         => $feesSplit
            ]);

        $processor = Processor\Factory::get($validation);
        $processor->preProcessValidation();
    }

    public function failFavAfterLedgerStatusCheck($validation)
    {
        $this->trace->info(
            TraceCode::FAIL_FAV_AFTER_LEDGER_STATUS_SUCCESS,
            [
                'fav_id'            => $validation->getId(),
                'entity_name'       => EntityConstant::FUND_ACCOUNT_VALIDATION,
            ]);

        $processor = Processor\Factory::get($validation);
        $processor->markValidationAsFailed();
    }

    public static function shouldFavGoThroughLedgerReverseShadowFlow($validation)
    {
        if (($validation->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true) and
            ($validation->isBalanceTypeBanking() === true) and
            ($validation->getFundAccountType() !== FundAccount\Type::VPA))
        {
            return true;
        }

        return false;
    }

    /**
     * @param Entity $validation
     * @param array  $input
     *
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function runInputValidations(Entity $validation, array $input)
    {
        $type = $validation->fundAccount->account->getEntityName();

        Processor\Factory::validate($type);

        // Extra checks are not required for non banking, create rules are sufficient
        if ($validation->balance->isTypeBanking() === false)
        {
            return;
        }

        $accountType = $validation->fundAccount->getAccountType();

        $validationRuleName = Product::BANKING . '_' . $accountType;

        (new Validator($validation))->validateInput($validationRuleName, $input);
    }

    /**
     * @param Entity          $validation
     * @param Merchant\Entity $merchant
     * @param Processor\Base  $processor
     *
     * @return \RZP\Models\Transaction\Entity|null
     * @throws Exception\BadRequestException
     * @throws Exception\LogicException
     */
    protected function createTransactionIfApplicable(Entity $validation,
                                                     Merchant\Entity $merchant,
                                                     Processor\Base $processor)
    {
        if ($validation->getFundAccountType() === FundAccount\Type::VPA)
        {
            return null;
        }

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

        return $txn;
    }

    /**
     * @param Entity $validation
     * @param Attempt\Entity $fta
     * @throws \Throwable
     */
    public function updateStatusAfterFtaInitiated(Entity $validation, Attempt\Entity $fta)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_INITIATED, [
            'validation_id' => $validation->getId(),
            'fta_id'        => $fta->getId(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $fta)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateStatusAfterFtaInitiated($fta);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * Updates validation entity status before FTA recon
     *
     * @param Entity $validation
     * @param array $input
     * @throws \Throwable
     */
    public function updateWithDetailsBeforeFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_WITH_DETAILS_BEFORE_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $input)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateWithDetailsBeforeFtaRecon($input);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                self::VALIDATION_UPDATE_MUTEX_RETRY_COUNT);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * Updates validation entity status after FTA recon
     *
     * @param Entity $validation
     * @param array $input
     * @throws \Throwable
     */
    public function updateStatusAfterFtaRecon(Entity $validation, array $input)
    {
        $this->trace->info(TraceCode::UPDATE_STATUS_AFTER_FTA_RECON, [
            'input' => $input,
            'validation_status' => $validation->getStatus(),
        ]);

        try
        {
            $this->mutex->acquireAndRelease(
                self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                function () use ($validation, $input)
                {
                    $processor = Processor\Factory::get($validation);

                    $processor->updateStatusAfterFtaRecon($input);

                    return;
                },
                self::VALIDATION_UPDATE_MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                self::VALIDATION_UPDATE_MUTEX_RETRY_COUNT);
        }
        catch (\Throwable $e)
        {
            $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_FTA_HOOK_FAILED, [
                'fav_id' => $validation->getId()
            ]);

            throw $e;
        }
    }

    /**
     * @param Entity          $validation
     * @param Merchant\Entity $merchant
     *
     * @throws Exception\BadRequestException
     */
    private function verifyFeesLessThanApplicableBalance(Entity $validation, Merchant\Entity $merchant)
    {
        if ($merchant->getFeeModel() === Merchant\FeeModel::POSTPAID)
        {
            return;
        }

        list($fee, $tax, $feesSplit) = (new Fee())->calculateMerchantFees($validation);

        if ($validation->hasBalance() === true)
        {
            $balance = $validation->balance;
        }
        else
        {
            $balance = $this->merchant->primaryBalance;
        }

        if (($balance->getFeeCredits() >= $fee) and
            ($balance['type'] === Balance\Type::PRIMARY))
        {
            return;
        }

        if (($balance->isAccountTypeShared() === true) &&
            ($balance->isTypeBanking() === true) &&
            ($merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === true))
        {
            $accountNumber = $balance->getAccountNumber();

            $bankingAccount = $this->repo
                                   ->banking_account
                                   ->findByMerchantAndAccountNumberPublic($this->merchant, $accountNumber);

            $ledgerResponse = (new LedgerCore())->fetchBalanceFromLedger($merchant->getId(), $bankingAccount->getPublicId());
            if ((empty($ledgerResponse) === false) &&
                (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE]) === false) &&
                (empty($ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE]) === false))
            {
                $balanceAmount = (int) $ledgerResponse[LedgerCore::MERCHANT_BALANCE][LedgerCore::BALANCE];
                $balance->setBalance($balanceAmount);
                if ($balance >= $fee)
                {
                    return;
                }
            }
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
        if (empty($ftsTransferId) === false)
        {
            $entity->setFTSTransferId($ftsTransferId);

            $this->repo->saveOrFail($entity);
        }
    }

    protected function associateBalance(Entity $fundAccValidation, array $input)
    {
        $balanceId = $input[Entity::BALANCE_ID] ?? null;

        if (empty($balanceId) === true)
        {
            $balance = $fundAccValidation->merchant->primaryBalance;
        }
        else
        {
            $balance = $this->repo->balance->findByIdAndMerchant($balanceId, $fundAccValidation->merchant);
        }

        $this->blockFAVIfApplicable($balance);

        $fundAccValidation->balance()->associate($balance);
    }

    protected function blockFAVIfApplicable($balance)
    {
        if ($balance->isTypeBanking() !== true)
        {
            return;
        }

        /*
         * Block FAV for merchants on account <> sub account setup. Ref slack thread :
         * https://razorpay.slack.com/archives/CR3K6S6C8/p1669613755735969?thread_ts=1668581579.373769&cid=CR3K6S6C8
         */
        if (($balance->isAccountTypeShared() === true) and
            (($balance->merchant->isSubMerchantOnDirectMasterMerchant() === true) or
             ($balance->merchant->isFeatureEnabled(Feature\Constants::BLOCK_FAV) === true)))
        {
            throw new BadRequestValidationFailureException(
                "Fund account validation not supported for the debit account"
            );
        }

        if ($balance->getChannel() === Channel::YESBANK)
        {
            $config = (new Admin\Service)->getConfigKey(['key' => Admin\ConfigKey::BLOCK_YESBANK_RX_FAV]) ?? false;

            if (boolval($config) === false)
            {
                return;
            }

            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_FAV_NOT_ALLOWED_CURRENTLY,
                null,
                [
                    'channel'       => 'yesbank',
                    'merchant_id'   => $balance->getMerchantId(),
                    'balance_id'    => $balance->getId(),
                    'config'        => $config,
                ]);
        }
    }

    public function getFavByMerchantIdAndFavId(string $favId, string $merchantId)
    {
        $merchant = $this->repo->merchant->findByPublicId($merchantId);

        $entity = $this->repo->fund_account_validation
            ->findByPublicIdAndMerchant($favId, $merchant);

        return $entity->toArrayPublic();
    }

    public function bulkPatchFavAsFailed(array $input): array
    {
        (new Validator())->validateInput('bulk_patch_fav', $input);

        $favIds = $input[Entity::FUND_ACCOUNT_VALIDATION_IDS];

        foreach ($favIds as $i => $favId)
        {
            $favIds[$i] =  Entity::stripSignWithoutValidation($favId);
        }

        $validations = $this->repo->fund_account_validation->getFundAccountValidationsToFail($favIds);

        $recordsReceived = count($favIds);

        if ($recordsReceived !== $validations->count())
        {
            $this->trace->info(TraceCode::FUND_ACCOUNT_VALIDATION_BULK_PATCH_FAILED, [
                'input' => $input
            ]);

            return [
                'processed'         => 0,
                'failed'            => $recordsReceived,
            ];
        }

        $processed = 0;
        foreach ($validations as $validation)
        {
            try
            {
                $this->mutex->acquireAndRelease(
                    self::VALIDATION_UPDATE_MUTEX . $validation->getId(),
                    function () use ($validation)
                    {
                        $processor = Processor\Factory::get($validation);

                        $processor->markValidationAsFailed();

                        return;
                    });
                $processed++;
            }
            catch (\Throwable $e)
            {
                $this->trace->error(TraceCode::FUND_ACCOUNT_VALIDATION_STATUS_CHANGE_FAILED, [
                    'fav_id' => $validation->getId()
                ]);
            }
        }

        return [
            'processed'         => $processed,
            'failed'            => $recordsReceived - $processed,
        ];
    }

    /**
     * @param Entity      $fundAccountValidation
     * @param string|null $status
     * @param array       $ftsSourceAccountInformation
     * Push to ledger sns when Fund Account Validation status is changed.
     * Since ledger keeps different records for all fav states, these events are triggered.
     * Status is sent only in case of reversed. Since FAV doesn't have reversed status, it is sent explicitly
     * For other cases, status is fetch directly from entity.
     *
     * TODO: In case of fav reversal, recon updates FTA to reversed and not FAV. So once FTA gets deprecated, have to check FAV_REVERSAL flow.
     */
    public function processLedgerFav(Entity $fundAccountValidation,
                                     string $status = null,
                                     array $ftsSourceAccountInformation = [])
    {
        // In case env variable ledger.enabled is false, or balance type is not banking, return.
        if (($this->app['config']->get('applications.ledger.enabled') === false) or
            ($fundAccountValidation->isBalanceTypeBanking() === false))
        {
           return;
        }

        // return if reverse shadow is enabled
        if (self::shouldFavGoThroughLedgerReverseShadowFlow($fundAccountValidation) === true)
        {
            return;
        }

        // If the mode is live but the merchant does not have the ledger journal write feature, we return.
        if (($this->isLiveMode()) and
            ($fundAccountValidation->merchant->isFeatureEnabled(Feature\Constants::LEDGER_JOURNAL_WRITES) === false))
        {
            return;
        }

        // Since FAV does not maintain time when it gets processed or failed, defining current time
        // which will be used as time when that particular FAV event is created in ledger.
        $transactorDate = Carbon::now(Timezone::IST)->getTimestamp();

        if ($status === null)
        {
            $status = $fundAccountValidation->getStatus();
        }

        $event = Status::getLedgerEventFromFavStatus($status);

        (new Ledger\FundAccountValidation)->pushTransactionToLedger($fundAccountValidation, $event, $transactorDate, $ftsSourceAccountInformation);
    }

    /**
     * This function does the following-
     * 1. Fetch the FAV entity from the FAV ID.
     * 2. Create the request body as per the API contract.
     * 3. Create a new Services\FTS\Transfer\Client object, and set its $request using setRequest() method call.
     * 4. Invoke Client object's doTransfer() method.
     * 5. Update FAV if there's no exception thrown (doTransfer() will throw exceptions, halting this step)
     * 6. Returns the response if no exception is thrown.
     *
     * @param  $favId string The FAV ID to be processed.
     *
     * @return mixed
     */
    public function sendFAVRequestToFTS(string $favId)
    {
        return $this->mutex->acquireAndRelease(
            'fav_queue_for_fts_mutex_' . $favId,
            function() use ($favId) {
                $this->trace->info(
                    TraceCode::FAV_QUEUE_FOR_FTS_JOB_HANDLER_INIT,
                    [
                        'fav_id' => $favId,
                    ]
                );

                $fav = $this->repo->fund_account_validation->findOrFail($favId);

                $request = $this->createRequestBodyFromFavForFTS($fav);

                $ftsClient = new FTS\Transfer\Client($this->app);

                $ftsClient->setRequest($request);

                return $ftsClient->doTransfer();
            },
            self::FAV_QUEUE_FOR_FTS_MUTEX_LOCK_TIMEOUT
        );
    }

    /**
     * This function creates the request array from an FAV entity.
     *
     * @param $fav Entity The FAV entity
     *
     * @return array[] Consisting of the request body
     */
    protected function createRequestBodyFromFavForFTS($fav)
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_REQUEST_BODY_CREATION_INIT,
            [
                'fav_id' => $fav->getPublicId(),
            ]
        );

        // Create the basic request body
        $request = [
            FtsRequestFields::TRANSFER => [
                FtsRequestFields::SOURCE_ID             => $fav->getId(),
                FtsRequestFields::SOURCE_TYPE           => FtsConstants::FUND_ACCOUNT_VALIDATION,
                FtsRequestFields::AMOUNT                => $fav->getAmount(),
                FtsRequestFields::MERCHANT_ID           => $fav->getMerchantId(),
                FtsRequestFields::TRANSFER_ACCOUNT_TYPE => FtsConstants::BANK_ACCOUNT,
                FtsRequestFields::PURPOSE               => FtsConstants::PENNY_TESTING,
                FtsRequestFields::PREFERRED_MODE        => FtsConstants::MODE_IMPS,
            ],
        ];

        // Add narration to the request body
        $narration = $this->getNarration($fav);

        if (empty($narration) === false)
        {
            $request[FtsRequestFields::TRANSFER][FtsRequestFields::NARRATION] = $narration;
        }

        // - Now fetch the bank account from the fund account associated with the FAV
        // - We are assuming that the associated account is of the type bank account,
        //   hence directly using the account relation
        $bankAccount = $fav->fundAccount->account;

        // Fill the request array further, by nesting bank_account sub-array, using the contents of $bankAccount var
        $request[FtsRequestFields::BANK_ACCOUNT] = [
            FtsRequestFields::ID             => $bankAccount->getId(),
            FtsConstants::IFSC_CODE          => $bankAccount->getIfscCode(),
            FtsRequestFields::ACCOUNT_TYPE   => $bankAccount->getAccountType(),
            FtsRequestFields::ACCOUNT_NUMBER => $bankAccount->getAccountNumber(),
            FtsConstants::BENEFICIARY_NAME   => $bankAccount->getBeneficiaryName(),
        ];

        if (is_null($bankAccount->getAccountType()) === true)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::ACCOUNT_TYPE] = FtsConstants::SAVING;
        }

        // Fill up optional fields in bank_account sub-array

        if (is_null($bankAccount->isVirtual()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsConstants::IS_VIRTUAL_ACCOUNT] = $bankAccount->isVirtual();
        }

        if (empty($bankAccount->getBeneficiaryCity()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_CITY] =
                $bankAccount->getBeneficiaryCity();
        }

        if (empty($bankAccount->getBeneficiaryEmail()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_EMAIL] =
                $bankAccount->getBeneficiaryEmail();
        }

        if (empty($bankAccount->getBeneficiaryState()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_STATE] =
                $bankAccount->getBeneficiaryState();
        }

        if (empty($bankAccount->getBeneficiaryMobile()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_MOBILE] =
                $bankAccount->getBeneficiaryMobile();
        }

        if (empty($bankAccount->getBeneficiaryCountry()) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_COUNTRY] =
                $bankAccount->getBeneficiaryCountry();
        }

        $address = $this->getAddressFromBankAccountEntity($bankAccount);

        if (is_null($address) === false)
        {
            $request[FtsRequestFields::BANK_ACCOUNT][FtsRequestFields::BENEFICIARY_ADDRESS] = $address;
        }

        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_REQUEST_CREATED,
            [
                'fav_id'       => $fav->getPublicId(),
                'request_body' => [
                    FtsRequestFields::TRANSFER     => $request[FtsRequestFields::TRANSFER],
                    FtsRequestFields::BANK_ACCOUNT => ['id' => $request[FtsRequestFields::BANK_ACCOUNT]['id']],
                ],
            ]
        );

        return $request;
    }

    public function setTransferId(string $favId, string $transferId)
    {
        $this->trace->info(
            TraceCode::FAV_QUEUE_FOR_FTS_JOB_TRANSFER_ID_UPDATE_INIT,
            [
                'fav_id'   => $favId,
                'transfer_id' => $transferId,
            ]
        );

        $fav = $this->repo->fund_account_validation->findOrFail($favId);
        $fav->setFTSTransferId($transferId);
        $this->repo->fund_account_validation->saveOrFail($fav);
    }

    protected function getNarration(Entity $fav)
    {
        $merchant = $fav->merchant;

        $merchantBillingLabel = $merchant->getBillingLabel();

        // Remove all characters other than a-z, A-Z, 0-9 and space
        $formattedLabel = preg_replace('/[^a-zA-Z0-9 ]+/', '', $merchantBillingLabel);

        // If formattedLabel is non-empty, pick the first 30 chars, else fallback to 'Razorpay'
        $formattedLabel = ($formattedLabel ? $formattedLabel : 'Razorpay');

        $narration = $formattedLabel . ' Acc Validation';

        $narration = str_limit($narration, 30, '');

        return $narration;
    }

    protected function getAddressFromBankAccountEntity($bankAccount)
    {
        $address = null;

        if (empty($bankAccount->getBeneficiaryAddress1()) === false)
        {
            $address = $bankAccount->getBeneficiaryAddress1();
        }

        if (empty($bankAccount->getBeneficiaryAddress2()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress2();
        }

        if (empty($bankAccount->getBeneficiaryAddress3()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress3();
        }

        if (empty($bankAccount->getBeneficiaryAddress4()) === false)
        {
            $address = $address . ', ' . $bankAccount->getBeneficiaryAddress4();
        }

        return $address;
    }

    public function updateFavWithFtsWebhook(array $input)
    {
        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_INIT,
            [
                'input'     => (new Redaction())->redactData($input)
            ]);

        try
        {
            // Convert status to lowercase if the need be
            if (array_key_exists(Entity::STATUS, $input) === true)
            {
                $input[Entity::STATUS] = strtolower($input[Entity::STATUS]);
            }

            $validator = new Validator;

            $validator->setStrictFalse()->validateInput('fts_status_update', $input);

            $validator->validateStatusInFtsWebhook($input[FtsConstants::STATUS]);

            $extraInfo = $input['extra_info'] ?? [];

            $mapping = [
                Entity::STATUS                    => $input[FtsConstants::STATUS],
                Entity::UTR                       => $input[FtsConstants::UTR],
                FtsConstants::BANK_STATUS_CODE    => $input[FtsConstants::BANK_STATUS_CODE],
                Entity::ID                        => $input[FtsConstants::SOURCE_ID],
                Entity::FTS_TRANSFER_ID           => $input[FtsConstants::FUND_TRANSFER_ID],
                Attempt\Entity::BANK_ACCOUNT_TYPE => $input[Attempt\Entity::BANK_ACCOUNT_TYPE] ?? null,
                Attempt\Entity::SOURCE_ACCOUNT_ID => $input[Attempt\Entity::SOURCE_ACCOUNT_ID] ?? null,
            ] + $extraInfo;

            $this->updateFav($mapping);

            $this->trace->info(
                TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_SUCCESSFUL,
                [
                    'input'     => (new Redaction())->redactData($input)
                ]);

            return [
                'message' => 'FAV source updated successfully',
            ];
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_FAILED,
                [
                    'error' => $e->getMessage()
                ]);

            $this->trace->count(Metric::FAV_UPDATE_FROM_FTS_WEBHOOK_FAILED_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);

            throw $e;
        }
    }

    public function manualUpdateFavToFailedState($favId)
    {
        try
        {
            $extraInfo = [
                Attempt\Constants::BENEFICIARY_NAME => '',
                Attempt\Entity::CMS_REF_NO => '',
                Attempt\Constants::INTERNAL_ERROR => true,
                'ponum' => '',
            ];

            $mapping = [
                           Entity::STATUS                    => Status::FAILED,
                           Entity::UTR                       => null,
                           FtsConstants::BANK_STATUS_CODE    => FtsConstants::STATUS_FAILED,
                           Entity::ID                        => $favId,
                           Entity::FTS_TRANSFER_ID           => null,
                           Attempt\Entity::BANK_ACCOUNT_TYPE => 'CURRENT',
                           Attempt\Entity::SOURCE_ACCOUNT_ID => 51833651,
                       ] + $extraInfo;

            $this->trace->info(
                TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_INIT,
                [
                    'mapping'     => (new Redaction())->redactData($mapping)
                ]);

            $this->updateFav($mapping);

            $this->trace->info(
                TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_CORE_HANDLER_SUCCESSFUL,
                [
                    'input'     => (new Redaction())->redactData($mapping)
                ]);

            return [
                'message' => 'FAV source updated successfully',
            ];
        }
        catch(\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_FAILED,
                [
                    'error' => $e->getMessage()
                ]);

            $this->trace->count(Metric::FAV_UPDATE_FROM_FTS_WEBHOOK_FAILED_COUNT,
                                [
                                    'error' => $e->getMessage()
                                ]);

            throw $e;
        }
    }

    protected function updateFav(array $mapping)
    {
        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_UPDATE_FAV,
            [
                'input'     => (new Redaction())->redactData($mapping),
            ]);

        $fav = $this->repo->fund_account_validation->findOrFail($mapping[Entity::ID]);

        $fav->setFTSTransferId($mapping[Entity::FTS_TRANSFER_ID]);

        $this->updateWithDetailsBeforeFtaRecon($fav, $mapping);

        $this->updateStatusAfterFtaRecon($fav, $mapping);
    }

    public function updateTransactionEntity($source, $reset = false, $reconciledType = ReconciledType::MIS)
    {
        $this->trace->info(
            TraceCode::FAV_UPDATE_FROM_FTS_WEBHOOK_UPDATE_TRANSACTION_INIT,
            [
                'input'     => $source->toArrayPublic(),
            ]);

        // Source entity might update the transaction but because we would have already fetched
        // the transaction from source earlier. Then if we try to access $this->source->transaction now,
        // It will return an old copy. Not the updated transaction. Hence, we reload the relation.
        $source->load(EntityConstant::TRANSACTION);

        $reconciledTime = Carbon::now(Timezone::IST)->timestamp;

        if ($reset === true)
        {
            $reconciledTime = $reconciledType = null;
        }

        $source->transaction->setReconciledAt($reconciledTime);

        $source->transaction->setReconciledType($reconciledType);

        $source->transaction->saveOrFail();
    }

    public function createTransactionInLedgerReverseShadowFlow(string $entityId, array $ledgerResponse, PublicCollection $feeSplit = null)
    {
        $fav = $this->repo->fund_account_validation->find($entityId);

        if(self::shouldFavGoThroughLedgerReverseShadowFlow($fav) === false)
        {
            throw new Exception\LogicException('Merchant does not have the ledger reverse shadow feature flag enabled'
                ,ErrorCode::BAD_REQUEST_MERCHANT_NOT_ON_LEDGER_REVERSE_SHADOW,
            ['merchant_id' => $fav->getMerchantId()]);
        }

        if ($fav->getFundAccountType() === FundAccount\Type::VPA)
        {
            $this->trace->info(
                TraceCode::LEDGER_TRANSACTIONS_QUEUE_VPA_BASED_FAV_NOT_ALLOWED,
                [
                    'fav_id' => $fav->getId(),
                ]
            );

            return [
                'entity' => $fav->getPublicId(),
                'txn'    => null,
            ];
        }

        $processor = Processor\Factory::get($fav);

        $txn = $this->mutex->acquireAndRelease('fav_'.$entityId,
            function () use ($fav, $ledgerResponse, $processor, $feeSplit)
            {
                $fav->reload();

                return $this->repo->transaction(function () use ($ledgerResponse, $fav, $processor, $feeSplit)
                {
                   $txn = $processor->createTransactionForLedger($ledgerResponse);

                    $this->repo->saveOrFail($txn);

                    $fav->setTransactionId($txn->getId());
                    $this->repo->saveOrFail($fav);

                    if ($feeSplit !== null)
                    {
                        (new \RZP\Models\Transaction\Core)->saveFeeDetails($txn, $feeSplit);
                    }

                    return $txn;
                });
            },
            60,
            ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS
        );

        return [
            'entity' => $fav->getPublicId(),
            'txn'    => $txn->getPublicId(),
        ];
    }

    public function createFundAccountValidationViaLedgerCronJob(array $blacklistIds, array $whitelistIds, int $limit)
    {
        if(empty($whitelistIds) === false)
        {
            $favs = $this->repo->fund_account_validation->fetchCreatedFAVWhereTxnIdNullAndIdsIn($whitelistIds);

            return $this->processFundAccountValidationViaLedgerCronJob($blacklistIds, $favs, true);
        }

        for ($i = 0; $i < 3; $i++)
        {
            // Fetch all fund_account_validations created in the last 24 hours.
            // Doing this 3 times in for loop to fetch fav created in last 72 hours.
            // This is done so as to not put extra load on the database while querying.
            $favs = $this->repo->fund_account_validation->fetchCreatedFAVAndTxnIdNullBetweenTimestamp($i, $limit);

            $this->processFundAccountValidationViaLedgerCronJob($blacklistIds, $favs);
        }
    }

    private function processFundAccountValidationViaLedgerCronJob(array $blacklistIds, $favs, bool $skipChecks = false)
    {
        foreach ($favs as $fav)
        {
            try
            {
                /*
                 * If merchant is not on reverse shadow, and is not present in $forcedMerchantIds array,
                 * only then skip the merchant.
                 */
                if ($fav->merchant->isFeatureEnabled(Feature\Constants::LEDGER_REVERSE_SHADOW) === false)
                {
                    $this->trace->info(
                        TraceCode::LEDGER_STATUS_CRON_SKIP_MERCHANT_NOT_REVERSE_SHADOW,
                        [
                            'fav_id'      => $fav->getPublicId(),
                            'merchant_id' => $fav->getMerchantId(),
                        ]
                    );
                    continue;
                }

                if($skipChecks === false)
                {
                    if(in_array($fav->getPublicId(), $blacklistIds) === true)
                    {
                        $this->trace->info(
                            TraceCode::LEDGER_STATUS_CRON_SKIP_BLACKLIST_FAV,
                            [
                                'fav_id' => $fav->getPublicId(),
                            ]
                        );
                        continue;
                    }

                }

                $this->trace->info(
                    TraceCode::LEDGER_STATUS_CRON_FAV_INIT,
                    [
                        'fav_id' => $fav->getPublicId(),
                    ]
                );

                $ledgerRequest = (new FavLedgerProcessor())->createLedgerPayloadFromEntity($fav);

                (new LedgerStatus($this->mode, $ledgerRequest, null, false))->handle();
            }
            catch (\Throwable $e)
            {
                $this->trace->traceException(
                    $e,
                    Trace::ERROR,
                    TraceCode::LEDGER_STATUS_CRON_FAV_FAILED,
                    [
                        'fav_id' => $fav->getPublicId(),
                    ]
                );

                $this->trace->count(\RZP\Models\Payout\Metric::LEDGER_STATUS_CRON_FAILURE_COUNT,
                                    [
                                        'environment' => $this->app['env'],
                                        'entity'      => 'fund_account_validation'
                                    ]);

                continue;
            }
        }
    }

}
