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
use RZP\Models\Merchant\AccessMap\Entity;
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
    public function __construct(string $mode, string $merchantId, bool $migrateBankAccount, bool $migrateMerchantConfig, $via)
    {
        parent::__construct($mode);

        $this->merchantId            = $merchantId;

        $this->migrateBankAccount    = $migrateBankAccount;

        $this->migrateMerchantConfig = $migrateMerchantConfig;

        $this->via                   = $via;
    }

    /**
     * Process queue request
     */
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

        if(in_array($merchant->getParentId(), SettlementServiceMigration::MIGRATION_BLACKLISTED_PARENT_MIDS) === true)
        {
            $skip = true;
            $skipReason = sprintf('merchants parent mid %s belongs to blacklisted parent mids', $merchant->getParentId());
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
                    'merchant_id' => $this->merchantId,
                     self::VIA     => $this->via,
                ]);

            $resource = sprintf(self::MUTEX_RESOURCE, $this->merchantId);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use($featureResult, &$migrationResult, &$isFailure)
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
                            $isFailure = true;
                            $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                            $migrationResult[self::FAILED_STEPS][Mode::LIVE][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
                        }

                        try
                        {
                            (new Core)->MigrateMerchantConfiguration($this->merchantId, $this->via, Mode::TEST);

                            $migrationResult[self::SUCCESSFUL_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION] = true;

                        }
                        catch(\Throwable $e)
                        {
                            $isFailure = true;
                            $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::STATUS] = true;
                            $migrationResult[self::FAILED_STEPS][Mode::TEST][self::MERCHANT_CONFIG_MIGRATION][self::REASON] = $e->getMessage();
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
                            $isFailure = true;
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
                    'merchant_id' => $this->merchantId,
                    'input'       => $input,
                    'result'      => $migrationResult,
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
                            'merchant_id' => $this->merchantId,
                            'result'      => $migrationResult,
                            'input'       => $input,
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
