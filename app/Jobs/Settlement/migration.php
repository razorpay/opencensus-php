<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity as E;
use RZP\Models\Settlement\Core;
use RZP\Models\Feature\Constants;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\BankAccount\Core as BankAccount;

class migration extends Job
{
    const MUTEX_RESOURCE     = 'SETTLEMENT_MIGRATION_%s';

    const MUTEX_LOCK_TIMEOUT = 30;

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

        $featureResult = $this->repoManager
                              ->feature
                              ->findMerchantWithFeatures(
                                  $this->merchantId,
                                  [
                                      Constants::DAILY_SETTLEMENT,
                                  ])
                              ->toArray();

        if (in_array(Constants::DAILY_SETTLEMENT, $featureResult) === true)
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_SKIPPED,
                [
                    'reason'   => 'not supported features assigned',
                    'features' => $featureResult
                ]);

            return ;
        }

        try
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_BEGIN,
                [
                    'merchant_id' => $this->merchantId,
                    'via'         => $this->via,
                ]);

            $resource = sprintf(self::MUTEX_RESOURCE, $this->merchantId);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use($featureResult)
                {
                    if($this->migrateBankAccount === true)
                    {
                        $this->assignNewSettlementServiceFeaturePostMigration();

                        try
                        {
                            (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, $this->via, Mode::LIVE);
                        }
                        catch(\Throwable $e)
                        {
                            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                                [
                                    'merchant_id' => $this->merchantId,
                                    'step'        => 'bank account migration',
                                    'mode'        => 'live',
                                ]);
                        }

                        try
                        {
                            (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, $this->via, Mode::TEST);
                        }
                        catch(\Throwable $e)
                        {
                            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                                [
                                    'merchant_id' => $this->merchantId,
                                    'step'        => 'bank account migration',
                                    'mode'        => 'test',
                                ]);
                        }
                    }

                    if($this->migrateMerchantConfig === true)
                    {
                        try
                        {
                            (new Core)->MigrateMerchantConfiguration($this->merchantId, Mode::LIVE);;
                        }
                        catch(\Throwable $e)
                        {
                            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                                [
                                    'merchant_id' => $this->merchantId,
                                    'step'        => 'merchant configuration migration',
                                    'mode'        => 'live',
                                ]);
                        }

                        try
                        {
                            (new Core)->MigrateMerchantConfiguration($this->merchantId, Mode::TEST);;
                        }
                        catch(\Throwable $e)
                        {
                            $this->trace->traceException(
                                $e,
                                Trace::ERROR,
                                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                                [
                                    'merchant_id' => $this->merchantId,
                                    'step'        => 'merchant configuration migration',
                                    'mode'        => 'test',
                                ]);
                        }
                    }
                },
                static::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_FAILED,
                [
                    'merchant_id' => $this->merchantId
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
