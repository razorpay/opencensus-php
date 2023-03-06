<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Constants\Entity as E;
use RZP\Models\Settlement\Core;
use RZP\Models\Merchant\Balance;
use RZP\Models\Feature\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Core as BankAccount;
use RZP\Models\Settlement\SettlementServiceMigration;

class migration extends Job
{
    const MUTEX_RESOURCE     = 'SETTLEMENT_MIGRATION_%s';

    const MUTEX_LOCK_TIMEOUT = 30;

    const VIA    = 'VIA';
    const STATUS = 'STATUS';
    const REASON = 'REASON';
    const FAILED_STEPS = 'FAILED_STEPS';
    const SUCCESSFUL_STEPS = 'SUCCESSFUL_STEPS';
    const BANK_ACCOUNT_MIGRATION = 'BANK_ACCOUNT_MIGRATION';
    const MERCHANT_CONFIG_MIGRATION = 'MERCHANT_CONFIG_MIGRATION';
    const TRANSACTION_MIGRATION_DISPATCH = 'TRANSACTION_MIGRATION_DISPATCH';

    // in case when parent config is not found; we will dispatch
    // parent MID to migrate immediately & redispatch child MID after 15 minutes
    const CHILD_MID_DISPATCH_JOB_DELAY = 900; //in seconds
    // max 5 times a merchant is supposed to be attempted
    // including the first attempt
    const MIGRATION_DISPATCH_JOB_MAX_ATTEMPTS = 5;
    // job attempts count (including first attempt)
    protected $jobAttempts;


    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_initiate';

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var bool
     */
    protected $migrateBankAccount;

    /**
     * @var bool
     */
    protected $migrateMerchantConfig;

    /**
     * @var string
     * uses the field to decide whether to migrate merchant on payout or fts
     */
    protected $via;

    /**
     * @param string $mode
     * @param string $merchantId
     * @param bool $migrateBankAccount
     * @param bool $migrateMerchantConfig
     * @param $via
     */
    public function __construct(string $mode, string $merchantId, bool $migrateBankAccount, bool $migrateMerchantConfig, $via, int $attempt = null)
    {
        parent::__construct($mode);

        $this->merchantId            = $merchantId;

        $this->migrateBankAccount    = $migrateBankAccount;

        $this->migrateMerchantConfig = $migrateMerchantConfig;

        $this->via                   = $via;

        $this->jobAttempts           = $attempt ?? 1;
    }

    /**
     * Process queue request
     */
    // TODO : add retries for job properly across merchant migration job.
    public function handle()
    {
        parent::handle();

        $startTime = microtime(true);

        $input = [
            self::MERCHANT_CONFIG_MIGRATION => $this->migrateMerchantConfig,
            self::BANK_ACCOUNT_MIGRATION    => $this->migrateBankAccount,
            self::VIA                       => $this->via,
        ];

        $featureResult = $this->repoManager
                              ->feature
                              ->findMerchantWithFeatures(
                                  $this->merchantId,
                                  [
                                      Constants::DAILY_SETTLEMENT,
                                      Constants::NEW_SETTLEMENT_SERVICE,
                                  ])
                              ->pluck(Feature\Entity::NAME)
                              ->toArray();

        $skip = false;
        $skipReason = null;

        $merchant = $this->repoManager
                         ->merchant
                         ->findOrFail($this->merchantId);

        if(in_array($this->merchantId, SettlementServiceMigration::MIGRATION_BLACKLISTED_MIDS) === true)
        {
            $skip = true;
            $skipReason = 'merchant belong to blacklisted mids';
        }

        if(in_array(Constants::DAILY_SETTLEMENT, $featureResult) === true)
        {
            $skip = true;
            $skipReason = 'not supported features assigned';
        }

        if(in_array(Constants::NEW_SETTLEMENT_SERVICE, $featureResult) === true)
        {
            $skip = true;
            $skipReason = 'merchant is already migrated';
        }
        // if current retry attempt has exhausted all attempts; skip the migration further
        if($this->jobAttempts === self::MIGRATION_DISPATCH_JOB_MAX_ATTEMPTS) {
            $this->trace->info(TraceCode::SETTLEMENT_SERVICE_MIGRATION_JOB_RETRY_EXHAUSTED, [
                'merchant_id'        => $this->merchantId,
                'mode'               => $this->mode,
                'completed_attempts' => $this->jobAttempts
            ]);
            $skip = true;
            $skipReason = 'merchant migration attempts reached maximum limit';
        }

        if($skip === true)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_SKIPPED,
                [
                    'merchant_id'   => $this->merchantId,
                    'input'         => $input,
                    'reason'        => $skipReason,
                    'features'      => $featureResult,
                ]);

            return ;
        }

        //$migrationResult this will store the migration results for a merchant in this job
        $migrationResult = [
            self::SUCCESSFUL_STEPS => [
                Mode::LIVE => [
                    self::MERCHANT_CONFIG_MIGRATION => false,
                    self::BANK_ACCOUNT_MIGRATION => false,
                    self::TRANSACTION_MIGRATION_DISPATCH => false,
                ],
                Mode::TEST => [
                    self::MERCHANT_CONFIG_MIGRATION => false,
                    self::BANK_ACCOUNT_MIGRATION => false,
                    self::TRANSACTION_MIGRATION_DISPATCH => false,
                ],
            ],
            self::FAILED_STEPS => [
                Mode::LIVE => [
                    self::MERCHANT_CONFIG_MIGRATION => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                    self::BANK_ACCOUNT_MIGRATION => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                    self::TRANSACTION_MIGRATION_DISPATCH => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                ],
                Mode::TEST => [
                    self::MERCHANT_CONFIG_MIGRATION => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                    self::BANK_ACCOUNT_MIGRATION => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                    self::TRANSACTION_MIGRATION_DISPATCH => [
                        self::STATUS => false,
                        self::REASON => null,
                    ],
                ],
            ],
        ];

        // $isFailure is identifier if any step has failed.
        $isFailure = false;

        try
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_BEGIN,
                [
                    'merchant_id'     => $this->merchantId,
                     self::VIA        => $this->via,
                    'current_attempt' => $this->jobAttempts
                ]);

            $resource = sprintf(self::MUTEX_RESOURCE, $this->merchantId);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use($input, $featureResult, &$migrationResult, &$isFailure)
                {
                    if($this->migrateMerchantConfig === true)
                    {
                        try
                        {
                            (new Core)->MigrateMerchantConfiguration($this->merchantId, $this->via, Mode::LIVE);

                            $migrationResult[self::SUCCESSFUL_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION] = true;
                        }
                        catch(\Throwable $e)
                        {
                            // failure has already happened; so to avoid further processing
                            // for older job instance; marking a FAILURE here
                            $isFailure = true;

                            // in case of logical exception (parent config not found); redispatch the jobs
                            if($e->getMessage() === SettlementServiceMigration::FAILED_TO_FETCH_PARENT_CONFIG) {

                                $isDispatchFailed = $this->handleParentConfigRegisterDispatchJob($input);

                                if ($isDispatchFailed === true) {
                                    $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
                                } else {
                                    // don't go further if both jobs are redispatched
                                    return;
                                }
                            } else {
                                $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                                $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
                            }
                        }

                        try
                        {
                            (new Core)->MigrateMerchantConfiguration($this->merchantId, $this->via, Mode::TEST);

                            $migrationResult[self::SUCCESSFUL_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION] = true;

                        }
                        catch(\Throwable $e)
                        {
                            // we will not hold up further steps of migrations in case of
                            // any type of failures in TEST mode

                            // in case of logical exception (parent config not found); redispatch the jobs
                            if($e->getMessage() === SettlementServiceMigration::FAILED_TO_FETCH_PARENT_CONFIG) {

                                $isDispatchFailed = $this->handleParentConfigRegisterDispatchJob($input);

                                if ($isDispatchFailed === true) {
                                    $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
                                } else {
                                    // don't go further if both jobs are redispatched
                                    return ;
                                }
                            } else {
                                $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                                $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
                            }
                        }
                    }

                    if($this->migrateBankAccount === true)
                    {
                        try
                        {
                            (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, $this->via, Mode::LIVE);

                            $migrationResult[self::SUCCESSFUL_STEPS][Mode::LIVE][self::BANK_ACCOUNT_MIGRATION] = true;
                        }
                        catch(\Throwable $e)
                        {
                            $isFailure = true;
                            $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::BANK_ACCOUNT_MIGRATION][self::STATUS] = true;
                            $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::BANK_ACCOUNT_MIGRATION][self::REASON] = $e->getMessage();
                        }

                        try
                        {
                            (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, $this->via, Mode::TEST);

                            $migrationResult[self::SUCCESSFUL_STEPS][Mode::TEST][self::BANK_ACCOUNT_MIGRATION] = true;
                        }
                        catch(\Throwable $e)
                        {
                            // we will not hold up further steps of migrations in case of failures in TEST mode
                            $migrationResult[self::FAILED_STEPS][Mode::TEST][self::BANK_ACCOUNT_MIGRATION][self::STATUS] = true;
                            $migrationResult[self::FAILED_STEPS][Mode::TEST][self::BANK_ACCOUNT_MIGRATION][self::REASON] = $e->getMessage();
                        }
                    }
                },
                static::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $isFailure = true;

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                [
                    'merchant_id'   => $this->merchantId,
                    'input'         => $input,
                    'result'        => $migrationResult,
                    'error_message' => $e->getMessage()
                ]);
        }
        finally {
            if($isFailure === false)
            {
                try {
                        $balances = $this->repoManager->balance->getMerchantBalances($this->merchantId);

                        $this->assignNewSettlementServiceFeaturePostMigration();

                        foreach ($balances as $balance)
                        {
                            //TODO: Allow Commission Type also
                            if(Balance\Type::isSettleableBalanceType($balance->getType()) === true)
                            {
                                $opt = [
                                    'from'                => null,
                                    'to'                  => null,
                                    'balance_type'        => $balance->getType(),
                                    'transaction_ids'     => [],
                                    'initial_ramp'        => true,
                                    'source_type'         => null,
                                ];

                                try {
                                    TransactionMigrationBatch::dispatch(Mode::LIVE, $this->merchantId, $opt);

                                    $migrationResult[self::SUCCESSFUL_STEPS][Mode::LIVE][self::TRANSACTION_MIGRATION_DISPATCH] = true;
                                }
                                catch (\Throwable $e) {
                                    $isFailure = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::TRANSACTION_MIGRATION_DISPATCH][self::STATUS] = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::TRANSACTION_MIGRATION_DISPATCH][self::REASON] = $e->getMessage();
                                }

                                try {
                                    TransactionMigrationBatch::dispatch(Mode::TEST, $this->merchantId, $opt);

                                    $migrationResult[self::SUCCESSFUL_STEPS][Mode::TEST][self::TRANSACTION_MIGRATION_DISPATCH] = true;
                                }
                                catch (\Throwable $e) {
                                    $isFailure = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::TEST][self::TRANSACTION_MIGRATION_DISPATCH][self::STATUS] = true;
                                    $migrationResult[self::FAILED_STEPS][Mode::TEST][self::TRANSACTION_MIGRATION_DISPATCH][self::REASON] = $e->getMessage();
                                }
                            }
                        }
                }
                catch (\Throwable $e)
                {
                    $isFailure = true;

                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::SETTLEMENT_SERVICE_TRANSACTION_MIGRATION_DISPATCH_FAILED,
                        [
                            'merchant_id'   => $this->merchantId,
                            'result'        => $migrationResult,
                            'input'         => $input,
                            'error_message' => $e->getMessage()
                        ]);
                }
            }

            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_RESULT,
                [
                    'is_failure'  => $isFailure,
                    'merchant_id' => $this->merchantId,
                    'input'       => $input,
                    'result'      => $migrationResult,
                    'time_taken'  => microtime(true) - $startTime,
                ]);
        }
    }

    /**
     * function to handle retry attempt for merchant migration in case parent
     * migration has not happened yet but child is getting migrated first
     * in that case we will delete the child job, insert parent job & reinsert child job
     * @param $input
     * @return bool - whether queue dispatch has failed OR not
     */
    private function handleParentConfigRegisterDispatchJob($input): bool
    {
        // get current merchant
        $merchant = $this->repoManager->merchant->fetchMerchantOnConnection($this->merchantId, $this->mode);

        // this merchant would have parent ID definitely; no need to check
        $parentMID = $merchant->getParentId();

        $this->trace->info(TraceCode::SETTLEMENT_SERVICE_MIGRATION_JOB_RETRY, [
            'mode'        => $this->mode,
            'merchant_id' => $this->merchantId,
            'parent_MID'  => $parentMID,
            'input'       => $input,
            'attempted'   => $this->jobAttempts
        ]);
        try {
            //delete current child job
            $this->delete();

            // dispatch parent job at the moment (as if it's coming for first time)
            $this->dispatch($this->mode, $parentMID, $this->migrateBankAccount, $this->migrateMerchantConfig, $this->via);

            // dispatch child job with some delay & incremented attempt
            $this->dispatch($this->mode, $this->merchantId, $this->migrateBankAccount, $this->migrateMerchantConfig,
                $this->via, $this->jobAttempts + 1)->delay(self::CHILD_MID_DISPATCH_JOB_DELAY);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException($e, Trace::ERROR, TraceCode::SETTLEMENT_SERVICE_MIGRATION_JOB_RETRY_FAILED, [
                'merchant_id'    => $this->merchantId,
                'parent_MID'     => $parentMID,
                'error_message'  => $e->getMessage(),
                'attempted'      => $this->jobAttempts
            ]);
            return true;
        }
        return false;
    }

    /**
     * assignNewSettlementServiceFeaturePostMigration will assign the new settlement service
     * feature to the merchant so as to migrate bank account and the transactions
     */
    public function assignNewSettlementServiceFeaturePostMigration()
    {
        $result = (new Feature\Core)->create(
            [
                Feature\Entity::ENTITY_TYPE => E::MERCHANT,
                Feature\Entity::ENTITY_ID => $this->merchantId,
                Feature\Entity::NAME => Feature\Constants::NEW_SETTLEMENT_SERVICE,
            ], $shouldSync = true);

        $this->trace->info(
            TraceCode::SETTLEMENT_SERVICE_MIGRATION_FEATURE_ASSIGN,
            [
                'merchant_id'             => $this->merchantId,
                'result'                  => $result,
                'migrate_bank_account'    => $this->migrateBankAccount,
                'migrate_merchant_config' => $this->migrateMerchantConfig
            ]);
    }
}
