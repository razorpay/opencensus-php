<?php

namespace RZP\Jobs\Settlement;

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

    const MUTEX_LOCK_TIMEOUT = 3600;

    const LEDGER_INCONSISTENCY = 'ledger_inconsistency';

    /**
     * @var string
     */
    protected $queueConfigKey = 'merchant_invoice';

    /**
     * @var string
     */
    protected $merchantId;

    /**
     * if the job takes more time then it'll be terminated
     *
     * @var int
     */
    public $timeout = 3600;

    /**
     * @param string $mode
     * @param string $merchantId
     */
    public function __construct(string $mode, string $merchantId)
    {
        parent::__construct($mode);

        $this->merchantId = $merchantId;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try {
            $this->trace->info(
                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_BEGIN,
                [
                    'merchant_id' => $this->merchantId,
                ]);

            $merchantId = $this->merchantId;

            $balanceRepo = $this->repoManager->balance;

            $reportingReplicaConnection = $balanceRepo->getReportingReplicaConnection();

            $balance = $balanceRepo->getMerchantBalanceByType($merchantId, Type::PRIMARY, $reportingReplicaConnection);

            $balanceAmount = $balance->getBalance();

            $balanceUpdatedAt = $balance->getUpdatedAt();

            $resource = sprintf(self::MUTEX_RESOURCE, $merchantId, $this->mode);

            $result = $this->mutex->acquireAndRelease(
                $resource,
                function () use ($merchantId, $balance)
                {
                    return $this->repoManager->transaction->getUnsettledTransactionSumAndCount($merchantId, $balance);
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

            $unsettledTxnAmount = (int) $result['settlement_amount'];

            $unsettledTxnCount = (int) $result['count'];

            $diff = $unsettledTxnAmount-$balanceAmount;

            $this->checkRedisDataForInconsistency($merchantId, $diff, $balanceUpdatedAt);

            $this->trace->info(
                TraceCode::LEDGER_RECON_FOR_MERCHANT_RESULT,
                [
                    'merchant_id'        => $merchantId,
                    'txn_count'          => $unsettledTxnCount,
                    'balance_id'         => $balance->getId(),
                    'balance'            => $balanceAmount,
                    'unsettled_amount'   => $unsettledTxnAmount,
                    'difference'         => $diff,
                    'balance_updated_at' => $balanceUpdatedAt,
                ]);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::LEDGER_RECON_FOR_MERCHANT_JOB_FAILED,
                [
                    'merchant_id' => $this->merchantId,
                ]
            );
        }
        finally
        {
            $this->delete();
        }
    }

    // initial   new
    //1 null     diff non 0   set data and log
    //2 null     diff 0       no set no log
    //3 not null diff non 0   set and log
    //4 not null diff 0       delete and log
    protected function checkRedisDataForInconsistency($merchantId, $diff, $ranAt)
    {
        $redis = app('redis')->Connection('mutex_redis');

        $data = $redis->hget(self::LEDGER_INCONSISTENCY, $merchantId);

        if($data == null)
        {
            //trace new key set, the fist discrepancy
            if($diff !== 0)
            {
                $this->trace->info(
                    TraceCode::LEDGER_RECON_DIFF_FOR_MERCHANT,
                    [
                        'merchant_id' => $merchantId,
                        'prev_diff' => 0,
                        'new_diff' => $diff,
                        'change' => $diff,
                        'old_diff_time' => null,
                    ]);
            }
        }
        else
        {
            $dataArray = explode('_', $data, 2);

            $prevDiff = (int)$dataArray[0];

            $prevRanAt = $dataArray[1];

            if ($prevDiff !== $diff) {

                //trace new diff of discrepancy
                $this->trace->info(
                    TraceCode::LEDGER_RECON_DIFF_FOR_MERCHANT,
                    [
                        'merchant_id' => $merchantId,
                        'prev_diff' => $prevDiff,
                        'new_diff' => $diff,
                        'change' => $diff - $prevDiff,
                        'old_diff_time' =>  Carbon::createFromTimestamp($prevRanAt, Timezone::IST)->format('d/m/Y H:i:s'),
                    ]);

            }
        }

        if (($diff == 0) and ($data !== null))
        {
            $redis->hDel(self::LEDGER_INCONSISTENCY, $merchantId);
        }
        else if($diff !== 0)
        {
            //Setting the new values
            $redis->hSet(
                self::LEDGER_INCONSISTENCY,
                $merchantId,
                $diff . '_' . $ranAt
            );
        }

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
