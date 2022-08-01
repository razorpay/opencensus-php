<?php

namespace RZP\Jobs;

use App;
use RZP\Trace\TraceCode;
use RZP\Gateway\Mozart\Action;
use RZP\Models\Payment\Gateway;
use RZP\Gateway\Mozart\BTRbl\ErrorCode;
use RZP\Models\VirtualAccount\Entity;

class RblVirtualAccountCreateProcess extends Job
{
    const RETRY_PERIOD = 300;

    const MAX_ALLOWED_ATTEMPTS = 3;

    protected $app;

    protected $repo;

    protected $virtualAccountId;

    protected $queueConfigKey = 'rbl_virtual_account_create';


    public function __construct(string $mode = null, $virtualAccountId)
    {
        parent::__construct($mode);

        $this->virtualAccountId = $virtualAccountId;
    }

    public function handle()
    {
        parent::handle();

        $this->app =  App::getFacadeRoot();

        $this->repo = $this->app['repo'];

        $isDeleteFromQueue = true;

        $this->trace->info(
            TraceCode::CREATE_VIRTUAL_ACCOUNT_QUEUE_INITIATED,
            [
                'virtual_account_id' => $this->virtualAccountId,
            ]
        );

        $errorMessage = null;

        try
        {
            $virtualAccount = $this->getVirtualAccount();

            $this->trace->info(
                TraceCode::VIRTUAL_ACCOUNT_PROCESS_QUEUE,
                $virtualAccount->toArrayPublic()
            );

            $response = $this->app['gateway']->call(Gateway::BT_RBL, Action::CREATE_VIRTUAL_ACCOUNT,
                                                    $this->getRequestInput($virtualAccount), $this->mode);

            if ((isset($response['create_VA']['Header']['Error_Cde']) === true) and
                (in_array($response['create_VA']['Header']['Error_Cde'], ErrorCode::ERROR_CODE) === true))
            {
                if ($this->attempts() < self::MAX_ALLOWED_ATTEMPTS)
                {
                    $isDeleteFromQueue = false;
                }

                $this->trace->info(
                    TraceCode::VIRTUAL_ACCOUNT_GATEWAY_SYNC_FAILED,
                    [
                        'response'    => $response,
                        'retryCount'  => $this->attempts(),
                    ]
                );
            }

            if ($isDeleteFromQueue === true)
            {
                $this->delete();

                $this->updateBankAccountGatewaySyncStatus($virtualAccount);

                $this->trace->info(
                    TraceCode::CREATE_VIRTUAL_ACCOUNT_QUEUE_COMPLETED,
                    [
                        'virtualAccountId' => $this->virtualAccountId,
                        'retryCount'       => $this->attempts(),
                    ]
                );
            }
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::VIRTUAL_ACCOUNT_GATEWAY_SYNC_FAILED,
                [
                    'message'            => $ex->getMessage(),
                    'virtual_account_id' => $this->virtualAccountId,
                    'retryCount'         => $this->attempts()
                ]
            );

            if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
            {
                $this->delete();
            }
            else
            {
                $this->release(self::RETRY_PERIOD);
            }
        }
    }

    private function getVirtualAccount()
    {
        try
        {
            return $this->repo->virtual_account->findOrFailPublic($this->virtualAccountId);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::VIRTUAL_ACCOUNT_NOT_FOUND,
                [
                    'virtualAccountId' =>  $this->virtualAccountId,
                ]
            );

            throw $ex;
        }
    }

    protected function getRequestInput(Entity $virtualAccount) {

       return array(
           'gateway'     => Gateway::BT_RBL,
           'bankAccount' =>  $virtualAccount->bankAccount,
       );
    }

    protected function updateBankAccountGatewaySyncStatus($virtualAccount)
    {
        $bankAccount = $virtualAccount->bankAccount;
        $bankAccount->setIsGatewaySync(true);

        try
        {
            return $this->repo->bank_account->saveOrFail($bankAccount);
        }
        catch (\Exception $ex)
        {
            $this->trace->traceException(
                $ex,
                null,
                TraceCode::BANK_ACCOUNT_SYNC_UPADTE_FAILED,
                [
                    'bank_account' => $bankAccount->getId(),
                ]
            );

            throw $ex;
        }
    }
}
