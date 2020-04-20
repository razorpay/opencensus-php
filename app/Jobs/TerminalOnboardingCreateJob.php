<?php

namespace RZP\Jobs;

use App;
use Exception;
use RZP\Trace\TraceCode;
use RZP\Models\Terminal;
use RZP\Models\Terminal\Status;
use RZP\Exception\BaseException;
use Razorpay\Trace\Logger as Trace;
use RZP\Gateway\Mpi\Enstage\Constant;
use RZP\Models\Gateway\Terminal\Metric;
use RZP\Models\Gateway\Terminal\Constants;
use RZP\Models\TerminalOnboardingDetail;
use RZP\Models\Gateway\Terminal\Service as GatewayTerminalService;

class TerminalOnboardingCreateJob extends Job
{
    protected $terminal;

    protected $terminalId;

    protected $queueConfigKey = 'terminal_onboarding_creation';

    public function __construct(string $mode, $terminalId)
    {
        parent::__construct($mode);

        $this->terminal = (new Terminal\Repository)->find($terminalId);

        $this->terminalId = $terminalId;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::TERMINAL_ONBOARDING_JOB_REQUEST,
            ['terminal_id' => $this->terminalId]
        );

        try
        {
            (new GatewayTerminalService())->callGatewayForOnboardingAsync($this->terminal);
        }
        catch (\Throwable $ex)
        {
            $this->trace->count(Metric::TERMINAL_ONBOARDING_CREATE_FAILED, [
                'mode'      => $this->mode,
            ]);

            $this->trace->traceException(
                $ex,
                Trace::ERROR,
                TraceCode::TERMINAL_ONBOARDING_CREATE_JOB_FAILURE_EXCEPTION,
                ['terminal_id' => $this->terminalId]
            );

            $exceptionData = [];

            if (method_exists($ex, 'getData'))
            {
                $exceptionData = $ex->getData();
            }

            $this->handleJobRelease($exceptionData);
        }
        finally
        {
            $this->trace->info(
                TraceCode::TERMINAL_ONBOARDING_JOB_DELETE,
                [
                    'terminal_id'                   =>  $this->terminalId,
                    'terminal_onboarding_detail'    =>  $this->terminal->terminalOnboardingDetail,
                    'message'                       => 'Deleting the job.'
                ]
            );

            $this->delete();
        }
    }

    protected function handleJobRelease(array $exceptionData)
    {
        // Using try catch, because code in 'finally' should always execute, 
        // otherwise in case of exception, terminals may remain suspended in queued state
        try
        {
            $app = App::getFacadeRoot();

            $terminalOnboardingDetail = $this->terminal->terminalOnboardingDetail;

            $terminalOnboardingDetail->incrementAttempts();

            $this->updateTerminalOnboardingDetailsErrors($exceptionData, $terminalOnboardingDetail);

            // TODO: do not fail terminals if we get retriable error
            $this->terminal->setStatus(Terminal\Status::FAILED);

            $this->terminal->save();

            $terminalOnboardingDetail->setStatus(TerminalOnboardingDetail\Status::FAILED);

            $terminalOnboardingDetail->save();

            $app['events']->fire('api.terminal.failed', ['main' => $this->terminal]);
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::TERMINAL_ONBOARDING_CREATE_JOB_RELEASE_FAILURE_EXCEPTION
            );
        }
    }

    protected function updateTerminalOnboardingDetailsErrors(array $exceptionData, $terminalOnboardingDetail)
    {
        if (isset($exceptionData[Constants::ERROR][Constants::INTERNAL_ERROR_CODE]) === true)
        {
            $terminalOnboardingDetail->setErrorCode($exceptionData[Constants::ERROR][Constants::INTERNAL_ERROR_CODE]);
        }
        
        // PSP Gateway Error 
        if ((isset($exceptionData[Constants::ERROR][Constants::GATEWAY_ERROR_CODE]) === true) and  
            ($exceptionData[Constants::ERROR][Constants::GATEWAY_ERROR_CODE] === Constants::GATEWAY_FAILURE_ERROR_CODE))
        {
            $this->trace->count(Metric::TERMINAL_ONBOARDING_PSP_GATEWAY_ERROR, [
                'mode'      => $this->mode,
            ]);

            if (isset($exceptionData[Constants::DATA][Constants::DESCRIPTION]))
            {
                $terminalOnboardingDetail->setErrorDescription($exceptionData[Constants::DATA][Constants::DESCRIPTION]);
            }
        }
        // Internal error from Mozart
        else
        {
            $this->trace->count(Metric::TERMINAL_ONBOARDING_INTERNAL_ERROR, [
                'mode'      => $this->mode,
            ]);

            if (isset($exceptionData[Constants::ERROR][Constants::DESCRIPTION]) === true)
            {
                $terminalOnboardingDetail->setErrorDescription($exceptionData[Constants::ERROR][Constants::DESCRIPTION]);
        
            }
        }
    }
}
