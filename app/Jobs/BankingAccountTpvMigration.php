<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccountTpv\Entity;
use RZP\Models\BankingAccountTpv\Status;
use RZP\Models\BankingAccountTpv\Constants;
use RZP\Models\BankingAccountTpv\Core as TpvCore;

class BankingAccountTpvMigration extends Job
{
    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_recon';

    /**
     * @var string
     */
    public $bankingAccountTpvId;

    /**
     * @var string
     */
    public $migrationBalanceId;

    /**
     * @var integer
     */
    public $timeout = 150;

    public function __construct(string $mode, string $bankingAccountTpvId, string $migrationBalanceId)
    {
        $this->bankingAccountTpvId = $bankingAccountTpvId;

        $this->migrationBalanceId = $migrationBalanceId;

        parent::__construct($mode);
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        parent::handle();

        $this->trace->info(TraceCode::TPV_MIGRATION_JOB_INITIATE, [
            Constants::BANKING_ACCOUNT_TPV_ID => $this->bankingAccountTpvId,
            Constants::MIGRATION_BALANCE_ID   => $this->migrationBalanceId,
        ]);

        $bankingAccountTpv = null;

        try
        {
            $this->mutex->acquireAndRelease(
                Entity::getSignedId($this->bankingAccountTpvId),
                function() use (&$bankingAccountTpv) {

                    $bankingAccountTpvCore = (new TpvCore());

                    /** @var Entity $bankingAccountTpv */
                    $bankingAccountTpv = $this->repoManager->banking_account_tpv->findOrFail($this->bankingAccountTpvId);

                    $migrationBalance = $this->repoManager->balance->findOrFailById($this->migrationBalanceId);

                    if (($bankingAccountTpv->getRemarks() === Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL) or
                        ($bankingAccountTpv->getMerchantId() !== $migrationBalance->getMerchantId()) or
                        ($bankingAccountTpv->getStatus() !== Status::APPROVED) or
                        (boolval($bankingAccountTpv->getIsActive()) === false))
                    {
                        $this->trace->info(TraceCode::TPV_MIGRATION_JOB_DELETED, [
                            Constants::BANKING_ACCOUNT_TPV_ID => $this->bankingAccountTpvId,
                            Constants::MIGRATION_BALANCE_ID   => $this->migrationBalanceId,
                            Constants::STATUS                 => $bankingAccountTpv->getStatus(),
                            Entity::IS_ACTIVE                 => $bankingAccountTpv->getIsActive(),
                            Entity::REMARKS                   => $bankingAccountTpv->getRemarks(),
                        ]);

                        $this->delete();

                        return;
                    }

                    $migratedBankingAccountTpv = new Entity();

                    $tpvInput = [
                        Entity::MERCHANT_ID          => $bankingAccountTpv->getMerchantId(),
                        Entity::BALANCE_ID           => $bankingAccountTpv->getBalanceId(),
                        Entity::STATUS               => Status::APPROVED,
                        Entity::PAYER_NAME           => $bankingAccountTpv->getPayerName(),
                        Entity::PAYER_ACCOUNT_NUMBER => $bankingAccountTpv->getPayerAccountNumber(),
                        Entity::PAYER_IFSC           => $bankingAccountTpv->getPayerIfsc(),
                    ];

                    $migratedBankingAccountTpv->build($tpvInput);

                    $migratedBankingAccountTpv->balance()->associate($migrationBalance);

                    $migratedBankingAccountTpv->setIsActive(true);

                    if (is_null($bankingAccountTpv->getFundAccountValidationId()) === false)
                    {
                        $migratedBankingAccountTpv->setFundAccountValidationId($bankingAccountTpv->getFundAccountValidationId());
                    }

                    $bankingAccountTpvCore->sourceAccountAdditionForRxWallet($migratedBankingAccountTpv);

                    $this->repoManager->transaction(function() use ($migratedBankingAccountTpv, $bankingAccountTpv) {
                        $bankingAccountTpv->setRemarks(Constants::RX_WALLET_TPV_MIGRATION_SUCCESSFUL);

                        $migratedBankingAccountTpv->saveOrFail();

                        $bankingAccountTpv->saveOrFail();
                    });
                },
                120,
                ErrorCode::BAD_REQUEST_SOURCE_ACCOUNT_ADDITION_IN_PROGRESS);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                null,
                TraceCode::TPV_MIGRATION_JOB_FAILED,
                [
                    Constants::BANKING_ACCOUNT_TPV_ID => $this->bankingAccountTpvId,
                    Constants::MIGRATION_BALANCE_ID   => $this->migrationBalanceId,
                ]);

            if (isset($bankingAccountTpv) === true)
            {
                $bankingAccountTpv->setRemarks(Constants::RX_WALLET_TPV_MIGRATION_FAILURE);

                $bankingAccountTpv->saveOrFail();
            }
        }

        $workerCompletionEndTime = microtime(true);

        $workerCompletionTotalTime =  $workerCompletionEndTime - $workerStartTime;

        $this->trace->info(TraceCode::TPV_MIGRATION_JOB_COMPLETED, [
            Constants::BANKING_ACCOUNT_TPV_ID => $this->bankingAccountTpvId,
            Constants::MIGRATION_BALANCE_ID   => $this->migrationBalanceId,
            'response_time'                   => $workerCompletionTotalTime,
        ]);

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

        $context = [
            Constants::BANKING_ACCOUNT_TPV_ID => $this->bankingAccountTpvId,
            Constants::MIGRATION_BALANCE_ID   => $this->migrationBalanceId,
        ];

        $this->handleWorkerTimeoutGracefully($context);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
