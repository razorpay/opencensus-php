<?php

namespace RZP\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Services\RazorXClient;

class RblUniqueGatewayBalanceUpdate extends Job implements ShouldBeUnique
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'rbl_banking_account_gateway_balance_update';

    // config for priority balance update queue
    public const PRIORITY_QUEUE_CONFIG_KEY = 'rbl_banking_account_gateway_balance_priority_update';

    protected $metricsEnabled = true;

    /**
     * @var int Each job will remain unique for 200s
     *
     * After this, the redis key used to maintain the lock will expire.
     */
    public int $uniqueFor = 240;

    protected $params;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    /**
     * @return string Returns the unique ID, i.e. merchant_id_channel, to be used to take locks and ensure that only unique
     * messages are present in the queue.
     */
    public function uniqueId()
    {
        return $this->params[BankingAccount\Entity::MERCHANT_ID] . '_' . $this->params[BankingAccount\Entity::CHANNEL];
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $BACore = new BankingAccount\Core;

            if ($BACore->gatewayBalanceUpdateDeleteMode($this->params[BankingAccount\Entity::CHANNEL]) === true)
            {
                $this->trace->info(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE, [
                    'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                    'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                    'queue_name'  => $this->queue,
                ]);

                $this->delete();

                return;
            }

            $this->trace->info(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_INIT, [
                'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                'job_name'    => $this->getJobName(),
                'queue_name'  => $this->queue,
            ]);

            $BACore->fetchAndUpdateGatewayBalanceWrapper($this->params);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                TraceCode::ERROR_EXCEPTION,
                TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_FAILED,
                [
                    'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                    'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                    'job_name'    => $this->getJobName(),
                    'queue_name'  => $this->queue,
                ]);
        }

        $this->delete();
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->delete();

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
            'queue_name'  => $this->queue,
        ]);
    }
}
