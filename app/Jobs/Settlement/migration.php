<?php

namespace RZP\Jobs\Settlement;

use RZP\Jobs\Job;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
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
     * @param string $mode
     * @param string $merchantId
     */
    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId    = $merchantId;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $featureResult = $this->repoManager
                              ->feature
                              ->findMerchantWithFeatures($this->merchantId, [Constants::DAILY_SETTLEMENT]);

        if ($featureResult->isEmpty() === false)
        {
            return ;
        }

        try
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_SERVICE_MIGRATION_BEGIN,
                [
                    'merchant_id' => $this->merchantId
                ]);

            $resource = sprintf(self::MUTEX_RESOURCE, $this->merchantId);

            $this->mutex->acquireAndRelease(
                $resource,
                function ()
                {
                    try
                    {
                        (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, Mode::LIVE);
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
                        (new BankAccount)->MigrateBankAccountsToSettlementService($this->merchantId, Mode::TEST);
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
}
