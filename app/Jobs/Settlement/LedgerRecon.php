<?php

namespace RZP\Jobs\Settlement;

use RZP\Constants\Entity;
use RZP\Jobs\Job;
use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Balance\Type as Type;

class LedgerRecon extends Job
{
    const MUTEX_RESOURCE = 'LEDGER_RECON_%s_%s';

    const MUTEX_LOCK_TIMEOUT = 7200;

    const LEDGER_CRON_MID_COUNT = 'ledger_cron_mid_count_%s';

    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_invoice';

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * @var string
     */
    protected $cronId;

    /**
     * @var
     */
    protected $baseLineDiscrepancy;

    /**
     * @var bool
     */
    protected $setBaselineDiscrepancyZero;

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 7200;

    /**
     * @param string $mode
     * @param string $merchantId
     * @param string $cronId
     * @param $baseLineDiscrepancy
     * @param bool $setBaselineDiscrepancyZero
     */
    public function __construct(string $mode, string $merchantId, string $cronId, $baseLineDiscrepancy = null, bool $setBaselineDiscrepancyZero = false)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;

        $this->cronId = $cronId;

        $this->baseLineDiscrepancy = $baseLineDiscrepancy;

        $this->setBaselineDiscrepancyZero = $setBaselineDiscrepancyZero;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        $merchantId = $this->merchantId;

        try
        {
            $this->trace->info(
                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_BEGIN,
                [
                    'cron_id'     => $this->cronId,
                    'merchant_id' => $merchantId,
                ]);

            $this->incrementCronExecutionMerchantCount(1);

            $resource = sprintf(self::MUTEX_RESOURCE, $merchantId, $this->mode);

            $this->mutex->acquireAndRelease(
                $resource,
                function () use ($merchantId)
                {
                    $diff1 = $this->getCurrentDiscrepancy($merchantId);

                    if($this->shouldRecheckDiscrepancy($diff1) === true)
                    {
                        $diff2 = $this->getCurrentDiscrepancy($merchantId);

                        if(($diff2 === $diff1) or ($diff2 === 0))
                        {
                            $this->processLedgerCron($merchantId, $diff2);
                        }
                        else
                        {
                            $this->trace->info(
                                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_SKIPPED,
                                [
                                    'cron_id'     => $this->cronId,
                                    'merchant_id' => $merchantId,
                                    'diff1'       => $diff1,
                                    'diff2'       => $diff2
                                ]);
                        }
                    }
                    else
                    {
                        $this->processLedgerCron($merchantId, $diff1);
                    }
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

            $this->trace->info(
                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_FINISH,
                [
                    'cron_id'     => $this->cronId,
                    'merchant_id' => $merchantId,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_FAILED,
                [
                    'cron_id'     => $this->cronId,
                    'merchant_id' => $this->merchantId,
                ]
            );
        }
        finally
        {
            $currentCount = $this->incrementCronExecutionMerchantCount(-1);

            $this->trace->info(
                TraceCode::LEDGER_RECON_CRON_REDIS_COUNT,
                [
                    'cron_id'       => $this->cronId,
                    'merchant_id'   => $merchantId,
                    'current_count' => $currentCount
                ]);

            if($currentCount === 0)
            {
                $this->markCronExecutionProcessed();

                $this->deleteKeyForCronExecution();
            }

            $this->delete();
        }
    }

    protected function getCurrentDiscrepancy($merchantId)
    {
        $balanceRepo = $this->repoManager->balance;

        $balance = $balanceRepo->getMerchantBalanceByTypeFromDataLake($merchantId, Type::PRIMARY);

        $this->trace->info(
            TraceCode::LEDGER_RECON_FOR_MERCHANT_RESULT,
            [
                'balance'        => $balance
            ]);

        $balanceAmount = $balance[Entity::BALANCE];

        $balanceUpdatedAt = $balance[Entity::UPDATED_AT];

        $result = $this->repoManager->transaction->getUnsettledTransactionSumAndCount($merchantId, $balance);

        $unsettledTxnAmount = (int) $result['settlement_amount'];

        $unsettledTxnCount = (int) $result['count'];

        $diff = $unsettledTxnAmount-$balanceAmount;

        $this->trace->info(
            TraceCode::LEDGER_RECON_FOR_MERCHANT_RESULT,
            [
                'merchant_id'        => $merchantId,
                'cron_id'            => $this->cronId,
                'txn_count'          => $unsettledTxnCount,
                'balance_id'         => $balance['id'],
                'balance'            => $balanceAmount,
                'unsettled_amount'   => $unsettledTxnAmount,
                'difference'         => $diff,
                'balance_updated_at' => $balanceUpdatedAt,
            ]);

        return $diff;
    }

    protected function shouldRecheckDiscrepancy($diff1) : bool
    {
        if($diff1 === 0)
        {
            return false;
        }

        return true;
    }

    protected function processLedgerCron($merchantId, $currentDiscrepancy)
    {
        $activeDiscrepancy = false;

        if(isset($this->baseLineDiscrepancy) === true)
        {
            $baselineDiscrepancy = $this->baseLineDiscrepancy;

            $activeDiscrepancy = true;
        }
        else
        {
            $data = $this->checkForLedgerCronActiveMtu($merchantId);

            if($data['status'] === false)
            {
                $baselineDiscrepancy = ($this->setBaselineDiscrepancyZero === true) ? 0 : $currentDiscrepancy;

                if($currentDiscrepancy !== 0)
                {
                    $activeDiscrepancy = true;
                }

                $this->addToLedgerCronActiveMtu($merchantId, $baselineDiscrepancy, $currentDiscrepancy);
            }
            else
            {
                $baselineDiscrepancy = $data['baseline_discrepancy'];
                $activeDiscrepancy   = $data['active_discrepancy'];

                if(($activeDiscrepancy === false) and ($currentDiscrepancy !== 0))
                {
                    $activeDiscrepancy = true;

                    $this->updateMtuAsActiveDiscrepancy();
                }
            }
        }

        if($currentDiscrepancy === 0)
        {
            $baselineDiscrepancy = 0;

            //update baseline discrepancy to 0 if mtu is already present
            $this->updateMtuBaselineDiscrepancyToZero();
        }

        if($activeDiscrepancy === true)
        {
            $this->addToLedgerCronResults($merchantId, $currentDiscrepancy, $baselineDiscrepancy);
        }
    }

    protected function checkForLedgerCronActiveMtu($merchantId) : array
    {
        $status = false;
        $activeDiscrepancy = false;
        $baselineDiscrepancy = 0;

        $input = ['merchant_id' => $merchantId];
        $response = app('settlements_api')->ledgerCronActiveMtuCheck($input, $this->mode);

        $this->traceLedgerCronStep(TraceCode::LEDGER_CRON_ACTIVE_MTU_CHECK, $input, $response);

        if(empty($response) === false)
        {
            $status = true;
            if(isset($response['active_discrepancy']) === true)
            {
                $activeDiscrepancy = $response['active_discrepancy'];
            }

            if(isset($response['baseline_discrepancy']) === true)
            {
                $baselineDiscrepancy = $response['baseline_discrepancy'];
            }
        }

        return [
            "status" => $status,
            "baseline_discrepancy" => (int) $baselineDiscrepancy,
            "active_discrepancy" => $activeDiscrepancy,
        ];
    }

    protected function addToLedgerCronActiveMtu($merchantId, $baselineDiscrepancy, $currentDiscrepancy)
    {
        $activeDiscrepancy = ($currentDiscrepancy !== 0);

        $input = [
            'merchant_id' => $merchantId,
            'baseline_discrepancy' => $baselineDiscrepancy,
            'active_discrepancy' => $activeDiscrepancy,
            ];

        $response = app('settlements_api')->ledgeCronActiveMtuAdd($input, $this->mode);

        $this->traceLedgerCronStep(TraceCode::LEDGER_CRON_ACTIVE_MTU_ADD, $input, $response);
    }

    protected function addToLedgerCronResults($merchantId, $currentDiscrepancy, $baselineDiscrepancy)
    {
        [$adjustmentAmount, $adjustmentStatus] = $this->getAdjustmentAmountAndStatus($currentDiscrepancy, $baselineDiscrepancy);

        $input = [
            'merchant_id'           => $merchantId,
            'cron_id'               => $this->cronId,
            'current_discrepancy'   => $currentDiscrepancy,
            'baseline_discrepancy'  => $baselineDiscrepancy,
            'adjustment_amount'     => $adjustmentAmount,
            'adjustment_needed'     => $adjustmentStatus,
        ];

        $response = app('settlements_api')->ledgeCronResultAdd($input, $this->mode);

        $this->traceLedgerCronStep(TraceCode::LEDGER_CRON_RESULT_ADD, $input, $response);
    }

    protected function traceLedgerCronStep($traceCode, $input, $response)
    {
        $this->trace->info(
            $traceCode,
            [
                'input'     => $input,
                'response'  => $response,
                'cron_id'   => $this->cronId,
            ]);
    }

    protected function getAdjustmentAmountAndStatus($currentDiscrepancy, $baselineDiscrepancy) : array
    {
        if(($currentDiscrepancy === 0) or ($currentDiscrepancy === $baselineDiscrepancy))
        {
            return [0, false];
        }

        if($this->isSignChange($currentDiscrepancy, $baselineDiscrepancy) === true)
        {
            return [$currentDiscrepancy, true];
        }

        if(abs($currentDiscrepancy) > abs($baselineDiscrepancy))
        {
            return [($currentDiscrepancy - $baselineDiscrepancy), true];
        }

        return [$currentDiscrepancy, false];
    }

    protected function isSignChange($currentDiscrepancy, $baselineDiscrepancy) : bool
    {
        return ((($currentDiscrepancy < 0) and ($baselineDiscrepancy > 0)) or ((($currentDiscrepancy > 0) and ($baselineDiscrepancy < 0))));
    }

    protected function incrementCronExecutionMerchantCount($count) : int
    {
        $redis = app('redis')->Connection('mutex_redis');

        $key = sprintf(self::LEDGER_CRON_MID_COUNT, $this->mode);

        return (int) $redis->hincrby($key, $this->cronId, $count);
    }

    protected function deleteKeyForCronExecution()
    {
        $redis = app('redis')->Connection('mutex_redis');

        $key = sprintf(self::LEDGER_CRON_MID_COUNT, $this->mode);

        $redis->hdel($key, $this->cronId);
    }

    protected function markCronExecutionProcessed()
    {
        $cronExecutionUpdateInput = [
            'cron_id'   => $this->cronId,
            'status'    => 'processed'
        ];

        app('settlements_api')->ledgeCronExecutionUpdate($cronExecutionUpdateInput);
    }

    protected function updateMtuBaselineDiscrepancyToZero()
    {
        $mtuUpdateInput = [
            'merchant_id'   => $this->merchantId,
            'baseline_discrepancy'  => 0
        ];

        app('settlements_api')->ledgerReconActiveMtuUpdate($mtuUpdateInput);
    }

    protected function updateMtuAsActiveDiscrepancy()
    {
        $mtuUpdateInput = [
            'merchant_id'          => $this->merchantId,
            'active_discrepancy'   => true
        ];

        app('settlements_api')->ledgerReconActiveMtuUpdate($mtuUpdateInput);
    }

    protected function beforeJobKillCleanUp()
    {
        $this->trace->info(
            TraceCode::LEDGER_RECON_FOR_MERCHANT_BEGIN_JOB_TIMEOUT,
            [
                'merchant_id' => $this->merchantId,
            ]);

        $this->delete();

        parent::beforeJobKillCleanUp();
    }
}
