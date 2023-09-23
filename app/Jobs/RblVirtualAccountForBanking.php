<?php

namespace RZP\Jobs;

use App;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Payout\Metric;
use RZP\Services\RazorXClient;
use RZP\Gateway\Mozart\Action;
use RZP\Models\VirtualAccount;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Mozart\BTRblBanking;

class RblVirtualAccountForBanking extends Job
{
    const RETRY_PERIOD = 200;

    const MAX_ALLOWED_ATTEMPTS = 3;

    const MUTEX_LOCK_TIMEOUT = 180;

    const RBL_VIRTUAL_ACCOUNT_PROCESS   = 'RBL_VIRTUAL_ACCOUNT_PROCESS_';

    const WORKER_RBL_VIRTUAL_ACCOUNT_FOR_BANKING = 'worker:rbl_virtual_account_for_banking';

    protected $app;

    protected $repo;

    protected string $virtualAccountId;

    protected string $action;

    protected string $gateway;

    protected string $responseKey;

    protected bool $shouldRetry = false;

    protected bool $isSuccess = false;

    protected $virtualAccount;

    protected $queueConfigKey = 'rbl_virtual_account_for_banking';

    /**
     * Default timeout value for a job is 60s. Changing it to 250s
     * @var integer
     */
    public $timeout = 250;

    public function __construct(string $mode = null, $virtualAccountId, $action)
    {
        parent::__construct($mode);

        $this->virtualAccountId = $virtualAccountId;

        $this->action = $action;

        $this->gateway = Gateway::BT_RBL;

        if ($action === Action::CREATE_VIRTUAL_ACCOUNT_FOR_BANKING)
        {
            $this->responseKey = 'create_VA';
        }
        else
        {
            $this->responseKey = 'deactivate_VA';
        }
    }

    public function handle()
    {
        $workerStartTime = microtime(true);

        parent::handle();

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_BANKING_QUEUE_INITIATED,
            [
                'virtual_account_id' => $this->virtualAccountId,
                'action'             => $this->action,
            ]
        );

        $this->app =  App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        try
        {
            $this->mutex->acquireAndRelease(
                self::RBL_VIRTUAL_ACCOUNT_PROCESS . $this->virtualAccountId,
                function() {
                    $this->createVirtualAccountForBanking();
                },
                self::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_PROCESSING_ANOTHER_OPERATION_IN_PROGRESS);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::CREATE_VIRTUAL_ACCOUNT_BANKING_QUEUE_EXCEPTION,
                [
                    'virtualAccountId' =>  $this->virtualAccountId,
                    'action'           =>  $this->action,
                ]
            );
        }

        if (getenv('APP_ENV') === 'testing')
        {
            $this->shouldRetry = false;
        }

        if ($this->shouldRetry and
            $this->attempts() <= self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_FOR_BANKING_QUEUE_PUSH_FOR_RETRY,
                [
                    'virtualAccountId' => $this->virtualAccountId,
                    'action'           => $this->action,
                    'attempts'         => $this->attempts(),
                ]
            );

            $this->release(self::RETRY_PERIOD);
        }
        else
        {
            if ($this->isSuccess)
            {
                $this->pushSuccessMetrics($workerStartTime);
            }
            else
            {
                $this->trace->count(Metric::RBL_VIRTUAL_ACCOUNT_BANKING_JOB_FAILURES_COUNT, [
                    'action'    => $this->action,
                ]);
            }

            if (!$this->isSuccess and
                $this->action === Action::CREATE_VIRTUAL_ACCOUNT_FOR_BANKING)
            {
                $this->closeVirtualAccount();
            }

            $this->delete();
        }
    }

    protected function createVirtualAccountForBanking()
    {
        $this->virtualAccount = $this->repo->virtual_account->findOrFailPublic($this->virtualAccountId);

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_FOR_BANKING_QUEUE,
            [
                'virtual_account'    => $this->virtualAccount->toArrayPublic(),
                'action'             => $this->action,
            ]
        );

        $input = $this->getRequestInput();

        $response = $this->app['gateway']->call($this->gateway,
            $this->action,
            $input,
            $this->mode);

        if (isset($response[$this->responseKey]['Header']['Error_Cde']) === true)
        {
            if (in_array($response[$this->responseKey]['Header']['Error_Cde'],
                    BTRblBanking\ErrorCode::RETRYABLE_ERROR_CODES) === true)
            {
                $this->shouldRetry = true;
            }

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_GATEWAY_SYNC_FOR_BANKING_FAILED_AT_BANK,
                [
                    'response'           => $response,
                    'retryCount'         => $this->attempts(),
                    'action'             => $this->action,
                    'virtual_account_id' => $this->virtualAccountId,
                ]
            );
        }
        else
        {
            $this->isSuccess = true;

            $this->updateBankAccountGatewaySyncStatus();
        }
    }

    protected function getRequestInput() {

        return array(
            'gateway'     =>  $this->gateway,
            'bankAccount' =>  $this->virtualAccount->bankAccount,
        );
    }

    protected function updateBankAccountGatewaySyncStatus()
    {
        $bankAccount = $this->virtualAccount->bankAccount;
        $bankAccount->setIsGatewaySync(true);

        $this->repo->bank_account->saveOrFail($bankAccount);
    }

    protected function pushSuccessMetrics($workerStartTime)
    {
        $workerEndTime = microtime(true);

        $workerCompletionTotalTime = $workerEndTime - $workerStartTime;

        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_FOR_BANKING_QUEUE_COMPLETED,
            [
                'virtualAccountId' => $this->virtualAccountId,
                'action'           => $this->action,
                'response_time'    => $workerCompletionTotalTime,
            ]
        );

        $dimensions = [
            'worker_class'          => $this->getJobName(),
            'action'                => $this->action,
        ];

        $this->trace->histogram(
            Metric::RBL_VIRTUAL_ACCOUNT_BANKING_COMPLETED_DURATION_SECONDS,
            $workerCompletionTotalTime,
            $dimensions);
    }

    protected function closeVirtualAccount()
    {
        try
        {
            (new VirtualAccount\Core)->closeForBanking($this->virtualAccount);
        }
        catch(\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::CLOSE_VIRTUAL_ACCOUNT_BANKING_QUEUE_EXCEPTION,
                [
                    'virtualAccountId' =>  $this->virtualAccountId,
                    'action'           =>  $this->action,
                ]
            );
        }
    }

    public function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->info(
            TraceCode::VIRTUAL_ACCOUNT_FOR_BANKING_JOB_TIMEOUT,
            [
                'virtualAccountId' =>  $this->virtualAccountId,
                'action'           =>  $this->action,
            ]);

        $this->delete();

        parent::beforeJobKillCleanUp();
    }
}
